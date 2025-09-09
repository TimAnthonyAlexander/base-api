<?php

namespace BaseApi\Controllers;

use BaseApi\Http\JsonResponse;

class HealthController
{
    public function get(): JsonResponse
    {
        return JsonResponse::ok(['ok' => true]);
    }
}
