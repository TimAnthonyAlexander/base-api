<?php

namespace Tests;

use Override;
use PHPUnit\Framework\TestCase;
use BaseApi\Permissions\PermissionsService;
use BaseApi\Auth\UserProvider;
use InvalidArgumentException;

class PermissionsServiceTest extends TestCase
{
    private string $testFilePath;

    private PermissionsService $service;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary test file
        $this->testFilePath = sys_get_temp_dir() . '/test_permissions_' . uniqid() . '.json';

        // Create service (will auto-create file with defaults)
        $this->service = new PermissionsService($this->testFilePath);
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test file
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
    }

    public function testDefaultGroupsAreCreated(): void
    {
        $groups = $this->service->getGroups();

        $this->assertArrayHasKey('guest', $groups);
        $this->assertArrayHasKey('user', $groups);
        $this->assertArrayHasKey('premium', $groups);
        $this->assertArrayHasKey('admin', $groups);
    }

    public function testGroupInheritance(): void
    {
        $userGroup = $this->service->getGroup('user');
        $this->assertEquals(['guest'], $userGroup['inherits']);

        $premiumGroup = $this->service->getGroup('premium');
        $this->assertEquals(['user'], $premiumGroup['inherits']);

        $adminGroup = $this->service->getGroup('admin');
        $this->assertEquals(['premium'], $adminGroup['inherits']);
    }

    public function testCheckRoleWithDirectPermission(): void
    {
        $allowed = $this->service->checkRole('guest', 'auth.login');
        $this->assertTrue($allowed);

        $denied = $this->service->checkRole('guest', 'admin.delete');
        $this->assertFalse($denied);
    }

    public function testCheckRoleWithInheritedPermission(): void
    {
        // User inherits from guest
        $allowed = $this->service->checkRole('user', 'auth.login');
        $this->assertTrue($allowed);
    }

    public function testCheckRoleWithWildcard(): void
    {
        // Admin has wildcard permission '*'
        $allowed = $this->service->checkRole('admin', 'anything.at.all');
        $this->assertTrue($allowed);
    }

    public function testCheckRoleWithSpecificWildcard(): void
    {
        // Premium has 'export.*' permission
        $allowed = $this->service->checkRole('premium', 'export.csv');
        $this->assertTrue($allowed);

        $allowed2 = $this->service->checkRole('premium', 'export.pdf');
        $this->assertTrue($allowed2);

        $denied = $this->service->checkRole('premium', 'import.csv');
        $this->assertFalse($denied);
    }

    public function testCreateGroup(): void
    {
        $this->service->createGroup('moderator', 25);

        $group = $this->service->getGroup('moderator');
        $this->assertNotNull($group);
        $this->assertEquals(25, $group['weight']);
        $this->assertEquals([], $group['inherits']);
        $this->assertEquals([], $group['permissions']);
    }

    public function testCreateDuplicateGroupThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->createGroup('guest', 0);
    }

    public function testDeleteGroup(): void
    {
        $this->service->createGroup('temp', 0);
        $this->assertTrue($this->service->groupExists('temp'));

        $this->service->deleteGroup('temp');
        $this->assertFalse($this->service->groupExists('temp'));
    }

    public function testDeleteGroupWithDependentsThrowsException(): void
    {
        // Try to delete 'guest' which is inherited by 'user'
        $this->expectException(InvalidArgumentException::class);
        $this->service->deleteGroup('guest');
    }

    public function testSetGroupWeight(): void
    {
        $this->service->setGroupWeight('user', 50);

        $group = $this->service->getGroup('user');
        $this->assertEquals(50, $group['weight']);
    }

    public function testAddParent(): void
    {
        $this->service->createGroup('editor', 20);
        $this->service->addParent('editor', 'user');

        $group = $this->service->getGroup('editor');
        $this->assertEquals(['user'], $group['inherits']);
    }

    public function testAddParentCircularThrowsException(): void
    {
        $this->service->createGroup('a', 10);
        $this->service->createGroup('b', 10);
        $this->service->addParent('a', 'b');

        $this->expectException(InvalidArgumentException::class);
        $this->service->addParent('b', 'a');
    }

    public function testRemoveParent(): void
    {
        $this->service->removeParent('user', 'guest');

        $group = $this->service->getGroup('user');
        $this->assertEquals([], $group['inherits']);
    }

    public function testGrantPermission(): void
    {
        $this->service->grant('user', 'test.permission', true);

        $group = $this->service->getGroup('user');
        $this->assertTrue($group['permissions']['test.permission']);
    }

    public function testGrantDenyPermission(): void
    {
        $this->service->grant('user', 'test.deny', false);

        $group = $this->service->getGroup('user');
        $this->assertFalse($group['permissions']['test.deny']);
    }

    public function testRevokePermission(): void
    {
        $this->service->grant('user', 'test.permission', true);
        $this->service->revoke('user', 'test.permission');

        $group = $this->service->getGroup('user');
        $this->assertArrayNotHasKey('test.permission', $group['permissions']);
    }

    public function testValidateSucceeds(): void
    {
        $errors = $this->service->validate();
        $this->assertEmpty($errors);
    }

    public function testValidateDetectsInvalidPermissionNode(): void
    {
        // Manually inject an invalid node to test validation (bypassing grant's validation)
        $config = json_decode(file_get_contents($this->testFilePath), true);
        $config['groups']['user']['permissions']['Invalid-Node!'] = true;
        file_put_contents($this->testFilePath, json_encode($config));
        $this->service->reload();

        $errors = $this->service->validate();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Invalid permission node', $errors[0]);
    }

    public function testTraceShowsResolution(): void
    {
        // Create a mock user provider
        $userProvider = new class implements UserProvider {
            public function byId(string $id): ?array
            {
                return ['id' => $id, 'role' => 'user'];
            }

            public function getRole(string $id): ?string
            {
                return 'user';
            }

            public function setRole(string $id, string $role): bool
            {
                return true;
            }
        };

        $this->service->setUserProvider($userProvider);

        $trace = $this->service->trace('test-user', 'auth.login');

        $this->assertEquals('test-user', $trace['userId']);
        $this->assertEquals('user', $trace['role']);
        $this->assertEquals('auth.login', $trace['node']);
        $this->assertEquals('allow', $trace['result']);
        $this->assertNotEmpty($trace['matches']);
        $this->assertContains('user', $trace['inheritanceChain']);
        $this->assertContains('guest', $trace['inheritanceChain']);
    }

    public function testCheckWithUserProvider(): void
    {
        $userProvider = new class implements UserProvider {
            public function byId(string $id): ?array
            {
                return ['id' => $id, 'role' => 'admin'];
            }

            public function getRole(string $id): ?string
            {
                return 'admin';
            }

            public function setRole(string $id, string $role): bool
            {
                return true;
            }
        };

        $this->service->setUserProvider($userProvider);

        $allowed = $this->service->check('admin-user', 'anything');
        $this->assertTrue($allowed);
    }

    public function testCheckWithoutUserProviderFallsBackToGuest(): void
    {
        // No user provider set
        $allowed = $this->service->check('unknown-user', 'auth.login');
        $this->assertTrue($allowed); // Guest can login

        $denied = $this->service->check('unknown-user', 'admin.delete');
        $this->assertFalse($denied); // Guest can't access admin
    }

    public function testSpecificityMatching(): void
    {
        // Create a test scenario with overlapping permissions
        $this->service->grant('user', 'content.*', true, true); // force=true for wildcard
        $this->service->grant('user', 'content.delete', false);

        // More specific permission (content.delete) should win
        $denied = $this->service->checkRole('user', 'content.delete');
        $this->assertFalse($denied);

        // Other content permissions should be allowed
        $allowed = $this->service->checkRole('user', 'content.read');
        $this->assertTrue($allowed);
    }

    public function testWeightBasedResolution(): void
    {
        // Create two groups with different weights
        $this->service->createGroup('editor', 20);
        $this->service->createGroup('reviewer', 15);

        // Both grant content.edit, but editor has higher weight
        $this->service->grant('editor', 'content.edit', true);
        $this->service->grant('reviewer', 'content.edit', false);

        // Create a user that inherits from both
        $this->service->createGroup('hybrid', 30, ['editor', 'reviewer']);

        // Editor's permission should win due to higher weight
        $allowed = $this->service->checkRole('hybrid', 'content.edit');
        $this->assertTrue($allowed);
    }

    public function testFileReloading(): void
    {
        // Modify the file directly
        $config = json_decode(file_get_contents($this->testFilePath), true);
        $config['groups']['test_group'] = [
            'inherits' => [],
            'weight' => 99,
            'permissions' => []
        ];
        file_put_contents($this->testFilePath, json_encode($config));

        // Reload
        $this->service->reload();

        // Check that new group is loaded
        $this->assertTrue($this->service->groupExists('test_group'));
    }

    public function testAtomicFileWrites(): void
    {
        // Create a group
        $this->service->createGroup('atomic_test', 10);

        // Verify file exists and is valid JSON
        $this->assertFileExists($this->testFilePath);
        $content = file_get_contents($this->testFilePath);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('groups', $data);
        $this->assertArrayHasKey('atomic_test', $data['groups']);
    }

    public function testGetRolePermissionsIncludesInherited(): void
    {
        $perms = $this->service->getRolePermissions('user');

        // Should include guest permissions
        $this->assertArrayHasKey('auth.login', $perms);

        // Should include user permissions
        $this->assertArrayHasKey('content.create', $perms);
    }

    public function testImplicitDeny(): void
    {
        // Permission that doesn't exist anywhere should be denied
        $denied = $this->service->checkRole('user', 'nonexistent.permission');
        $this->assertFalse($denied);
    }

    public function testValidPermissionNodeFormat(): void
    {
        // Valid nodes
        $this->service->grant('user', 'valid.node', true);
        $this->service->grant('user', 'a', true);
        $this->service->grant('user', 'a.b.c.d', true);
        $this->service->grant('user', 'test123', true);
        
        // Create admin group with high weight for wildcards
        $this->service->createGroup('superadmin', 100);
        $this->service->grant('superadmin', '*', true, true); // force=true for universal wildcard
        $this->service->grant('superadmin', 'a.b.*', true, true); // force=true for wildcard

        $errors = $this->service->validate();
        $this->assertEmpty($errors);
    }

    public function testInvalidPermissionNodeFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->grant('user', 'Invalid-Node', true);
    }
}

