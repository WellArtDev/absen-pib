<?php
declare(strict_types=1);

namespace App\Utils;

class CsvExport
{
    /**
     * Generate CSV from array of associative arrays.
     */
    public static function generate(array $rows, array $headers): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);

        foreach ($rows as $row) {
            $values = [];
            foreach ($headers as $key => $label) {
                $values[] = $row[$key] ?? '';
            }
            fputcsv($output, $values);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    public static function download(string $csv, string $filename): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF"; // BOM for Excel UTF-8
        echo $csv;
        exit;
    }
}
