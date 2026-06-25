<?php

namespace Modules\Deals\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricePoint extends Model
{
    protected $fillable = ['product_listing_id', 'price', 'observed_at'];

    protected $casts = [
        'price' => 'decimal:2',
        'observed_at' => 'immutable_datetime',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ProductListing::class, 'product_listing_id');
    }
}
