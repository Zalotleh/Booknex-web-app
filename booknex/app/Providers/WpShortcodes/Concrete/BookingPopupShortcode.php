<?php

namespace BookneticApp\Providers\WpShortcodes\Concrete;

use BookneticApp\Providers\WpShortcodes\WpShortcode;

class BookingPopupShortcode extends WpShortcode
{
    public function index($attrs): string
    {
        return $this->view('popup/index.php', $attrs);
    }
}