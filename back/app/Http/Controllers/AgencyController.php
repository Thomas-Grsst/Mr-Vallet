<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use Illuminate\Http\JsonResponse;

class AgencyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Agency::query()->orderBy('id')->get());
    }
}
