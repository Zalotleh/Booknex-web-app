<?php

namespace BookneticApp\Providers\WpShortcodes;

class WpShortcodeRegistry
{
    public static function register(string $shortcode, WpShortcode $class)
    {
        add_shortcode($shortcode, [$class, 'index']);
    }
}