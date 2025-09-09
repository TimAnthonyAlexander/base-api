<?php

namespace BaseApi\Controllers;

use BaseApi\Http\JsonResponse;

class HealthController
{
    public function get(): JsonResponse
    {
        return JsonResponse::ok(['ok' => true]);
    }

    public function post(): JsonResponse
    {
        return JsonResponse::ok(['ok' => true, 'received' => 'data']);
    }
}
