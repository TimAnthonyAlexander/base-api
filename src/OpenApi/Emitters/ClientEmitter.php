<?php

declare(strict_types=1);

namespace BaseApi\OpenApi\Emitters;

use BaseApi\OpenApi\IR\ApiIR;
use BaseApi\OpenApi\IR\OperationIR;

class ClientEmitter
{
    public function emitHttp(ApiIR $ir): string
    {
        $lines = [];

        $lines[] = '// Generated HTTP client for ' . $ir->title;
        $lines[] = "// Do not edit manually - regenerate with: ./mason types:generate";
        $lines[] = "";

        $lines[] = "export interface HttpOptions {";
        $lines[] = "  headers?: Record<string, string>;";
        $lines[] = "  signal?: AbortSignal;";
        $lines[] = "}";
        $lines[] = "";

        $lines[] = "export class ApiError extends Error {";
        $lines[] = "  status: number;";
        $lines[] = "  requestId?: string;";
        $lines[] = "  errors?: Record<string, string>;";
        $lines[] = "";
        $lines[] = "  constructor(";
        $lines[] = "    message: string,";
        $lines[] = "    status: number,";
        $lines[] = "    requestId?: string,";
        $lines[] = "    errors?: Record<string, string>";
        $lines[] = "  ) {";
        $lines[] = "    super(message);";
        $lines[] = "    this.name = 'ApiError';";
        $lines[] = "    this.status = status;";
        $lines[] = "    this.requestId = requestId;";
        $lines[] = "    this.errors = errors;";
        $lines[] = "  }";
        $lines[] = "}";
        $lines[] = "";

        $baseUrl = $ir->baseUrl ?? '';
        $lines[] = sprintf("const BASE_URL = '%s';", $baseUrl);
        $lines[] = "";

        $lines[] = "async function fetchApi<T>(";
        $lines[] = "  path: string,";
        $lines[] = "  method: string,";
        $lines[] = "  options?: HttpOptions & { body?: unknown }";
        $lines[] = "): Promise<T> {";
        $lines[] = "  const url = BASE_URL + path;";
        $lines[] = "  ";
        $lines[] = "  const response = await fetch(url, {";
        $lines[] = "    method,";
        $lines[] = "    credentials: 'include',";
        $lines[] = "    headers: {";
        $lines[] = "      'Content-Type': 'application/json',";
        $lines[] = "      ...options?.headers,";
        $lines[] = "    },";
        $lines[] = "    body: options?.body ? JSON.stringify(options.body) : undefined,";
        $lines[] = "    signal: options?.signal,";
        $lines[] = "  });";
        $lines[] = "";
        $lines[] = "  // Handle 204 No Content and HEAD requests";
        $lines[] = "  if (response.status === 204 || method === 'HEAD') {";
        $lines[] = "    if (!response.ok) {";
        $lines[] = "      throw new ApiError('Request failed', response.status);";
        $lines[] = "    }";
        $lines[] = "    return undefined as T;";
        $lines[] = "  }";
        $lines[] = "";
        $lines[] = "  // Parse response based on content type";
        $lines[] = "  const contentType = response.headers.get('content-type');";
        $lines[] = "  const isJson = contentType?.includes('application/json');";
        $lines[] = "  ";
        $lines[] = "  let data: any;";
        $lines[] = "  try {";
        $lines[] = "    data = isJson ? await response.json() : await response.text();";
        $lines[] = "  } catch (err) {";
        $lines[] = "    // Failed to parse response";
        $lines[] = "    if (!response.ok) {";
        $lines[] = "      throw new ApiError('Request failed', response.status);";
        $lines[] = "    }";
        $lines[] = "    throw new ApiError('Failed to parse response', response.status);";
        $lines[] = "  }";
        $lines[] = "";
        $lines[] = "  if (!response.ok) {";
        $lines[] = "    // Handle error responses";
        $lines[] = "    if (typeof data === 'object' && data !== null) {";
        $lines[] = "      throw new ApiError(";
        $lines[] = "        data.error || 'Request failed',";
        $lines[] = "        response.status,";
        $lines[] = "        data.requestId,";
        $lines[] = "        data.errors";
        $lines[] = "      );";
        $lines[] = "    } else {";
        $lines[] = "      throw new ApiError(";
        $lines[] = "        typeof data === 'string' ? data : 'Request failed',";
        $lines[] = "        response.status";
        $lines[] = "      );";
        $lines[] = "    }";
        $lines[] = "  }";
        $lines[] = "";
        $lines[] = "  return data;";
        $lines[] = "}";
        $lines[] = "";

        $lines[] = "export const http = {";
        $lines[] = "  get: <T>(path: string, options?: HttpOptions) => ";
        $lines[] = "    fetchApi<T>(path, 'GET', options),";
        $lines[] = "  ";
        $lines[] = "  post: <T>(path: string, body: unknown, options?: HttpOptions) => ";
        $lines[] = "    fetchApi<T>(path, 'POST', { ...options, body }),";
        $lines[] = "  ";
        $lines[] = "  put: <T>(path: string, body: unknown, options?: HttpOptions) => ";
        $lines[] = "    fetchApi<T>(path, 'PUT', { ...options, body }),";
        $lines[] = "  ";
        $lines[] = "  patch: <T>(path: string, body: unknown, options?: HttpOptions) => ";
        $lines[] = "    fetchApi<T>(path, 'PATCH', { ...options, body }),";
        $lines[] = "  ";
        $lines[] = "  delete: <T>(path: string, options?: HttpOptions) => ";
        $lines[] = "    fetchApi<T>(path, 'DELETE', options),";
        $lines[] = "  ";
        $lines[] = "  head: <T>(path: string, options?: HttpOptions) => ";
        $lines[] = "    fetchApi<T>(path, 'HEAD', options),";
        $lines[] = "  ";
        $lines[] = "  options: <T>(path: string, options?: HttpOptions) => ";
        $lines[] = "    fetchApi<T>(path, 'OPTIONS', options),";
        $lines[] = "};";

        return implode("\n", $lines);
    }

