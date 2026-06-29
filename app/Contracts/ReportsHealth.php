<?php

namespace App\Contracts;

use App\Support\Health\ModuleHealth;

interface ReportsHealth
{
    /**
     * Report whether this module is ready to use (config + local couplings).
     * Must not perform network calls — read config and the database only.
     */
    public function health(): ModuleHealth;
}
