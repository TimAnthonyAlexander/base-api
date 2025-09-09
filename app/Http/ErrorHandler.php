<?php

namespace BaseApi\Http;

use BaseApi\App;

class ErrorHandler implements Middleware
{
    public function handle(Request $req, callable $next): Response
    {
        try {
            return $next($req);
        } catch (\Throwable $e) {
            $config = App::config();
            $logger = App::logger();
            
            $logger->error('Uncaught exception: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $message = 'Server Error';
            $data = [
                'error' => $message,
                'requestId' => $req->requestId
            ];

            if ($config->bool('APP_DEBUG')) {
                $data['debug'] = [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ];
            }

            return new JsonResponse($data, 500);
        }
    }
}
