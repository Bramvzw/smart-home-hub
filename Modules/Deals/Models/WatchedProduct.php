<?php

namespace Modules\Deals\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WatchedProduct extends Model
{
    protected $fillable = ['name', 'query', 'category', 'image_url', 'notes'];

    public function listings(): HasMany
    {
        return $this->hasMany(ProductListing::class, 'watched_product_id')->orderBy('retailer')->orderBy('title');
    }
}
