<?php

namespace App\Http\Controllers\Api\Ditusi;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\Ditusi\DitusiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TransactionController extends Controller
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
     * Create a new transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'product_code' => 'required|string|exists:products,product_code',
            'amount' => 'required|integer|min:1',
            'additional_information' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get product
        $product = Product::where('product_code', $request->product_code)->first();
        if (!$product || !$product->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found or inactive',
            ], 404);
        }

        // Generate reference ID
        $referenceId = 'TOP-' . Str::upper(Str::random(10));

        // Create transaction in database
        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            'reference_id' => $referenceId,
            'product_code' => $product->product_code,
            'product_name' => $product->name,
            'amount' => $request->amount,
            'price' => $product->price * $request->amount,
            'status' => 'PENDING',
            'additional_information' => $request->additional_information,
        ]);

        // Create transaction in Ditusi
        $ditusiResponse = $this->ditusiService->createTransaction([
            'productCode' => $product->product_code,
            'amount' => $request->amount,
            'transactionReferenceId' => $referenceId,
            'initialPrice' => $product->price,
            'additionalInformation' => $request->additional_information,
        ]);

        if (!$ditusiResponse) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create transaction in Ditusi',
                'transaction_id' => $transaction->reference_id,
            ], 500);
        }

        // Update transaction in database
        $transaction->update([
            'transaction_id' => $ditusiResponse['transactionId'] ?? null,
            'status' => $ditusiResponse['statusTransaction'] ?? 'PENDING',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Transaction created successfully',
            'data' => [
                'transaction' => $transaction,
                'ditusi_response' => $ditusiResponse,
            ],
        ], 201);
    }

    /**
     * Check transaction status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Request $request, $id)
    {
        // Find transaction in database
        $transaction = Transaction::where('reference_id', $id)
            ->orWhere('transaction_id', $id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaction not found',
            ], 404);
        }

        // Check if user owns this transaction
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        // Check status in Ditusi
        if ($transaction->transaction_id) {
            $ditusiResponse = $this->ditusiService->checkTransaction($transaction->transaction_id);

            if ($ditusiResponse && isset($ditusiResponse['data']['transactionStatus'])) {
                // Update transaction status
                $transaction->update([
                    'status' => $ditusiResponse['data']['transactionStatus'],
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'transaction' => $transaction,
            ],
        ]);
    }
} 