    public function emitClient(ApiIR $ir): string
    {
        $lines = [];

        $lines[] = '// Generated API client functions for ' . $ir->title;
        $lines[] = "// Do not edit manually - regenerate with: ./mason types:generate";
        $lines[] = "";
        $lines[] = "import { http, type HttpOptions } from './http';";
        $lines[] = "import { buildPath } from './routes';";
        $lines[] = "import * as Types from './types';";
        $lines[] = "";

        foreach ($ir->operations as $operation) {
            $lines = array_merge($lines, $this->emitOperationFunction($operation));
            $lines[] = "";
        }

        return implode("\n", $lines);
    }

    public function emitHooks(ApiIR $ir): string
    {
        $lines = [];

        $lines[] = '// Generated React hooks for ' . $ir->title;
        $lines[] = "// Do not edit manually - regenerate with: ./mason types:generate";
        $lines[] = "";
        $lines[] = "import { useState, useEffect, useCallback, type DependencyList } from 'react';";
        $lines[] = "import { type HttpOptions } from './http';";
        $lines[] = "import * as Api from './client';";
        $lines[] = "import * as Types from './types';";
        $lines[] = "";

        // Base hook interfaces
        $lines[] = "export interface QueryOptions<T> extends HttpOptions {";
        $lines[] = "  enabled?: boolean;";
        $lines[] = "  onSuccess?: (data: T) => void;";
        $lines[] = "  onError?: (error: Error) => void;";
        $lines[] = "}";
        $lines[] = "";

        $lines[] = "export interface QueryResult<T> {";
        $lines[] = "  data: T | null;";
        $lines[] = "  loading: boolean;";
        $lines[] = "  error: Error | null;";
        $lines[] = "  refetch: () => Promise<void>;";
        $lines[] = "}";
        $lines[] = "";

        $lines[] = "export interface MutationResult<T, TVariables> {";
        $lines[] = "  data: T | null;";
        $lines[] = "  loading: boolean;";
        $lines[] = "  error: Error | null;";
        $lines[] = "  mutate: (variables: TVariables) => Promise<T>;";
        $lines[] = "  reset: () => void;";
        $lines[] = "}";
        $lines[] = "";

        foreach ($ir->operations as $operation) {
            $lines = array_merge($lines, $this->emitOperationHook($operation));
            $lines[] = "";
        }

        return implode("\n", $lines);
    }

