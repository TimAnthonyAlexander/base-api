<?php

namespace BaseApi\Controllers;

use BaseApi\Http\JsonResponse;

class GetOnlyController extends Controller
{
    public function get(): JsonResponse
    {
        return JsonResponse::ok(['method' => 'GET']);
    }
    
    // No post() or delete() methods - should return 405
}
