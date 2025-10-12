<?php

namespace BaseApi\Permissions;

use BaseApi\Auth\UserProvider;
use Exception;
use InvalidArgumentException;

/**
 * PermissionsService manages role-based permissions from a file-based configuration.
 * 
 * Features:
 * - Group inheritance with weight-based precedence
 * - Wildcard permission matching (*, a.*, a.b.*)
 * - Deterministic resolution with caching
 * - Atomic file updates
 */
class PermissionsService
{
    private array $config = [];

    private array $flatPermissions = [];

    private ?UserProvider $userProvider = null;

    public function __construct(private readonly string $filePath)
    {
        $this->ensureFileExists();
        $this->load();
    }

    /**
     * Set the user provider for role resolution.
     */
    public function setUserProvider(UserProvider $provider): void
    {
        $this->userProvider = $provider;
    }

    /**
     * Check if a user has permission for a given node.
     */
    public function check(string $userId, string $node): bool
    {
        $role = $this->resolveRole($userId);
        return $this->checkRole($role, $node);
    }

    /**
     * Check if a role has permission for a given node.
     */
    public function checkRole(string $role, string $node): bool
    {
        $permissions = $this->getFlatPermissions($role);

        return $this->resolveNode($permissions, $node);
    }

    /**
     * Get all permissions for a role (including inherited).
     */
    public function getRolePermissions(string $role): array
    {
        return $this->getFlatPermissions($role);
    }

    /**
     * Get all groups.
     */
    public function getGroups(): array
    {
        return $this->config['groups'] ?? [];
    }

    /**
     * Get a specific group configuration.
     */
    public function getGroup(string $id): ?array
    {
        return $this->config['groups'][$id] ?? null;
    }

    /**
     * Check if a group exists.
     */
    public function groupExists(string $id): bool
    {
        return isset($this->config['groups'][$id]);
    }

    /**
     * Create a new group.
     */
    public function createGroup(string $id, int $weight = 0, array $inherits = []): void
    {
        $this->validateGroupId($id);

        if ($this->groupExists($id)) {
            throw new InvalidArgumentException(sprintf('Group "%s" already exists', $id));
        }

        $this->config['groups'][$id] = [
            'inherits' => $inherits,
            'weight' => $weight,
            'permissions' => []
        ];

        $this->save();
    }

    /**
     * Rename a group and update all parent references.
     */
    public function renameGroup(string $oldId, string $newId): void
    {
        if (!$this->groupExists($oldId)) {
            throw new InvalidArgumentException(sprintf('Group "%s" does not exist', $oldId));
        }

        $this->validateGroupId($newId);

        if ($this->groupExists($newId)) {
            throw new InvalidArgumentException(sprintf('Group "%s" already exists', $newId));
        }

        // Copy the group with new ID
        $this->config['groups'][$newId] = $this->config['groups'][$oldId];

        // Update all groups that inherit from this one
        foreach ($this->config['groups'] as &$group) {
            $inheritsIndex = array_search($oldId, $group['inherits'], true);
            if ($inheritsIndex !== false) {
                $group['inherits'][$inheritsIndex] = $newId;
            }
        }

        // Remove old group
        unset($this->config['groups'][$oldId]);

        $this->save();
    }

    /**
     * Delete a group.
     */
    public function deleteGroup(string $id): void
    {
        if (!$this->groupExists($id)) {
            throw new InvalidArgumentException(sprintf('Group "%s" does not exist', $id));
        }

        // Check if any other groups inherit from this group
        foreach ($this->config['groups'] as $groupId => $group) {
            if (in_array($id, $group['inherits'])) {
                throw new InvalidArgumentException(
                    sprintf('Cannot delete group "%s": group "%s" inherits from it', $id, $groupId)
                );
            }
        }

        unset($this->config['groups'][$id]);
        $this->save();
    }

