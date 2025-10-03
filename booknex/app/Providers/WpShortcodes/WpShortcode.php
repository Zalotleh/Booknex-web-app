<?php

namespace BookneticApp\Providers\WpShortcodes;

use BookneticApp\Providers\Helpers\Helper;

abstract class WpShortcode
{
    const FRONT_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Frontend' . DIRECTORY_SEPARATOR;

    abstract public function index($attrs): string;

    public static function instance(): self
    {
        return new static();
    }

    protected function isPreview(): bool
    {
        $isBricksPreview = (isset($_SERVER['HTTP_X_BRICKS_IS_BUILDER']) && $_SERVER['HTTP_X_BRICKS_IS_BUILDER'] === '1') || isset ($_GET['bricks_preview']) || (function_exists('bricks_is_builder') && call_user_func('bricks_is_builder'));
        $isBookneticPreview = isset($_GET['booknetic_preview']);
        $isElementorPreview = isset($_GET['elementor-preview']) || (isset($_POST['action']) && $_POST['action'] === 'elementor_ajax');

        return $isBricksPreview || $isBookneticPreview || $isElementorPreview;
    }

    protected function view(string $path, $params)
    {
        return Helper::renderView(self::FRONT_DIR . 'view' . DIRECTORY_SEPARATOR . $path, $params);
    }
}