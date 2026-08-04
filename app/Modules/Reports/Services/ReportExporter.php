<?php

declare(strict_types=1);

namespace App\Modules\Reports\Services;

/**
 * CSV export is hand-rolled (no dependency needed for something this simple).
 * Excel (.xlsx) uses PhpSpreadsheet and PDF uses Dompdf — both declared in
 * composer.json; run `composer install` to pull them in before using
 * exportExcel()/exportPdf(). This keeps the CSV path dependency-free for
 * environments where composer hasn't been run yet.
 */
final class ReportExporter
{
    /** @param array $rows each row: transaction_date, type, category_name, description, amount, currency, created_by_name */
    public function streamCsv(array $rows, string $filename): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Type', 'Category', 'Description', 'Amount', 'Currency', 'Recorded By']);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row['transaction_date'],
                ucfirst($row['type']),
                $row['category_name'],
                $row['description'],
                $row['amount'],
                $row['currency'],
                $row['created_by_name'] ?? '',
            ]);
        }

        fclose($out);
        exit;
    }

    /** Requires composer package phpoffice/phpspreadsheet (see composer.json). */
    public function streamExcel(array $rows, string $filename): never
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            http_response_code(500);
            echo 'Excel export requires `composer install` to pull in phpoffice/phpspreadsheet.';
            exit;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Date', 'Type', 'Category', 'Description', 'Amount', 'Currency', 'Recorded By'], null, 'A1');

        $r = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row['transaction_date'], ucfirst($row['type']), $row['category_name'],
                $row['description'], $row['amount'], $row['currency'], $row['created_by_name'] ?? '',
            ], null, "A{$r}");
            $r++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    /** Requires composer package dompdf/dompdf (see composer.json). */
    public function streamPdf(string $html, string $filename): never
    {
        if (!class_exists(\Dompdf\Dompdf::class)) {
            http_response_code(500);
            echo 'PDF export requires `composer install` to pull in dompdf/dompdf.';
            exit;
        }

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("{$filename}.pdf", ['Attachment' => true]);
        exit;
    }
}
