<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'game_code',
        'product_amount',
        'user_information',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_information' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the products for the game.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
