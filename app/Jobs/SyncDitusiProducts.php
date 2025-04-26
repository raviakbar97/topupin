<?php

namespace App\Jobs;

use App\Models\Game;
use App\Models\Product;
use App\Services\Ditusi\DitusiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncDitusiProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(DitusiService $ditusiService): void
    {
        try {
            // Step 1: Sync Games
            $this->syncGames($ditusiService);
            
            // Step 2: Sync Products
            $this->syncProducts($ditusiService);
            
            Log::info('Ditusi products sync completed successfully');
        } catch (\Exception $e) {
            Log::error('Ditusi products sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
    
    /**
     * Sync games from Ditusi API.
     */
    protected function syncGames(DitusiService $ditusiService): void
    {
        // Get games from API
        $response = $ditusiService->getGames();
        
        if (!$response || empty($response['data'])) {
            Log::warning('No games found in Ditusi API response');
            return;
        }
        
        foreach ($response['data'] as $gameData) {
            // Find or create game in database
            $game = Game::updateOrCreate(
                ['game_code' => $gameData['gameCode']],
                [
                    'title' => $gameData['title'],
                    'product_amount' => $gameData['productAmount'],
                    'user_information' => $gameData['userInformation'] ?? null,
                    'is_active' => true,
                ]
            );
            
            Log::info("Sync game: {$game->title}");
        }
    }
    
    /**
     * Sync products from Ditusi API.
     */
    protected function syncProducts(DitusiService $ditusiService): void
    {
        // Get all active games
        $games = Game::where('is_active', true)->get();
        
        foreach ($games as $game) {
            // Get products for this game
            $response = $ditusiService->getProducts($game->game_code);
            
            if (!$response || empty($response['data'])) {
                Log::warning("No products found for game: {$game->title}");
                continue;
            }
            
            foreach ($response['data'] as $productData) {
                // Find or create product in database
                $product = Product::updateOrCreate(
                    ['product_code' => $productData['productCode']],
                    [
                        'game_id' => $game->id,
                        'name' => $productData['name'],
                        'code' => $productData['code'] ?? null,
                        'description' => $productData['description'] ?? null,
                        'price' => $productData['price'],
                        'currency' => $productData['currency'] ?? 'IDR',
                        'ingame_currency' => $productData['ingame_currency'] ?? null,
                        'is_active' => true,
                    ]
                );
                
                Log::info("Sync product: {$product->name} for game {$game->title}");
            }
        }
    }
}
