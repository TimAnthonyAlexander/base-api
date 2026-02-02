<?php

namespace BaseApi\Http;

use BaseApi\Logger;

class ControllerInvoker
{
    public function invoke(object $controller, Request $req, ?string $customMethod = null): Response
    {
        // If a custom method is specified, use it directly
        if ($customMethod !== null) {
            if (!method_exists($controller, $customMethod)) {
                // Custom method doesn't exist - return 500 Internal Server Error
                return new Response(500, [
                    'Content-Type' => 'application/json; charset=utf-8'
                ], json_encode([
                    'error' => 'Controller method not found',
                    'requestId' => Logger::getRequestId()
                ]));
            }

            return $controller->$customMethod();
        }

        // Determine which method to call based on HTTP method
        $method = match ($req->method) {
            'GET' => 'get',
            'POST' => 'post',
            'DELETE' => 'delete',
            'PUT' => 'put',
            'PATCH' => 'patch',
            'HEAD' => 'head',
            default => 'action',
        };

        // Check if the method exists
        if (!method_exists($controller, $method)) {
            $method = 'action';
        }

        if (!method_exists($controller, $method)) {
            // Return 405 Method Not Allowed
            $allowedMethods = $this->getAllowedMethods($controller);

            return new Response(405, [
                'Allow' => implode(', ', $allowedMethods),
                'Content-Type' => 'application/json; charset=utf-8'
            ], json_encode([
                'error' => 'Method not allowed',
                'requestId' => Logger::getRequestId()
            ]));
        }

        return $controller->$method();
    }

    private function getAllowedMethods(object $controller): array
    {
        $methods = [];

        if (method_exists($controller, 'get')) {
            $methods[] = 'GET';
        }

        if (method_exists($controller, 'post')) {
            $methods[] = 'POST';
        }

        if (method_exists($controller, 'delete')) {
            $methods[] = 'DELETE';
        }

        if (method_exists($controller, 'put')) {
            $methods[] = 'PUT';
        }

        if (method_exists($controller, 'patch')) {
            $methods[] = 'PATCH';
        }

        if (method_exists($controller, 'head')) {
            $methods[] = 'HEAD';
        }

        if (method_exists($controller, 'action')) {
            // action() can handle any method not specifically implemented
            $missing = array_diff(['GET', 'POST', 'DELETE', 'PUT', 'PATCH', 'HEAD'], $methods);
            $methods = array_merge($methods, $missing);
        }

        // Always include OPTIONS
        $methods[] = 'OPTIONS';

        return array_unique($methods);
    }
}
