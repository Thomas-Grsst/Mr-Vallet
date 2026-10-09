<?php

namespace App\Http\Controllers;

use App\Models\KeyAccount;
use Illuminate\Http\JsonResponse;

class KeyAccountController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(KeyAccount::query()->orderBy('name')->pluck('name'));
    }
}
