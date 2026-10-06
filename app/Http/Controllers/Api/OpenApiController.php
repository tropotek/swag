<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OpenApiController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $spec = json_decode(file_get_contents(resource_path('openapi.json')), true, flags: JSON_THROW_ON_ERROR);
        $spec['servers'] = [['url' => url('/api')]];

        return response()->json($spec);
    }
}
