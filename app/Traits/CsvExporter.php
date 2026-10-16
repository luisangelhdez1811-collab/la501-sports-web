<?php

namespace App\Traits;

trait CsvExporter
{
    /**
     * Define los headers estándar para la descarga de archivos CSV.
     */
    protected function getCsvHeaders($fileName)
    {
        return [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];
    }

    /**
     * Inserta el Byte Order Mark (BOM) para que Excel reconozca caracteres especiales (tildes, Ñ).
     */
    protected function insertBom($file)
    {
        fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
    }
}
