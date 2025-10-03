<?php

/**
 * Generate a random file name.
 *
 * @param string $extension The extension of the file.
 * @param string $prefix The prefix to prepend to the generated file name.
 * @return string The generated random file name.
 */
function randFileName(string $extension = '', string $prefix = ''): string
{
    return uniqid($prefix, true) . $extension;
}

/**
 * Get the temporary file path.
 *
 * @param string $fileName The name of the file.
 * @return string The temporary file path.
 */
function getTempFilePath(string $fileName): string
{
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
}
