<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\ApiController;
use App\Http\Resources\FeatureResource;

class FeatureController extends ApiController
{
    public function index(): JsonResponse
    {
        $features = Feature::all();
        return $this->successResponse(
            data: FeatureResource::collection($features),
        );
    }
}
