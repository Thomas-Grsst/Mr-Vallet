<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();

        if ($user === null || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => 'Identifiants incorrects']);
        }

        return response()->json([
            'token' => $user->createToken('web')->plainTextToken,
            'user' => $this->present($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->present($request->user()));
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /** @return array<string, mixed> */
    private function present(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
            'agency' => $user->agency?->name,
            'can_book' => $user->role->canBook(),
            'can_maintain' => $user->role->canMaintain(),
        ];
    }
}