    /**
     * Set group weight.
     */
    public function setGroupWeight(string $id, int $weight): void
    {
        if (!$this->groupExists($id)) {
            throw new InvalidArgumentException(sprintf('Group "%s" does not exist', $id));
        }

        $this->config['groups'][$id]['weight'] = $weight;
        $this->save();
    }

    /**
     * Add a parent group to inherit from.
     */
    public function addParent(string $id, string $parent): void
    {
        if (!$this->groupExists($id)) {
            throw new InvalidArgumentException(sprintf('Group "%s" does not exist', $id));
        }

        if (!$this->groupExists($parent)) {
            throw new InvalidArgumentException(sprintf('Parent group "%s" does not exist', $parent));
        }

        if ($id === $parent) {
            throw new InvalidArgumentException('A group cannot inherit from itself');
        }

        $inherits = $this->config['groups'][$id]['inherits'];
        if (in_array($parent, $inherits)) {
            return; // Already inherits
        }

        $inherits[] = $parent;

        // Check for circular inheritance
        $this->validateInheritance($id, $inherits);

        $this->config['groups'][$id]['inherits'] = $inherits;
        $this->save();
    }

    /**
     * Remove a parent group.
     */
    public function removeParent(string $id, string $parent): void
    {
        if (!$this->groupExists($id)) {
            throw new InvalidArgumentException(sprintf('Group "%s" does not exist', $id));
        }

        $inherits = $this->config['groups'][$id]['inherits'];
        $inherits = array_values(array_filter($inherits, fn($p): bool => $p !== $parent));

        $this->config['groups'][$id]['inherits'] = $inherits;
        $this->save();
    }

    /**
     * Grant a permission to a group.
     * 
     * @param string $group Group ID
     * @param string $node Permission node
     * @param bool $allow True to allow, false to deny
     * @param bool $force Skip dangerous operation check for wildcards
     */
    public function grant(string $group, string $node, bool $allow = true, bool $force = false): void
    {
        if (!$this->groupExists($group)) {
            throw new InvalidArgumentException(sprintf('Group "%s" does not exist', $group));
        }

        $this->validatePermissionNode($node);

        // Warn about dangerous wildcard grants on low-weight groups
        $groupData = $this->getGroup($group);
        if (!$force && $allow && ($node === '*' || str_ends_with($node, '.*')) && $groupData['weight'] < 50) {
            throw new InvalidArgumentException(
                sprintf('DANGEROUS: Granting wildcard permission "%s" to low-weight group "%s". Use --force to confirm.', $node, $group)
            );
        }

        $this->config['groups'][$group]['permissions'][$node] = $allow;
        $this->save();
    }

    /**
     * Revoke a permission from a group.
     */
    public function revoke(string $group, string $node): void
    {
        if (!$this->groupExists($group)) {
            throw new InvalidArgumentException(sprintf('Group "%s" does not exist', $group));
        }

        unset($this->config['groups'][$group]['permissions'][$node]);
        $this->save();
    }

