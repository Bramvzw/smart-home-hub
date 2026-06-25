<?php

namespace Modules\Recipes\Models;

use Illuminate\Database\Eloquent\Model;

class GroceryOffer extends Model
{
    protected $fillable = [
        'store',
        'external_id',
        'product_name',
        'category',
        'normal_price',
        'offer_price',
        'discount_label',
        'unit',
        'image_url',
        'valid_from',
        'valid_to',
        'week_key',
        'fetched_at',
    ];

    protected $casts = [
        'normal_price' => 'decimal:2',
        'offer_price' => 'decimal:2',
        'valid_from' => 'immutable_date',
        'valid_to' => 'immutable_date',
        'fetched_at' => 'immutable_datetime',
    ];
}
