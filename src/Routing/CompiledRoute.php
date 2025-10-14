<?php

namespace BaseApi\Routing;

/**
 * Immutable compiled route data transfer object.
 * Optimized for Opcache and zero-allocation dispatch.
 */
final readonly class CompiledRoute
{
    /**
     * @param string $method HTTP method
     * @param string $path Original path pattern
     * @param array<string> $segments Path segments for trie matching
     * @param array<string> $paramNames Parameter names in order
     * @param array<int,string|null> $paramConstraints Parameter constraints (regex patterns) indexed by segment position
     * @param array<string|array> $middlewares Precomputed middleware stack (includes optioned middleware)
     * @param string $controller Controller class name
     * @param bool $isStatic Whether this is a static route (no params)
     * @param string|null $compiledRegex Fallback regex for complex patterns
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $segments,
        public array $paramNames,
        public array $paramConstraints,
        public array $middlewares,
        public string $controller,
        public bool $isStatic,
        public ?string $compiledRegex = null
    ) {
    }

    /**
     * Match a path against this compiled route.
     *
     * @param array<string> $segments Path segments to match
     * @return array<string,string>|null Parameters if matched, null otherwise
     */
    public function matchSegments(array $segments): ?array
    {
        if (count($segments) !== count($this->segments)) {
            return null;
        }

        $params = [];
        $paramIndex = 0;
        $segmentCount = count($segments);

        for ($i = 0; $i < $segmentCount; $i++) {
            $pattern = $this->segments[$i];

            // Check if this segment is a parameter (starts with {)
            if (str_starts_with($pattern, '{')) {
                // This is a parameter segment
                $constraint = $this->paramConstraints[$i] ?? null;
                
                if ($constraint === null) {
                    // Unconstrained parameter - matches anything
                    if (isset($this->paramNames[$paramIndex])) {
                        $params[$this->paramNames[$paramIndex]] = $segments[$i];
                    }
                } elseif (preg_match($constraint, $segments[$i])) {
                    // Constrained parameter matches
                    if (isset($this->paramNames[$paramIndex])) {
                        $params[$this->paramNames[$paramIndex]] = $segments[$i];
                    }
                } else {
                    // Constraint failed
                    return null;
                }
                
                $paramIndex++;
            } elseif ($pattern !== $segments[$i]) {
                // Static segment doesn't match
                return null;
            }
        }

        return $params;
    }
}

