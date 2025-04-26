<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Product;
use App\Services\Ditusi\DitusiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportDitusiData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ditusi:import {--games : Import games} {--products : Import products} {--all : Import both games and products} {--debug : Show debug information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import games and products from Ditusi API to the database';

    /**
     * Execute the console command.
     */
    public function handle(DitusiService $ditusiService)
    {
        $importGames = $this->option('games') || $this->option('all');
        $importProducts = $this->option('products') || $this->option('all');
        $debug = $this->option('debug');

        if (!$importGames && !$importProducts) {
            $this->error('Please specify what to import: --games, --products, or --all');
            return 1;
        }

        // Import games
        if ($importGames) {
            $this->info('Importing games from Ditusi API...');
            
            $this->line('Calling Ditusi API to get games...');
            
            $gamesResponse = $ditusiService->getGames();
            
            if (!$gamesResponse) {
                $this->error('Failed to fetch games from Ditusi API. The API returned null.');
                return 1;
            }
            
            if ($debug) {
                $this->line('API Response: ' . json_encode($gamesResponse, JSON_PRETTY_PRINT));
            }
            
            // According to the API documentation, the response should have a 'data' key
            if (!isset($gamesResponse['data'])) {
                $this->error('Invalid response from Ditusi API. Missing data key.');
                $this->line('Response: ' . json_encode($gamesResponse));
                return 1;
            }
            
            $gamesData = $gamesResponse['data'];
            $count = 0;
            
            foreach ($gamesData as $gameData) {
                // Extract game information based on API documentation
                $gameCode = $gameData['gameCode'] ?? $gameData['code'] ?? null;
                $title = $gameData['title'] ?? 'Unknown Game';
                $productAmount = $gameData['productAmount'] ?? 0;
                $userInformation = [];
                
                // Process user information forms
                if (isset($gameData['userInformation'])) {
                    $userInformation = [
                        'forms' => []
                    ];
                    
                    foreach ($gameData['userInformation'] as $form) {
                        $formData = [
                            'name' => $form['name'] ?? '',
                            'type' => $form['type'] ?? 'text'
                        ];
                        
                        // Include options if available
                        if (isset($form['options'])) {
                            $formData['options'] = $form['options'];
                        }
                        
                        $userInformation['forms'][] = $formData;
                    }
                }
                
                if (!$gameCode) {
                    $this->warn('Skipping game with missing game code: ' . json_encode($gameData));
                    continue;
                }
                
                // Create or update game in database
                $game = Game::updateOrCreate(
                    ['game_code' => $gameCode],
                    [
                        'title' => $title,
                        'product_amount' => $productAmount,
                        'user_information' => $userInformation,
                        'is_active' => true,
                    ]
                );
                
                $count++;
                $this->line("Imported game: {$game->title} ({$game->game_code})");
            }
            
            $this->info("Successfully imported $count games.");
        }

        // Import products
        if ($importProducts) {
            $this->info('Importing products from Ditusi API...');
            
            // Get list of games first
            $games = Game::all();
            
            if ($games->isEmpty()) {
                $this->error('No games found in the database. Import games first.');
                return 1;
            }
            
            $totalCount = 0;
            
            foreach ($games as $game) {
                $this->line("Fetching products for game: {$game->title} ({$game->game_code})");
                
                $productsResponse = $ditusiService->getProducts($game->game_code);
                
                if (!$productsResponse) {
                    $this->warn("Failed to fetch products for game: {$game->game_code}");
                    continue;
                }
                
                if ($debug) {
                    $this->line('API Response: ' . json_encode($productsResponse, JSON_PRETTY_PRINT));
                }
                
                if (!isset($productsResponse['data'])) {
                    $this->warn("Invalid response format for products of game: {$game->game_code}");
                    continue;
                }
                
                $productsData = isset($productsResponse['data']['item']) ? $productsResponse['data']['item'] : $productsResponse['data'];
                $count = $this->processProducts($productsData, $game);
                
                $this->line("Imported $count products for {$game->title}");
                $totalCount += $count;
                
                // Update product count for the game
                $game->update(['product_amount' => $count]);
            }
            
            $this->info("Successfully imported $totalCount products.");
        }

        return 0;
    }
    
    /**
     * Process and import products data.
     *
     * @param  array  $productsData
     * @param  Game|null  $game
     * @return int
     */
    private function processProducts(array $productsData, ?Game $game = null): int
    {
        $count = 0;
        
        foreach ($productsData as $productData) {
            // Extract product information based on API documentation
            $productCode = $productData['productCode'] ?? $productData['code'] ?? null;
            $name = $productData['name'] ?? 'Unknown Product';
            $code = $productData['code'] ?? null;
            $description = $productData['description'] ?? null;
            $price = $productData['price'] ?? 0;
            $currency = $productData['currency'] ?? $productData['currencry'] ?? 'IDR';
            $ingameCurrency = $productData['ingame_currency'] ?? $productData['inGameCurrency'] ?? null;
            
            if (!$productCode) {
                $this->warn('Skipping product with missing product code: ' . json_encode($productData));
                continue;
            }
            
            // If we don't have a game, we need to find it
            if (!$game) {
                // Try to find game by productCode prefix or gameCode
                $gameCode = null;
                if (isset($productData['gameCode'])) {
                    $gameCode = $productData['gameCode'];
                } elseif (preg_match('/^([A-Z0-9]+)-/', $productCode, $matches)) {
                    $gameCode = $matches[1];
                }
                
                if ($gameCode) {
                    $game = Game::where('game_code', $gameCode)->first();
                    
                    if (!$game) {
                        $this->warn("Game not found for product: $productCode. Skipping.");
                        continue;
                    }
                } else {
                    $this->warn("Cannot determine game for product: $productCode. Skipping.");
                    continue;
                }
            }
            
            // Create or update product in database
            Product::updateOrCreate(
                ['product_code' => $productCode],
                [
                    'game_id' => $game->id,
                    'name' => $name,
                    'code' => $code,
                    'description' => $description,
                    'price' => $price,
                    'currency' => $currency,
                    'ingame_currency' => $ingameCurrency,
                    'is_active' => true,
                ]
            );
            
            $count++;
        }
        
        return $count;
    }
} 