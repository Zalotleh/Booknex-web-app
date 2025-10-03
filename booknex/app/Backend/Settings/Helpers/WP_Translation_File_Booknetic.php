<?php

namespace BookneticApp\Backend\Settings\Helpers;

class WP_Translation_File_Booknetic extends \WP_Translation_File
{
    public static function cloneFrom( $source ) {
        $obj = new WP_Translation_File_Booknetic('');
        $obj->import($source);
        return $obj;
    }

    public function setEntry($key, $value)
    {
        $this->entries[$key] = $value;
    }

    protected function parse_file() {}
    public function export() {}

    public function saveToDisk($path)
    {
        $moPath =  $path . '.mo';
        $phpPath = $path . '.l10n.php';

        $moVersion = new \WP_Translation_File_MO( $path . '.mo' );
        $phpVersion = new \WP_Translation_File_PHP( $path . '.l10n.php' );

        $moVersion->import( $this );
        $phpVersion->import( $this );

        file_put_contents($moPath, $moVersion->export());
        file_put_contents($phpPath, $phpVersion->export());
    }
}