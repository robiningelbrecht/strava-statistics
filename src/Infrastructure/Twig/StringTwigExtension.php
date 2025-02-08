<?php

namespace App\Infrastructure\Twig;

final readonly class StringTwigExtension
{
    public static function doEllipses(string $string, int $maxLength): string
    {
        if (strlen($string) <= $maxLength) {
            return $string;
        }

        return mb_substr($string, 0, $maxLength - 3, 'UTF-8').'...';
    }

    public static function doRepeat(string $char, int $times): string
    {
        return str_repeat($char, $times);
    }

    public static function doCountUpperCaseChars(string $string): int
    {
        /** @var string $stringWithoutUppercaseChars */
        $stringWithoutUppercaseChars = preg_replace('/[A-Z]/', '', $string);

        return strlen($string) - strlen($stringWithoutUppercaseChars);
    }
}
