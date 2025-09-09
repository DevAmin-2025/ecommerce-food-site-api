<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Validator;

class AuthController extends ApiController
{
    public function register(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
        ]);
        if ($validate->fails()) {
            return $this->errorResponse(
                message: $validate->errors(),
                code: 422,
            );
        };

        User::create($validate->validated());
        return $this->successResponse(
            data: null,
            message: 'You have successfully registered. Please log in.',
        );
    }

    public function login(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6',
        ]);
        if ($validate->fails()) {
            return $this->errorResponse(
                message: $validate->errors(),
                code: 422,
            );
        };

        $user = User::where('email', $request->email)->firstOrFail();
        if (!Hash::check($request->password, $user->password)) {
            return $this->errorResponse(
                message: "Invalid password",
                code: 401,
            );
        };

        $token = $user->createToken('auth_token')->plainTextToken;
        return $this->successResponse(
            data: [
                'token' => $token,
                'type' => 'Bearer',
                'user' => new UserResource($user),
            ],
            message: 'You have successfully logged in.',
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return $this->successResponse(
            data: null,
            message: 'You have successfully logged out.',
        );
    }

    public function forgetPassword(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validate->fails()) {
            return $this->errorResponse(
                message: $validate->errors(),
                code: 422,
            );
        };

        $userChangePasswordRequest = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();
        if ($userChangePasswordRequest) {
            return $this->errorResponse(
                message: 'You have already made a request to change your password and have received a token.',
                code: 401,
            );
        };

        $token = str()->random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now(),
        ]);
        return $this->successResponse(
            data: $token,
            message: 'Password reset token generated successfully.',
        );
    }

    public function resetPassword(Request $request): JsonResponse
    {
       $validate = Validator::make($request->all(), [
            'new_password' => 'required|min:6',
            'token' => 'required|string',
        ]);
        if ($validate->fails()) {
            return $this->errorResponse(
                message: $validate->errors(),
                code: 422,
            );
        };

        $userRecord = DB::table('password_reset_tokens')->where('token', $request->token)->first();
        if (!$userRecord) {
            return $this->errorResponse(
                message: "Invalid data",
                code: 401,
            );
        };

        $user = User::where('email', $userRecord->email)->first();
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        return $this->successResponse(
            data: null,
            message: 'Password has successfully been updated.',
        );
    }
}
