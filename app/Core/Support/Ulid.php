<?php

declare(strict_types=1);

namespace Premisely\Core\Support;

/**
 * Crockford Base32 ULID generator (26 chars).
 */
final class Ulid
{
    private const ENCODING = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public static function generate(): string
    {
        $time = (int) floor(microtime(true) * 1000);
        $timeChars = '';
        for ($i = 9; $i >= 0; $i--) {
            $mod = $time % 32;
            $timeChars = self::ENCODING[$mod] . $timeChars;
            $time = intdiv($time, 32);
        }

        $rand = '';
        for ($i = 0; $i < 16; $i++) {
            $rand .= self::ENCODING[random_int(0, 31)];
        }

        return $timeChars . $rand;
    }
}
