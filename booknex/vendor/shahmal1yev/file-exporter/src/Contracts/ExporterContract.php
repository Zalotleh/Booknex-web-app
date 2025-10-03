<?php

namespace BookneticVendor\FileExporter\Contracts;

/**
 * Interface ExporterContract
 *
 * This interface defines the contract for an exporter, allowing setting headers,
 * including an index, and exporting data.
 */
interface ExporterContract
{
    /**
     * Set the headers for the export.
     *
     * @param array $headers An associative array of headers to be included in the export.
     * @return ExporterContract Returns the current instance of the exporter for method chaining.
     */
    public function withHeaders(array $headers): ExporterContract;

    /**
     * Include an index in the export.
     *
     * @return ExporterContract Returns the current instance of the exporter for method chaining.
     */
    public function withIndex(): ExporterContract;

    /**
     * Export the data.
     *
     * @return object An object containing the exported data.
     */
    public function export(): object;
}
