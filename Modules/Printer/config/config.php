<?php

return [
    'name' => 'Printer',

    'default_spool_weight_g' => (int) env('PRINTER_DEFAULT_SPOOL_G', 1000),
    'low_filament_pct' => (int) env('PRINTER_LOW_PCT', 20), // informational badge only, no alerts
];
