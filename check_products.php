<?php

// Load Laravel environment
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Use Eloquent models
use App\Models\Game;
use App\Models\Product;

echo "==== Product Sync Status ====\n\n";

// Count total products
$totalProducts = Product::count();
echo "Total Products: {$totalProducts}\n\n";

// Count products by game
$games = Game::with(['products' => function($query) {
    $query->where('is_active', true);
}])->get();

echo "Products by Game:\n";
echo "=================\n";

foreach ($games as $game) {
    $productCount = $game->products->count();
    echo "{$game->title} ({$game->game_code}): {$productCount} products\n";
}

echo "\n==== End of Report ====\n"; 