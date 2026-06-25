<?php

namespace Modules\Deals\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductListing extends Model
{
    protected $fillable = [
        'watched_product_id',
        'retailer',
        'external_id',
        'title',
        'url',
        'current_price',
        'lowest_price',
        'confirmed',
        'active',
        'last_checked_at',
    ];

    protected $casts = [
        'current_price' => 'decimal:2',
        'lowest_price' => 'decimal:2',
        'confirmed' => 'boolean',
        'active' => 'boolean',
        'last_checked_at' => 'immutable_datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(WatchedProduct::class, 'watched_product_id');
    }

    public function pricePoints(): HasMany
    {
        return $this->hasMany(PricePoint::class, 'product_listing_id')->orderBy('observed_at');
    }
}