    /**
     * Validate the entire permissions structure.
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->config['groups'])) {
            $errors[] = 'No groups defined';
            return $errors;
        }

        foreach ($this->config['groups'] as $id => $group) {
            // Validate inherits references
            foreach ($group['inherits'] as $parent) {
                if (!isset($this->config['groups'][$parent])) {
                    $errors[] = sprintf('Group "%s" inherits from non-existent group "%s"', $id, $parent);
                }
            }

            // Validate weight
            if (!is_int($group['weight'])) {
                $errors[] = sprintf('Group "%s" has invalid weight (must be integer)', $id);
            }

            // Validate permissions
            foreach ($group['permissions'] as $node => $value) {
                if (!is_bool($value)) {
                    $errors[] = sprintf('Group "%s" has invalid permission value for "%s" (must be boolean)', $id, $node);
                }

                try {
                    $this->validatePermissionNode($node);
                } catch (Exception $e) {
                    $errors[] = sprintf('Group "%s": %s', $id, $e->getMessage());
                }
            }
        }

        // Check for circular inheritance
        foreach (array_keys($this->config['groups']) as $id) {
            try {
                $this->validateInheritance($id, $this->config['groups'][$id]['inherits']);
            } catch (Exception $e) {
                $errors[] = sprintf('Group "%s": %s', $id, $e->getMessage());
            }
        }

        return $errors;
    }

    /**
     * Trace permission resolution for debugging.
     */
    public function trace(string $userId, string $node): array
    {
        $role = $this->resolveRole($userId);
        $permissions = $this->getFlatPermissions($role);
        $chain = $this->getInheritanceChain($role);

        $matches = [];
        $allCandidates = [];

        // Collect all matching patterns with their source groups
        foreach ($chain as $groupId) {
            $group = $this->getGroup($groupId);
            if ($group === null) {
                continue;
            }

            foreach ($group['permissions'] as $pattern => $value) {
                $candidate = [
                    'pattern' => $pattern,
                    'value' => $value,
                    'group' => $groupId,
                    'weight' => $group['weight'],
                    'specificity' => $this->getSpecificity($pattern),
                    'matches' => $this->nodeMatches($pattern, $node)
                ];

                $allCandidates[] = $candidate;

                if ($candidate['matches']) {
                    $matches[] = $candidate;
                }
            }
        }

        // Sort matches by specificity (highest first)
        usort($matches, fn($a, $b): int => $b['specificity'] <=> $a['specificity']);

        // Determine the winning rule and explain tie-breaking
        $winner = null;
        $tieBreakExplanation = '';

        if ($matches !== []) {
            $topSpecificity = $matches[0]['specificity'];
            $topMatches = array_filter($matches, fn($m): bool => $m['specificity'] === $topSpecificity);

            if (count($topMatches) === 1) {
                $winner = $topMatches[0];
                $tieBreakExplanation = 'Unique match by specificity';
            } else {
                // Multiple matches with same specificity - use weight
                $topWeight = max(array_column($topMatches, 'weight'));
                $weightFiltered = array_filter($topMatches, fn($m): bool => $m['weight'] === $topWeight);

                if (count($weightFiltered) === 1) {
                    $winner = array_values($weightFiltered)[0];
                    $tieBreakExplanation = sprintf('Tie-break by weight (%d)', $topWeight);
                } else {
                    // Same specificity and weight - deny takes precedence
                    $denyMatch = null;
                    $allowMatch = null;

                    foreach ($weightFiltered as $match) {
                        if (!$match['value']) {
                            $denyMatch = $match;
                            break;
                        }

                        $allowMatch = $match;
                    }

                    $winner = $denyMatch ?? $allowMatch;
                    $tieBreakExplanation = sprintf('Tie-break by weight (%d), deny precedence', $topWeight);
                }
            }
        }

        $result = $this->resolveNode($permissions, $node);

        return [
            'userId' => $userId,
            'role' => $role,
            'node' => $node,
            'result' => $result ? 'allow' : 'deny',
            'matches' => $matches,
            'allCandidates' => $allCandidates,
            'winner' => $winner,
            'tieBreakExplanation' => $tieBreakExplanation,
            'inheritanceChain' => $chain,
            'implicitDeny' => $matches === []
        ];
    }

    /**
     * Reload permissions from file.
     */
    public function reload(): void
    {
        $this->load();
    }

    /**
     * Ensure permissions file exists with default structure.
     */
    private function ensureFileExists(): void
    {
        if (file_exists($this->filePath)) {
            return;
        }

        $dir = dirname($this->filePath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new Exception(sprintf('Failed to create permissions directory: %s', $dir));
        }

        $default = $this->getDefaultConfig();
        $this->writeFile($default);
    }

