<?php

namespace BaseApi\Http;

use Override;
use Throwable;
use BaseApi\App;

class ErrorHandler implements Middleware
{
    #[Override]
    public function handle(Request $req, callable $next): Response
    {
        try {
            return $next($req);
        } catch (Throwable $throwable) {
            $config = App::config();
            $logger = App::logger();
            
            $logger->error('Uncaught exception: ' . $throwable->getMessage(), [
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'trace' => $throwable->getTraceAsString()
            ]);

            $message = 'Server Error';
            $data = [
                'error' => $message,
                'requestId' => $req->requestId
            ];

            if ($config->bool('APP_DEBUG')) {
                $data['debug'] = [
                    'message' => $throwable->getMessage(),
                    'file' => $throwable->getFile(),
                    'line' => $throwable->getLine(),
                    'trace' => explode("\n", $throwable->getTraceAsString())
                ];
            }

            $response = new JsonResponse($data, 500);
            
            // Add CORS headers if Origin is present
            $origin = $req->headers['ORIGIN'] ?? $req->headers['Origin'] ?? null;
            if ($origin) {
                $config = App::config();
                $allowlist = $config->list('CORS_ALLOWLIST');
                $isAllowed = in_array($origin, $allowlist);

                if ($isAllowed) {
                    $response = $response
                        ->withHeader('Access-Control-Allow-Origin', $origin)
                        ->withHeader('Access-Control-Allow-Credentials', 'true')
                        ->withHeader('Access-Control-Expose-Headers', 'X-Request-Id, ETag');
                }

                $response = $response->withHeader('Vary', 'Origin');
            }
            
            return $response;
        }
    }
}