    private function emitOperationFunction(OperationIR $operation): array
    {
        $lines = [];
        $opName = $this->toCamelCase($operation->operationId);
        $opNamePascal = $this->toPascalCase($operation->operationId);

        // Build function signature
        $params = [];

        // Add path params
        if ($operation->pathParams !== []) {
            $params[] = sprintf('path: Types.%sPathParams', $opNamePascal);
        }

        // Add query params for all methods that have them
        if ($operation->queryParams !== []) {
            $params[] = sprintf('query?: Types.%sQueryParams', $opNamePascal);
        }

        // Add body for POST/PUT/PATCH
        if (in_array($operation->method, ['POST', 'PUT', 'PATCH']) && $operation->body) {
            $params[] = sprintf('body: Types.%sRequestBody', $opNamePascal);
        }

        $params[] = "options?: HttpOptions";

        $paramsStr = implode(', ', $params);

        $lines[] = "/**";
        $lines[] = sprintf(' * %s %s', $operation->method, $operation->path);
        if ($operation->tags !== []) {
            $lines[] = " * @tags " . implode(', ', $operation->tags);
        }

        $lines[] = " */";
        $lines[] = sprintf('export async function %s(%s): Promise<Types.%sResponse> {', $opName, $paramsStr, $opNamePascal);

        // Build the path
        if ($operation->pathParams !== []) {
            $routeKey = $this->toPascalCase($operation->operationId);
            $lines[] = sprintf("  const url = buildPath('%s', path);", $routeKey);
        } else {
            $lines[] = sprintf("  const url = '%s';", $operation->path);
        }

        // Add query string handling for any method with query params
        if ($operation->queryParams !== []) {
            $lines[] = "  const searchParams = new URLSearchParams();";
            $lines[] = "  if (query) {";
            $lines[] = "    for (const [key, value] of Object.entries(query)) {";
            $lines[] = "      if (value !== undefined) {";
            $lines[] = "        searchParams.append(key, String(value));";
            $lines[] = "      }";
            $lines[] = "    }";
            $lines[] = "  }";
            $lines[] = "  const fullUrl = searchParams.toString() ? `\${url}?\${searchParams}` : url;";
            $lines[] = "";

            if (in_array($operation->method, ['POST', 'PUT', 'PATCH']) && $operation->body) {
                $method = strtolower($operation->method);
                $lines[] = sprintf('  return http.%s(fullUrl, body, options);', $method);
            } else {
                $method = strtolower($operation->method);
                $lines[] = sprintf('  return http.%s(fullUrl, options);', $method);
            }
        } elseif (in_array($operation->method, ['POST', 'PUT', 'PATCH']) && $operation->body) {
            $method = strtolower($operation->method);
            $lines[] = "";
            $lines[] = sprintf('  return http.%s(url, body, options);', $method);
        } else {
            $method = strtolower($operation->method);
            $lines[] = "";
            $lines[] = sprintf('  return http.%s(url, options);', $method);
        }

        $lines[] = "}";

        return $lines;
    }

    private function emitOperationHook(OperationIR $operation): array
    {
        $lines = [];
        $opName = $this->toCamelCase($operation->operationId);
        $opNamePascal = $this->toPascalCase($operation->operationId);
        $hookName = 'use' . $opNamePascal;

        if ($operation->method === 'GET') {
            // Query hook (auto-fetch)
            return array_merge($lines, $this->emitQueryHook($operation, $hookName, $opName, $opNamePascal));
        }

        // Mutation hook (manual trigger)
        return array_merge($lines, $this->emitMutationHook($operation, $hookName, $opName, $opNamePascal));
    }

    private function emitQueryHook(OperationIR $operation, string $hookName, string $opName, string $opNamePascal): array
    {
        $lines = [];

        // Build parameters
        $params = [];
        if ($operation->pathParams !== []) {
            $params[] = sprintf('path: Types.%sPathParams', $opNamePascal);
        }

        if ($operation->queryParams !== []) {
            $params[] = sprintf('query?: Types.%sQueryParams', $opNamePascal);
        }

        $params[] = sprintf('options?: QueryOptions<Types.%sResponse>', $opNamePascal);
        $params[] = "deps?: DependencyList";

        $paramsStr = implode(', ', $params);

        $lines[] = "/**";
        $lines[] = sprintf(' * React hook for %s %s', $operation->method, $operation->path);
        $lines[] = " * Auto-fetches on mount and when dependencies change";
        $lines[] = " */";
        $lines[] = sprintf('export function %s(%s): QueryResult<Types.%sResponse> {', $hookName, $paramsStr, $opNamePascal);
        $lines[] = sprintf('  const [data, setData] = useState<Types.%sResponse | null>(null);', $opNamePascal);
        $lines[] = "  const [loading, setLoading] = useState(true);";
        $lines[] = "  const [error, setError] = useState<Error | null>(null);";
        $lines[] = "";
        $lines[] = "  const enabled = options?.enabled ?? true;";
        $lines[] = "";
        $lines[] = "  const fetchData = useCallback(async () => {";
        $lines[] = "    if (!enabled) return;";
        $lines[] = "    ";
        $lines[] = "    setLoading(true);";
        $lines[] = "    setError(null);";
        $lines[] = "    ";
        $lines[] = "    try {";

        // Build API call
        $callParams = [];
        if ($operation->pathParams !== []) {
            $callParams[] = "path";
        }

        if ($operation->queryParams !== []) {
            $callParams[] = "query";
        }

        $callParams[] = "options";
        $callParamsStr = implode(', ', $callParams);

        $lines[] = sprintf('      const result = await Api.%s(%s);', $opName, $callParamsStr);
        $lines[] = "      setData(result);";
        $lines[] = "      options?.onSuccess?.(result);";
        $lines[] = "    } catch (err) {";
        $lines[] = "      const error = err instanceof Error ? err : new Error(String(err));";
        $lines[] = "      setError(error);";
        $lines[] = "      options?.onError?.(error);";
        $lines[] = "    } finally {";
        $lines[] = "      setLoading(false);";
        $lines[] = "    }";
        // Build default dependencies
        $depArgs = [];
        if ($operation->pathParams !== []) {
            $depArgs[] = "path";
        }

        if ($operation->queryParams !== []) {
            $depArgs[] = "query";
        }

        $defaultDeps = $depArgs !== [] ? implode(', ', array_map(fn($arg): string => sprintf('JSON.stringify(%s)', $arg), $depArgs)) : '';
        $lines[] = $defaultDeps !== '' && $defaultDeps !== '0' ? sprintf('  }, [enabled, %s, ...(deps || [])]);', $defaultDeps) : "  }, [enabled, ...(deps || [])]);";

        $lines[] = "";
        $lines[] = "  useEffect(() => {";
        $lines[] = "    fetchData();";
        $lines[] = "  }, [fetchData]);";
        $lines[] = "";
        $lines[] = "  return { data, loading, error, refetch: fetchData };";
        $lines[] = "}";

        return $lines;
    }

