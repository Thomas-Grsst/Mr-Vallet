<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKeyAccountRequest;
use App\Models\KeyAccount;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KeyAccountController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(KeyAccount::query()->orderBy('name')->get(['id', 'name']));
    }

    public function store(StoreKeyAccountRequest $request): JsonResponse
    {
        abort_unless($request->user()->role->canManageKeyAccounts(), Response::HTTP_FORBIDDEN, 'Seules la Direction et la commerciale grands comptes peuvent modifier la liste des grands comptes.');

        $account = KeyAccount::query()->create(['name' => $request->string('name')->trim()->toString()]);

        return response()->json($account->only(['id', 'name']), Response::HTTP_CREATED);
    }

    public function destroy(Request $request, KeyAccount $keyAccount): Response
    {
        abort_unless($request->user()->role->canManageKeyAccounts(), Response::HTTP_FORBIDDEN, 'Seules la Direction et la commerciale grands comptes peuvent modifier la liste des grands comptes.');

        $keyAccount->delete();

        return response()->noContent();
    }

    public function clients(): JsonResponse
    {
        return response()->json(Reservation::query()->distinct()->orderBy('client')->pluck('client'));
    }
}
