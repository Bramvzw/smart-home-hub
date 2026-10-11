<?php

namespace Modules\Lighting\Enums;

/**
 * Which LEDs a lamp is driving: the dedicated white (tunable CCT) channels or
 * the RGB channels. Values match Tuya's `work_mode` codes.
 */
enum LightMode: string
{
    case White = 'white';
    case Colour = 'colour';
}