    /**
     * Get default permissions configuration.
     */
    private function getDefaultConfig(): array
    {
        return [
            'groups' => [
                'guest' => [
                    'inherits' => [],
                    'weight' => 0,
                    'permissions' => [
                        'auth.login' => true,
                        'auth.signup' => true,
                        'content.read' => true
                    ]
                ],
                'user' => [
                    'inherits' => ['guest'],
                    'weight' => 10,
                    'permissions' => [
                        'content.create' => true,
                        'content.comment' => true,
                        'profile.edit' => true
                    ]
                ],
                'premium' => [
                    'inherits' => ['user'],
                    'weight' => 50,
                    'permissions' => [
                        'content.premium' => true,
                        'export.*' => true
                    ]
                ],
                'admin' => [
                    'inherits' => ['premium'],
                    'weight' => 100,
                    'permissions' => [
                        '*' => true
                    ]
                ]
            ],
            'meta' => [
                'createdAt' => date('c'),
                'updatedAt' => date('c')
            ]
        ];
    }

    /**
     * Load permissions from file with shared lock.
     */
    private function load(): void
    {
        try {
            $handle = fopen($this->filePath, 'r');
            if ($handle === false) {
                throw new Exception('Failed to open permissions file');
            }

            // Acquire shared lock for reading
            if (!flock($handle, LOCK_SH)) {
                fclose($handle);
                throw new Exception('Failed to acquire read lock');
            }

            $content = stream_get_contents($handle);

            flock($handle, LOCK_UN);
            fclose($handle);

            if ($content === false) {
                throw new Exception('Failed to read permissions file');
            }

            $config = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in permissions file: ' . json_last_error_msg());
            }

            $this->config = $config;
            $this->flatPermissions = []; // Clear cache
        } catch (Exception $exception) {
            // If we have a previous valid config, keep using it
            if ($this->config === []) {
                throw $exception;
            }
        }
    }

    /**
     * Save permissions to file atomically.
     */
    private function save(): void
    {
        $this->config['meta']['updatedAt'] = date('c');
        $this->writeFile($this->config);
        $this->flatPermissions = []; // Clear cache
    }

    /**
     * Write config to file atomically with exclusive lock and fsync.
     */
    private function writeFile(array $config): void
    {
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Create temp file in same directory to ensure same filesystem
        $dir = dirname($this->filePath);
        $tempFile = $dir . '/.permissions.' . uniqid() . '.tmp';

        $handle = fopen($tempFile, 'w');
        if ($handle === false) {
            throw new Exception('Failed to create temporary permissions file');
        }

        // Acquire exclusive lock
        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            @unlink($tempFile);
            throw new Exception('Failed to acquire write lock');
        }

        if (fwrite($handle, $json) === false) {
            flock($handle, LOCK_UN);
            fclose($handle);
            @unlink($tempFile);
            throw new Exception('Failed to write permissions file');
        }

        // Force sync to disk before rename
        if (!fsync($handle)) {
            flock($handle, LOCK_UN);
            fclose($handle);
            @unlink($tempFile);
            throw new Exception('Failed to sync permissions file to disk');
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        // Atomic rename (within same filesystem)
        if (!rename($tempFile, $this->filePath)) {
            @unlink($tempFile);
            throw new Exception('Failed to save permissions file');
        }
    }

    /**
     * Resolve user's role via UserProvider.
     */
    private function resolveRole(string $userId): string
    {
        if (!$this->userProvider instanceof UserProvider) {
            return 'guest';
        }

        try {
            $role = $this->userProvider->getRole($userId);
            return $role ?? 'guest';
        } catch (Exception) {
            return 'guest';
        }
    }

    /**
     * Get flat permissions map for a role (with caching).
     */
    private function getFlatPermissions(string $role): array
    {
        if (isset($this->flatPermissions[$role])) {
            return $this->flatPermissions[$role];
        }

        $chain = $this->getInheritanceChain($role);
        $permissions = [];

        // Collect permissions from all inherited groups
        foreach ($chain as $groupId) {
            $group = $this->getGroup($groupId);
            if ($group === null) {
                continue;
            }

            foreach ($group['permissions'] as $node => $value) {
                $permissions[] = [
                    'node' => $node,
                    'value' => $value,
                    'weight' => $group['weight'],
                    'group' => $groupId
                ];
            }
        }

        // Flatten to single map
        $flat = [];
        foreach ($permissions as $perm) {
            $node = $perm['node'];

            if (!isset($flat[$node])) {
                $flat[$node] = $perm['value'];
            } else {
                // Higher weight wins, deny overrides allow on equal weight
                $existing = array_filter($permissions, fn($p): bool => $p['node'] === $node);
                $sorted = $this->sortByPrecedence($existing);
                $flat[$node] = $sorted[0]['value'];
            }
        }

        $this->flatPermissions[$role] = $flat;
        return $flat;
    }

    /**
     * Get inheritance chain for a role (including the role itself).
     */
    private function getInheritanceChain(string $role): array
    {
        $chain = [];
        $visited = [];
        $this->collectInheritance($role, $chain, $visited);
        return $chain;
    }

    /**
     * Recursively collect inheritance chain.
     */
    private function collectInheritance(string $role, array &$chain, array &$visited): void
    {
        if (in_array($role, $visited)) {
            return;
        }

        $visited[] = $role;
        $group = $this->getGroup($role);

        if ($group === null) {
            return;
        }

        // First collect parents
        foreach ($group['inherits'] as $parent) {
            $this->collectInheritance($parent, $chain, $visited);
        }

        // Then add self
        $chain[] = $role;
    }

    /**
     * Resolve permission for a node from flat permissions map.
     */
    private function resolveNode(array $permissions, string $node): bool
    {
        $matches = [];

        foreach ($permissions as $pattern => $value) {
            if ($this->nodeMatches($pattern, $node)) {
                $matches[] = [
                    'pattern' => $pattern,
                    'value' => $value,
                    'specificity' => $this->getSpecificity($pattern)
                ];
            }
        }

        if ($matches === []) {
            return false; // Implicit deny
        }

        // Sort by specificity (highest first)
        usort($matches, fn($a, $b): int => $b['specificity'] <=> $a['specificity']);

        return $matches[0]['value'];
    }

    /**
     * Check if a pattern matches a node.
     */
    private function nodeMatches(string $pattern, string $node): bool
    {
        if ($pattern === $node) {
            return true; // Exact match
        }

        if ($pattern === '*') {
            return true; // Universal wildcard
        }

        // Convert wildcard pattern to regex
        $regex = '/^' . str_replace(['\\*', '\\.'], ['[^.]+', '\\.'], preg_quote($pattern, '/')) . '$/';
        return preg_match($regex, $node) === 1;
    }

    /**
     * Get specificity score for a permission pattern.
     * Higher is more specific.
     */
    private function getSpecificity(string $pattern): int
    {
        if ($pattern === '*') {
            return 0;
        }

        $parts = explode('.', $pattern);
        $score = count($parts) * 10;

        // Penalize wildcards
        foreach ($parts as $part) {
            if ($part === '*') {
                $score -= 5;
            }
        }

        return $score;
    }

    /**
     * Sort permissions by precedence.
     */
    private function sortByPrecedence(array $permissions): array
    {
        usort($permissions, function (array $a, array $b): int {
            // Higher weight wins
            if ($a['weight'] !== $b['weight']) {
                return $b['weight'] <=> $a['weight'];
            }

            // Deny overrides allow on equal weight
            if ($a['value'] !== $b['value']) {
                return $a['value'] ? 1 : -1;
            }

            return 0;
        });

        return $permissions;
    }

    /**
     * Validate inheritance to prevent circular dependencies.
     */
    private function validateInheritance(string $id, array $inherits): void
    {
        $visited = [$id];
        foreach ($inherits as $parent) {
            $this->checkCircular($parent, $visited);
        }
    }

    /**
     * Recursively check for circular inheritance.
     */
    private function checkCircular(string $id, array $visited): void
    {
        if (in_array($id, $visited)) {
            throw new InvalidArgumentException(
                sprintf('Circular inheritance detected: %s', implode(' -> ', [...$visited, $id]))
            );
        }

        $group = $this->getGroup($id);
        if ($group === null) {
            return;
        }

        $visited[] = $id;
        foreach ($group['inherits'] as $parent) {
            $this->checkCircular($parent, $visited);
        }
    }

    /**
     * Validate group ID format.
     * Group IDs must be lowercase alphanumeric with hyphens/underscores.
     * Reserved words: '*', 'all', 'any'
     */
    private function validateGroupId(string $id): void
    {
        // Check for reserved words
        if (in_array($id, ['*', 'all', 'any'])) {
            throw new InvalidArgumentException(
                sprintf('Group ID "%s" is reserved', $id)
            );
        }

        // Only lowercase alphanumeric, hyphens, and underscores
        // Must start with a letter
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $id)) {
            throw new InvalidArgumentException(
                sprintf('Invalid group ID "%s". Must start with a letter and contain only lowercase letters, numbers, hyphens, and underscores', $id)
            );
        }

        // Prevent dots in group IDs to avoid confusion with permission nodes
        if (str_contains($id, '.')) {
            throw new InvalidArgumentException(
                sprintf('Group ID "%s" cannot contain dots', $id)
            );
        }

        // Reasonable length limit
        if (strlen($id) > 64) {
            throw new InvalidArgumentException(
                sprintf('Group ID "%s" exceeds maximum length of 64 characters', $id)
            );
        }
    }

    /**
     * Validate permission node format.
     * Nodes must be lowercase alphanumeric segments separated by dots.
     * Wildcard (*) can only appear as full segment or entire node.
     */
    private function validatePermissionNode(string $node): void
    {
        // Universal wildcard is allowed
        if ($node === '*') {
            return;
        }

        // Check if node is empty
        if ($node === '' || $node === '0') {
            throw new InvalidArgumentException('Permission node cannot be empty');
        }

        // Split into segments
        $segments = explode('.', $node);

        foreach ($segments as $i => $segment) {
            // Each segment must be non-empty
            if ($segment === '' || $segment === '0') {
                throw new InvalidArgumentException(
                    sprintf('Invalid permission node "%s". Segments cannot be empty (found at position %d)', $node, $i)
                );
            }

            // Wildcard must be entire segment, not partial
            if (str_contains($segment, '*') && $segment !== '*') {
                throw new InvalidArgumentException(
                    sprintf('Invalid permission node "%s". Wildcard must be entire segment, not "%s"', $node, $segment)
                );
            }

            // Only lowercase alphanumeric or wildcard
            if (!preg_match('/^([a-z0-9]+|\*)$/', $segment)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid permission node "%s". Segment "%s" contains invalid characters. Use only lowercase letters, numbers, dots, and * for wildcards', $node, $segment)
                );
            }
        }

        // Wildcard can only appear as last segment (except for universal *)
        $wildcardCount = 0;
        foreach ($segments as $i => $segment) {
            if ($segment === '*') {
                $wildcardCount++;
                if ($i !== count($segments) - 1) {
                    throw new InvalidArgumentException(
                        sprintf('Invalid permission node "%s". Wildcard can only appear as the last segment', $node)
                    );
                }
            }
        }

        // Maximum of one wildcard
        if ($wildcardCount > 1) {
            throw new InvalidArgumentException(
                sprintf('Invalid permission node "%s". Only one wildcard allowed per node', $node)
            );
        }

        // Reasonable total length
        if (strlen($node) > 128) {
            throw new InvalidArgumentException(
                sprintf('Permission node "%s" exceeds maximum length of 128 characters', $node)
            );
        }
    }
}

