<?php

namespace App\Http\Controllers;

use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\SliderResource;
use App\Http\Controllers\ApiController;

class SliderController extends ApiController
{
    public function index(): JsonResponse
    {
        $sliders = Slider::all();
        return $this->successResponse(
            data: SliderResource::collection($sliders),
        );
    }
}
