<?php

namespace App\Http\Controllers\Api\Ditusi;

use App\Http\Controllers\Controller;
use App\Services\Ditusi\DitusiService;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    protected DitusiService $ditusiService;

    /**
     * Create a new controller instance.
     */
    public function __construct(DitusiService $ditusiService)
    {
        $this->ditusiService = $ditusiService;
    }

    /**
     * Check deposit balance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(Request $request)
    {
        // Check balance in Ditusi
        $ditusiResponse = $this->ditusiService->checkBalance();

        if (!$ditusiResponse) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check balance in Ditusi',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'balance' => $ditusiResponse['data']['data']['DepositBalance'] ?? 0,
                'partner_type' => $ditusiResponse['data']['data']['partnerType'] ?? null,
                'raw_response' => $ditusiResponse,
            ],
        ]);
    }
} 