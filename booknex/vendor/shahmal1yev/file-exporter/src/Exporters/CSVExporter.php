<?php

namespace BookneticVendor\FileExporter\Exporters;

use BookneticVendor\FileExporter\Contracts\ExporterContract;
use BookneticVendor\FileExporter\Contracts\FileContract;
use BookneticVendor\FileExporter\Exceptions\Builders\CSVExporterLogicException;
use BookneticVendor\FileExporter\Helpers\File;

/**
 * Class CSVExporter
 *
 * This class implements the ExporterContract interface to export data to CSV format.
 */
class CSVExporter implements ExporterContract
{
    /** @var object The data to be exported. */
    protected object $data;

    /**
     * CSVExporter constructor.
     *
     * @param array $data The data to be exported.
     */
    public function __construct(array $data)
    {
        $this->data = (object) [];
        $this->data->content = $data;
        $this->data->headers = [];
    }

    /**
     * Set the headers for the export.
     *
     * @param array $headers An associative array of headers to be included in the export.
     * @return ExporterContract The current instance of the exporter, for method chaining.
     */
    public function withHeaders(array $headers): ExporterContract
    {
        $this->data->headers = $headers;

        return $this;
    }

    /**
     * Include an index in the export.
     *
     * @return ExporterContract The current instance of the exporter, for method chaining.
     * @throws CSVExporterLogicException If the headers are not set.
     */
    public function withIndex(): ExporterContract
    {
        if (empty($this->data->headers))
            throw new CSVExporterLogicException("You must set the headers to use this functionality with the exporter.");

        array_unshift(
            $this->data->headers,
            "#"
        );

        foreach($this->data->content as $index => $dataItem)
            array_unshift(
                $this->data->content[$index],
                $index + 1
            );

        return $this;
    }

    /**
     * Export the data to a CSV file.
     *
     * @return FileContract A FileContract object representing the exported CSV file.
     */
    public function export(): FileContract
    {
        array_unshift($this->data->content, $this->data->headers);

        $fileName = randFileName(".csv", "booknetic-");
        $filePath = getTempFilePath($fileName);
        $file = fopen($filePath, 'w');

        foreach($this->data->content as $row)
            if (! empty(array_filter($row)))
                fputcsv($file, $row);

        fclose($file);
        return new File($filePath);
    }
}
