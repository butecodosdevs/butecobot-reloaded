<?php

namespace App\Helpers;

class NumberMaskHelper
{
    public static function cooldown(string $number): string
    {
        return self::applyMask($number);
    }

    private static function applyMask(string $number): string
    {
        $number = str_replace(" ", "", $number);
        $masked = '';
        $numberIndex = 0;
        $numberLength = strlen($number);
        $mask = '###.###.###.';
        $maskLength = strlen($mask);

        while ($numberIndex < $numberLength) {
            for ($i = 0; $i < $maskLength; $i++) {
                if ($mask[$i] === '#') {
                    if (isset($number[$numberIndex])) {
                        $masked .= $number[$numberIndex++];
                    }
                } else {
                    $masked .= $mask[$i];
                }
                if ($numberIndex >= $numberLength) {
                    break;
                }
            }
        }

        return $masked;
    }
}
