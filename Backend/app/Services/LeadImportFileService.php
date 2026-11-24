<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reads lead upload files in CSV, XLSX or legacy XLS format and produces
 * matching sample templates for the bulk import dialogs.
 */
class LeadImportFileService
{
    public const ACCEPTED_EXTENSIONS = ['csv', 'txt', 'xlsx', 'xls'];

    public const VALIDATION_RULE = 'mimes:csv,txt,xlsx,xls';

    /**
     * @return array{header: array<int, string>, rows: array<int, array{number:int, cells: array<int, mixed>}>, truncated: bool}
     */
    public function read(UploadedFile $file, int $maxRows = 10000): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->extension());

        return in_array($extension, ['xlsx', 'xls'], true)
            ? $this->readSpreadsheet($file, $extension, $maxRows)
            : $this->readCsv($file, $maxRows);
    }

    public function sampleDownload(array $rows, string $baseFilename, ?string $format = 'csv'): StreamedResponse
    {
        if (strtolower((string) $format) === 'xlsx') {
            return $this->xlsxDownload($rows, $baseFilename . '.xlsx');
        }

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $baseFilename . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function xlsxDownload(array $rows, string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Leads');
        $sheet->fromArray($rows, null, 'A1');
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);

        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer, $spreadsheet) {
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array{header: array<int, string>, rows: array<int, array{number:int, cells: array<int, mixed>}>, truncated: bool}
     */
    private function readCsv(UploadedFile $file, int $maxRows): array
    {
        $fileContent = file_get_contents($file->getPathname());
        $encoding = mb_detect_encoding($fileContent ?: '', ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
            file_put_contents($file->getPathname(), $fileContent);
        }

        $handle = fopen($file->getPathname(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'csv_file' => ['Could not read the uploaded file. Upload a CSV or Excel (.xlsx/.xls) file.'],
            ]);
        }

        $header = fgetcsv($handle);
        $rows = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $rows[] = ['number' => $rowNumber, 'cells' => $row];
        }

        fclose($handle);

        return $this->shape($header, $rows, $maxRows);
    }

    /**
     * @return array{header: array<int, string>, rows: array<int, array{number:int, cells: array<int, mixed>}>, truncated: bool}
     */
    private function readSpreadsheet(UploadedFile $file, string $extension, int $maxRows): array
    {
        try {
            $reader = IOFactory::createReader($extension === 'xls' ? 'Xls' : 'Xlsx');
            $reader->setReadDataOnly(true);
            $reader->setReadFilter($this->rowFilter($maxRows + 2));

            $spreadsheet = $reader->load($file->getPathname());
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'csv_file' => ['Could not read the uploaded Excel file. Use a valid .xlsx or .xls workbook.'],
            ]);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, false, false, false);
        $spreadsheet->disconnectWorksheets();

        $header = null;
        $rows = [];
        $rowNumber = 0;

        foreach ($data as $index => $cells) {
            $rowNumber = $index + 1;

            if ($this->isEmptyRow($cells)) {
                continue;
            }

            if ($header === null) {
                $header = $cells;
                continue;
            }

            $rows[] = ['number' => $rowNumber, 'cells' => $cells];
        }

        return $this->shape($header, $rows, $maxRows);
    }

    private function rowFilter(int $limit): IReadFilter
    {
        return new class($limit) implements IReadFilter {
            public function __construct(private int $limit) {}

            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row <= $this->limit;
            }
        };
    }

    /**
     * @param  array<int, mixed>|null  $header
     * @param  array<int, array{number:int, cells: array<int, mixed>}>  $rows
     * @return array{header: array<int, string>, rows: array<int, array{number:int, cells: array<int, mixed>}>, truncated: bool}
     */
    private function shape(?array $header, array $rows, int $maxRows): array
    {
        if ($header === null) {
            throw ValidationException::withMessages([
                'csv_file' => ['The uploaded file is empty. Add a header row and at least one lead.'],
            ]);
        }

        $truncated = count($rows) > $maxRows;
        if ($truncated) {
            $rows = array_slice($rows, 0, $maxRows);
        }

        return [
            'header' => array_map(fn ($cell) => trim((string) $cell), $header),
            'rows' => $rows,
            'truncated' => $truncated,
        ];
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
