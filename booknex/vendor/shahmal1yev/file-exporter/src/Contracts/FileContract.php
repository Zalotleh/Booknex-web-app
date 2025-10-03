<?php

namespace BookneticVendor\FileExporter\Contracts;

/**
 * Interface FileContract
 *
 * This interface defines the contract for a file handler which provides various file-related operations.
 */
interface FileContract
{
    /**
     * Get the MIME type of the file.
     *
     * @return string The MIME type of the file.
     */
    public function getMimeType(): string;

    /**
     * Get the file extension.
     *
     * @return string The file extension.
     */
    public function getExtension(): string;

    /**
     * Get the file name.
     *
     * @return string The file name.
     */
    public function getFileName(): string;

    /**
     * Get the file full name.
     *
     * @return string The file full name.
     */
    public function getFileFullName(): string;

    /**
     * Get the full path of the file.
     *
     * @return string The full path of the file.
     */
    public function getPath(): string;

    /**
     * Get the base path of the file.
     *
     * @return string The base path of the file.
     */
    public function getBasePath(): string;

    /**
     * Get the size of the file in bytes.
     *
     * @return int The size of the file in bytes.
     */
    public function getFileSize(): int;

    /**
     * Check if the file exists.
     *
     * @return bool True if the file exists, false otherwise.
     */
    public function exists(): bool;

    /**
     * Check if the path is a file.
     *
     * @return bool True if the path is a file, false otherwise.
     */
    public function isFile(): bool;

    /**
     * Check if the path is a directory.
     *
     * @return bool True if the path is a directory, false otherwise.
     */
    public function isDirectory(): bool;

    /**
     * Get the creation time of the file.
     *
     * @return mixed The creation time of the file.
     */
    public function getCreationTime();

    /**
     * Get the last modification time of the file.
     *
     * @return mixed The last modification time of the file.
     */
    public function getModificationTime();

    /**
     * Get the content of the file.
     *
     * @return string The content of the file.
     */
    public function getContent(): string;
}
