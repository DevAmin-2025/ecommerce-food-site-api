<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\ApiController;
use App\Http\Resources\CategoryResource;

class CategoryController extends ApiController
{
    public function index(): JsonResponse
    {
        $categories = Category::all();
        return $this->successResponse(
            data: CategoryResource::collection($categories),
        );
    }
}