    private function emitMutationHook(OperationIR $operation, string $hookName, string $opName, string $opNamePascal): array
    {
        $lines = [];

        // Determine variables type - include query for DELETE operations
        $variablesType = "{";
        $variablesParts = [];

        if ($operation->pathParams !== []) {
            $variablesParts[] = sprintf('path: Types.%sPathParams', $opNamePascal);
        }

        if ($operation->queryParams !== []) {
            $variablesParts[] = sprintf('query?: Types.%sQueryParams', $opNamePascal);
        }

        if ($operation->body) {
            $variablesParts[] = sprintf('body: Types.%sRequestBody', $opNamePascal);
        }

        $variablesType .= implode('; ', $variablesParts) . "}";

        // Check if variables are actually used (prefix with _ if unused)
        $hasVariables = $operation->pathParams !== [] || $operation->queryParams !== [] || $operation->body !== null;
        $variablesParam = $hasVariables ? 'variables' : '_variables';

        $lines[] = "/**";
        $lines[] = sprintf(' * React hook for %s %s', $operation->method, $operation->path);
        $lines[] = " * Returns a mutate function that must be called manually";
        $lines[] = " */";
        $lines[] = sprintf('export function %s(', $hookName);
        $lines[] = sprintf('  options?: QueryOptions<Types.%sResponse>', $opNamePascal);
        $lines[] = sprintf('): MutationResult<Types.%sResponse, %s> {', $opNamePascal, $variablesType);
        $lines[] = sprintf('  const [data, setData] = useState<Types.%sResponse | null>(null);', $opNamePascal);
        $lines[] = "  const [loading, setLoading] = useState(false);";
        $lines[] = "  const [error, setError] = useState<Error | null>(null);";
        $lines[] = "";
        $lines[] = sprintf('  const mutate = useCallback(async (%s: %s) => {', $variablesParam, $variablesType);
        $lines[] = "    setLoading(true);";
        $lines[] = "    setError(null);";
        $lines[] = "    ";
        $lines[] = "    try {";

        // Build API call - include query if present
        $callParams = [];
        if ($operation->pathParams !== []) {
            $callParams[] = $variablesParam . ".path";
        }

        if ($operation->queryParams !== []) {
            $callParams[] = $variablesParam . ".query";
        }

        if ($operation->body) {
            $callParams[] = $variablesParam . ".body";
        }

        $callParams[] = "options";
        $callParamsStr = implode(', ', $callParams);

        $lines[] = sprintf('      const result = await Api.%s(%s);', $opName, $callParamsStr);
        $lines[] = "      setData(result);";
        $lines[] = "      options?.onSuccess?.(result);";
        $lines[] = "      return result;";
        $lines[] = "    } catch (err) {";
        $lines[] = "      const error = err instanceof Error ? err : new Error(String(err));";
        $lines[] = "      setError(error);";
        $lines[] = "      options?.onError?.(error);";
        $lines[] = "      throw error;";
        $lines[] = "    } finally {";
        $lines[] = "      setLoading(false);";
        $lines[] = "    }";
        $lines[] = "  }, [options]);";
        $lines[] = "";
        $lines[] = "  const reset = useCallback(() => {";
        $lines[] = "    setData(null);";
        $lines[] = "    setError(null);";
        $lines[] = "  }, []);";
        $lines[] = "";
        $lines[] = "  return { data, loading, error, mutate, reset };";
        $lines[] = "}";

        return $lines;
    }

    private function toCamelCase(string $str): string
    {
        $pascal = $this->toPascalCase($str);
        return lcfirst($pascal);
    }

    private function toPascalCase(string $str): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $str)));
    }
}

