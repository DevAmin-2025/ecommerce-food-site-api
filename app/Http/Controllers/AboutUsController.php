<?php

namespace App\Http\Controllers;

use App\Models\AboutUs;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\ApiController;
use App\Http\Resources\AboutUsResource;

class AboutUsController extends ApiController
{
    public function index(): JsonResponse
    {
        $aboutUs = AboutUs::firstOrFail();
        return $this->successResponse(
            data: new AboutUsResource($aboutUs),
        );
    }
}
