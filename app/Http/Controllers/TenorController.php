<?php

namespace App\Http\Controllers;

use App\Services\TenorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenorController extends Controller
{
    public function __construct(private TenorService $tenor) {}

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q'   => ['required', 'string', 'max:100'],
            'pos' => ['nullable', 'string', 'max:50'],
        ]);

        $result = $this->tenor->search($data['q'], 20, $data['pos'] ?? '');

        return response()->json($result);
    }
}
