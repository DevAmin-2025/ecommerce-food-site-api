<?php

namespace App\Http\Controllers;

use App\Models\Footer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\FooterResource;
use App\Http\Controllers\ApiController;

class FooterController extends ApiController
{
    public function index(): JsonResponse
    {
        $footer = Footer::firstOrFail();
        return $this->successResponse(
            data: new FooterResource($footer),
        );
    }
}
