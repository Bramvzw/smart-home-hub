<?php

namespace Modules\Lighting\Support;

/**
 * Small colour helpers to normalise between the shared hex model and the
 * per-provider representations (Govee RGB, Tuya HSV).
 */
final class Color
{
    /** @return array{0:int,1:int,2:int} */
    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    public static function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', self::clamp($r), self::clamp($g), self::clamp($b));
    }

    /**
     * @return array{h:int,s:int,v:int} h 0-360, s/v 0-1000 (Tuya colour_data_v2 scale)
     */
    public static function hexToTuyaHsv(string $hex): array
    {
        [$r, $g, $b] = array_map(static fn ($c) => $c / 255, self::hexToRgb($hex));
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        $h = 0.0;
        if ($delta > 0) {
            if ($max === $r) {
                $h = 60 * fmod((($g - $b) / $delta), 6);
            } elseif ($max === $g) {
                $h = 60 * ((($b - $r) / $delta) + 2);
            } else {
                $h = 60 * ((($r - $g) / $delta) + 4);
            }
        }
        if ($h < 0) {
            $h += 360;
        }

        $s = $max > 0 ? $delta / $max : 0.0;

        return [
            'h' => (int) round($h),
            's' => (int) round($s * 1000),
            'v' => (int) round($max * 1000),
        ];
    }

    /**
     * @param  int  $h  0-360
     * @param  int  $s  0-1000
     * @param  int  $v  0-1000
     */
    public static function tuyaHsvToHex(int $h, int $s, int $v): string
    {
        $sf = $s / 1000;
        $vf = $v / 1000;
        $c = $vf * $sf;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $vf - $c;

        [$r, $g, $b] = match (true) {
            $h < 60 => [$c, $x, 0],
            $h < 120 => [$x, $c, 0],
            $h < 180 => [0, $c, $x],
            $h < 240 => [0, $x, $c],
            $h < 300 => [$x, 0, $c],
            default => [$c, 0, $x],
        };

        return self::rgbToHex(
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        );
    }

    private static function clamp(int $c): int
    {
        return max(0, min(255, $c));
    }
}
