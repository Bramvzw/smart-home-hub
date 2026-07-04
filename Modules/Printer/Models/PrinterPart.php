<?php

namespace Modules\Printer\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class PrinterPart extends Model
{
    protected $table = 'printer_parts';

    protected $fillable = [
        'category',
        'name',
        'quantity',
        'unit',
        'low_threshold',
        'purchase_price',
        'purchase_store',
        'notes',
    ];

    protected function isLow(): Attribute
    {
        return Attribute::get(fn (): bool => $this->low_threshold !== null && (float) $this->quantity <= (int) $this->low_threshold);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'low_threshold' => 'integer',
            'purchase_price' => 'float',
        ];
    }
}
