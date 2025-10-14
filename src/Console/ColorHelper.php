<?php

namespace BaseApi\Console;

class ColorHelper
{
    public const RESET = "\033[0m";

    public const BLACK = "\033[0;30m";

    public const RED = "\033[0;31m";

    public const GREEN = "\033[0;32m";

    public const YELLOW = "\033[0;33m";

    public const BLUE = "\033[0;34m";

    public const MAGENTA = "\033[0;35m";

    public const CYAN = "\033[0;36m";

    public const WHITE = "\033[0;37m";

    public const BRIGHT_BLACK = "\033[1;30m";

    public const BRIGHT_RED = "\033[1;31m";

    public const BRIGHT_GREEN = "\033[1;32m";

    public const BRIGHT_YELLOW = "\033[1;33m";

    public const BRIGHT_BLUE = "\033[1;34m";

    public const BRIGHT_MAGENTA = "\033[1;35m";

    public const BRIGHT_CYAN = "\033[1;36m";

    public const BRIGHT_WHITE = "\033[1;37m";

    public const BG_BLACK = "\033[40m";

    public const BG_RED = "\033[41m";

    public const BG_GREEN = "\033[42m";

    public const BG_YELLOW = "\033[43m";

    public const BG_BLUE = "\033[44m";

    public const BG_MAGENTA = "\033[45m";

    public const BG_CYAN = "\033[46m";

    public const BG_WHITE = "\033[47m";

    public static function colorize(string $text, string $color): string
    {
        if (!self::supportsColor()) {
            return $text;
        }

        return $color . $text . self::RESET;
    }

    public static function success(string $text): string
    {
        return self::colorize($text, self::BRIGHT_GREEN);
    }

    public static function error(string $text): string
    {
        return self::colorize($text, self::BRIGHT_RED);
    }

    public static function warning(string $text): string
    {
        return self::colorize($text, self::BRIGHT_YELLOW);
    }

    public static function info(string $text): string
    {
        return self::colorize($text, self::BRIGHT_BLUE);
    }

    public static function comment(string $text): string
    {
        return self::colorize($text, self::BRIGHT_BLACK);
    }

    public static function header(string $text): string
    {
        return self::colorize($text, self::BRIGHT_CYAN);
    }

    private static function supportsColor(): bool
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM');
        }

        return function_exists('posix_isatty') && posix_isatty(STDOUT);
    }
}
