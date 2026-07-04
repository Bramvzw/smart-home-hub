<?php

namespace Modules\Printer\Models;

use App\Services\Settings\SettingsStore;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $remaining_pct
 * @property-read bool $is_low
 */
class FilamentSpool extends Model
{
    protected $table = 'filament_spools';

    protected $fillable = [
        'material',
        'color_name',
        'color_hex',
        'brand',
        'diameter_mm',
        'total_weight_g',
        'remaining_g',
        'purchase_price',
        'purchase_store',
        'purchased_at',
        'notes',
    ];

    protected $casts = [
        'diameter_mm' => 'float',
        'total_weight_g' => 'integer',
        'remaining_g' => 'integer',
        'purchase_price' => 'float',
        'purchased_at' => 'date',
    ];

    protected function remainingPct(): Attribute
    {
        return Attribute::get(function (): int {
            $total = (int) $this->total_weight_g;

            if ($total <= 0) {
                return 0;
            }

            return (int) round((int) $this->remaining_g / $total * 100);
        });
    }

    protected function isLow(): Attribute
    {
        return Attribute::get(function (): bool {
            $threshold = (int) app(SettingsStore::class)
                ->get('printer.low_filament_pct', config('printer.low_filament_pct', 15));

            return $this->remaining_pct <= $threshold;
        });
    }
}
