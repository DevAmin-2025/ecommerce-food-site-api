<?php

namespace App\Http\Controllers;

use App\Models\ContactUs;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Validator;

class ContactUsController extends ApiController
{
    public function store(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'full_name' => 'required|string',
            'email' => 'required|email',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);
        if ($validate->fails()) {
            return $this->errorResponse(
                message: $validate->errors(),
                code: 422,
            );
        };

        ContactUs::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'subject' => $request->subject,
            'body' => $request->body,
        ]);
        return $this->successResponse(
            data: null,
            message: 'Your message has been successfully submitted.',
            code: 201,
        );
    }
}
