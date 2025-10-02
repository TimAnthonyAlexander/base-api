<?php

declare(strict_types=1);

namespace BaseApi\OpenApi\Emitters;

use BaseApi\OpenApi\IR\ApiIR;
use BaseApi\OpenApi\IR\OperationIR;

class RoutesEmitter
{
    public function emit(ApiIR $ir): string
    {
        $lines = [];

        // Header
        $lines[] = '// Generated route constants and path builder for ' . $ir->title;
        $lines[] = "// Do not edit manually - regenerate with: ./mason types:generate";
        $lines[] = "";

        // Route constants
        $lines[] = "export const Routes = {";
        foreach ($ir->routes as $route) {
            $key = $this->toPascalCase($route->key);
            $lines[] = sprintf("  %s: '%s',", $key, $route->template);
        }

        $lines[] = "} as const;";
        $lines[] = "";

        // Route keys type
        $lines[] = "export type RouteKey = keyof typeof Routes;";
        $lines[] = "";

        // Path builder function
        $lines = array_merge($lines, $this->emitPathBuilder($ir));

        return implode("\n", $lines);
    }

    private function emitPathBuilder(ApiIR $ir): array
    {
        $lines = [];

        $lines[] = "/**";
        $lines[] = " * Build a path from a route key and parameters";
        $lines[] = " * @param key - The route key";
        $lines[] = " * @param params - Path parameters to substitute";
        $lines[] = " * @returns The built path";
        $lines[] = " */";
        $lines[] = "export function buildPath<K extends RouteKey>(";
        $lines[] = "  key: K,";
        $lines[] = "  params?: Record<string, string | number>";
        $lines[] = "): string {";
        $lines[] = "  let path = Routes[key];";
        $lines[] = "";
        $lines[] = "  if (params) {";
        $lines[] = "    for (const [paramKey, paramValue] of Object.entries(params)) {";
        $lines[] = "      path = path.replace(`{\${paramKey}}`, encodeURIComponent(String(paramValue)));";
        $lines[] = "    }";
        $lines[] = "  }";
        $lines[] = "";
        $lines[] = "  return path;";
        $lines[] = "}";
        $lines[] = "";

        // Generate type-safe path builder for each route
        $lines[] = "// Type-safe path builders for each route";
        $lines[] = "";

        foreach ($ir->operations as $operation) {
            if ($operation->pathParams !== []) {
                $lines = array_merge($lines, $this->emitTypedPathBuilder($operation));
                $lines[] = "";
            }
        }

        return $lines;
    }

    private function emitTypedPathBuilder(OperationIR $operation): array
    {
        $lines = [];
        $opName = $this->toPascalCase($operation->operationId);
        $routeKey = $this->toPascalCase($operation->operationId);

        // Build params type
        $paramTypes = [];
        foreach ($operation->pathParams as $param) {
            $paramTypes[] = $param->name . ': string | number';
        }

        $paramsType = '{ ' . implode('; ', $paramTypes) . ' }';

        $lines[] = sprintf('export function build%sPath(params: %s): string {', $opName, $paramsType);
        $lines[] = sprintf("  return buildPath('%s', params);", $routeKey);
        $lines[] = "}";

        return $lines;
    }

    private function toPascalCase(string $str): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $str)));
    }
}
