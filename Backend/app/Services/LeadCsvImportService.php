<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class LeadCsvImportService
{
    /**
     * Maximum number of errors to store before stopping (prevents memory leak).
     */
    private const MAX_ERRORS = 100;

    /**
     * Upper bound on rows per upload so a single file cannot exhaust memory.
     */
    private const MAX_ROWS = 5000;

    /**
     * Expected headers (case-insensitive). CSV, XLSX and XLS files all use them.
     */
    private const EXPECTED_HEADERS = ['Name', 'Email', 'Phone', 'Company'];

    public function __construct(private LeadImportFileService $files) {}

    /**
     * @return array{imported:int,failed:int,errors:string[],stopped:bool,message:string}
     */
    public function import(Campaign $campaign, User $user, UploadedFile $file): array
    {
        $sheet = $this->files->read($file, self::MAX_ROWS);
        $header = $sheet['header'];

        if (!$this->validateHeader($header)) {
            $found = $header ? implode(', ', $header) : 'none';

            return $this->blocked(
                'Invalid file header. Expected columns: ' . implode(', ', self::EXPECTED_HEADERS) . '. Found: ' . $found
            );
        }

        if ($sheet['truncated']) {
            return $this->blocked(
                'The file contains more than ' . self::MAX_ROWS . ' leads. Split it into smaller files and import again.'
            );
        }

        $rows = $sheet['rows'];

        if ($rows === []) {
            return $this->blocked('The file does not contain any lead rows.');
        }

        $rowCount = count($rows);
        $currentLeadCount = $campaign->leads()->count();
        $maxLeads = $user->plan?->max_leads_per_campaign;

        if ($maxLeads !== null && ($currentLeadCount + $rowCount) > $maxLeads) {
            $canAdd = max(0, $maxLeads - $currentLeadCount);

            return $this->blocked(
                "File has {$rowCount} rows but you can only add {$canAdd} more leads to this campaign. Please upgrade your plan or reduce the file size."
            );
        }

        $imported = 0;
        $errors = [];
        $stopped = false;

        foreach ($rows as $entry) {
            $rowNumber = $entry['number'];

            if (count($errors) >= self::MAX_ERRORS) {
                $stopped = true;
                break;
            }

            $data = $this->mapRowToData($entry['cells'], $header);

            if (empty($data['name'])) {
                $errors[] = "Row {$rowNumber}: Name is required";
                continue;
            }

            if (!empty($data['phone']) && !self::isValidPhone($data['phone'])) {
                $errors[] = "Row {$rowNumber}: Invalid phone number format";
                continue;
            }

            if (!$user->canAddLeadToCampaign($campaign)) {
                $errors[] = "Plan limit reached at row {$rowNumber}. Upgrade plan to add more leads.";
                $stopped = true;
                break;
            }

            try {
                $campaign->leads()->create([
                    'name' => $data['name'],
                    'email' => $data['email'] ?: null,
                    'phone' => $data['phone'] ?: null,
                    'company' => $data['company'] ?: null,
                    'status' => 'new',
                    'source' => 'csv',
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                Log::error('Lead Import Error', ['row' => $rowNumber, 'error' => $e->getMessage()]);
            }
        }

        $failed = count($errors);
        $message = "Imported {$imported} lead(s).";
        if ($failed > 0) {
            $message .= " {$failed} row(s) failed.";
        }
        if ($stopped && $failed >= self::MAX_ERRORS) {
            $message .= ' (Stopped after ' . self::MAX_ERRORS . ' errors)';
        }

        return [
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
            'stopped' => $stopped,
            'message' => $message,
        ];
    }

    private function validateHeader(?array $header): bool
    {
        if (empty($header)) {
            return false;
        }

        $normalizedHeader = array_map(fn ($col) => strtolower(trim((string) $col)), $header);
        $normalizedExpected = array_map('strtolower', self::EXPECTED_HEADERS);

        foreach ($normalizedExpected as $expected) {
            if (!in_array($expected, $normalizedHeader, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array{name:?string,email:?string,phone:?string,company:?string}
     */
    private function mapRowToData(array $row, array $header): array
    {
        $normalizedHeader = array_map(fn ($col) => strtolower(trim((string) $col)), $header);

        $idx = fn (string $key) => array_search($key, $normalizedHeader, true);

        $nameIndex = $idx('name');
        $emailIndex = $idx('email');
        $phoneIndex = $idx('phone');
        $companyIndex = $idx('company');

        return [
            'name' => $nameIndex !== false ? trim((string) ($row[$nameIndex] ?? '')) : null,
            'email' => $emailIndex !== false ? trim((string) ($row[$emailIndex] ?? '')) : null,
            'phone' => $phoneIndex !== false ? trim((string) ($row[$phoneIndex] ?? '')) : null,
            'company' => $companyIndex !== false ? trim((string) ($row[$companyIndex] ?? '')) : null,
        ];
    }

    public static function isValidPhone(string $phone): bool
    {
        $pattern = '/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/';
        return preg_match($pattern, $phone) === 1;
    }

    /**
     * @return array{imported:int,failed:int,errors:string[],stopped:bool,message:string}
     */
    private function blocked(string $message): array
    {
        return [
            'imported' => 0,
            'failed' => 0,
            'errors' => [$message],
            'stopped' => true,
            'message' => 'Import failed.',
        ];
    }
}
