<?php

namespace App\Http\Controllers\Api\Ditusi;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Product;
use App\Services\Ditusi\DitusiService;
use Illuminate\Http\Request;

class ProductController extends Controller
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
     * Get list of games.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function games(Request $request)
    {
        // Get games from database
        $games = Game::where('is_active', true)
            ->with(['products' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $games,
        ]);
    }

    /**
     * Get list of products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function products(Request $request)
    {
        $gameCode = $request->input('game_code');
        $query = Product::where('is_active', true)->with('game');

        // If game_code is provided, filter by game
        if ($gameCode) {
            $game = Game::where('game_code', $gameCode)->first();
            if (!$game) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Game not found',
                ], 404);
            }
            $query->where('game_id', $game->id);
        }

        $products = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }
} 