<?php

namespace BaseApi\Http;

class ControllerInvoker
{
    public function __construct()
    {
    }

    public function invoke(object $controller, Request $req): Response
    {
        // Determine which method to call based on HTTP method
        $method = match($req->method) {
            'GET' => 'get',
            'POST' => 'post',
            'DELETE' => 'delete',
            default => 'action'
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
                'requestId' => \BaseApi\Logger::getRequestId()
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
        
        if (method_exists($controller, 'action')) {
            // action() can handle any method not specifically implemented
            $missing = array_diff(['GET', 'POST', 'DELETE'], $methods);
            $methods = array_merge($methods, $missing);
        }
        
        // Always include OPTIONS
        $methods[] = 'OPTIONS';
        
        return array_unique($methods);
    }
}
