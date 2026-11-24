<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Enterprise;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EnterpriseLeadCsvImportService
{
    /**
     * Stop collecting row errors after this many (prevents runaway responses).
     */
    private const MAX_ERRORS = 100;

    /**
     * Upper bound on rows processed per upload so one file cannot block a request.
     */
    private const MAX_ROWS = 1000;

    /**
     * Keep a handful of duplicate notices for the response.
     */
    private const MAX_WARNINGS = 50;

    private const EXPECTED_HEADERS = ['Name', 'Email', 'Phone', 'Company'];

    public function __construct(
        private EnterpriseLimitService $limits,
        private EnterpriseLeadService $leads,
        private EnterpriseAuditService $audit,
        private LeadImportFileService $files,
    ) {}

    /**
     * @return array{imported:int,skipped:int,failed:int,errors:string[],warnings:string[],stopped:bool,message:string}
     */
    public function import(
        Enterprise $enterprise,
        User $actor,
        EnterpriseMembership $membership,
        UploadedFile $file,
        ?int $teamId = null,
        ?int $campaignId = null,
    ): array {
        if ($membership->isEmployee()) {
            throw ValidationException::withMessages([
                'csv_file' => ['Only admins and managers can import leads.'],
            ]);
        }

        $team = $this->resolveTeam($enterprise, $membership, $teamId);
        $campaign = $this->resolveCampaign($enterprise, $campaignId);

        $sheet = $this->files->read($file, self::MAX_ROWS);
        $header = $sheet['header'];

        if (!$this->validateHeader($header)) {
            $found = $header ? implode(', ', $header) : 'none';

            return $this->blocked(
                'Invalid file header. Expected columns: ' . implode(', ', self::EXPECTED_HEADERS) . '. Found: ' . $found
            );
        }

        $rows = $sheet['rows'];
        $truncated = $sheet['truncated'];

        if ($rows === []) {
            return $this->blocked('The file does not contain any lead rows.');
        }

        $rows = array_map(fn ($entry) => [
            'number' => $entry['number'],
            'data' => $this->mapRowToData($entry['cells'], $header),
        ], $rows);

        $limit = $this->limits->limit($enterprise, 'monthly_leads');
        if ($limit !== null) {
            $used = $this->limits->usage($enterprise)['monthly_leads'];
            $remaining = max(0, $limit - $used);

            if (count($rows) > $remaining) {
                return $this->blocked(
                    'File has ' . count($rows) . ' rows but you can only add ' . $remaining .
                    ' more lead(s) this month. Upgrade your plan or reduce the file.'
                );
            }
        }

        [$existingEmails, $existingPhones] = $this->existingContacts($enterprise, $rows);

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $warnings = [];
        $stopped = $truncated;

        foreach ($rows as $entry) {
            $rowNo = $entry['number'];
            $data = $entry['data'];

            if (count($errors) >= self::MAX_ERRORS) {
                $stopped = true;
                break;
            }

            $name = trim((string) ($data['name'] ?? ''));
            $email = trim((string) ($data['email'] ?? ''));
            $phone = trim((string) ($data['phone'] ?? ''));
            $company = trim((string) ($data['company'] ?? ''));

            if ($name === '') {
                $errors[] = "Row {$rowNo}: Name is required.";
                continue;
            }

            if ($email === '' && $phone === '') {
                $errors[] = "Row {$rowNo}: Provide an email or phone number.";
                continue;
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNo}: Invalid email address.";
                continue;
            }

            if ($phone !== '' && !LeadCsvImportService::isValidPhone($phone)) {
                $errors[] = "Row {$rowNo}: Invalid phone number format.";
                continue;
            }

            $emailKey = strtolower($email);
            $phoneKey = $this->normalizePhone($phone);

            $isDuplicate = ($emailKey !== '' && isset($existingEmails[$emailKey]))
                || ($phoneKey !== '' && isset($existingPhones[$phoneKey]));

            if ($isDuplicate) {
                $skipped++;
                if (count($warnings) < self::MAX_WARNINGS) {
                    $warnings[] = "Row {$rowNo}: {$name} matches an existing lead — skipped.";
                }
                continue;
            }

            try {
                $lead = Lead::create([
                    'enterprise_id' => $enterprise->id,
                    'team_id' => $team?->id,
                    'campaign_id' => $campaign?->id,
                    'name' => $name,
                    'email' => $email !== '' ? $email : null,
                    'phone' => $phone !== '' ? $phone : null,
                    'company' => $company !== '' ? $company : null,
                    'status' => 'new',
                    'enterprise_stage' => 'new',
                    'priority' => 'medium',
                    'source' => 'csv',
                ]);

                $this->leads->activity($lead, $actor, 'created', 'Lead imported from CSV', null, [
                    'source' => 'csv',
                    'imported_team_id' => $team?->id,
                    'imported_campaign_id' => $campaign?->id,
                ]);

                if ($emailKey !== '') {
                    $existingEmails[$emailKey] = true;
                }
                if ($phoneKey !== '') {
                    $existingPhones[$phoneKey] = true;
                }

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNo}: " . $e->getMessage();
                Log::error('Enterprise CSV Import Error', [
                    'enterprise_id' => $enterprise->id,
                    'row' => $rowNo,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($truncated) {
            $errors[] = 'Only the first ' . self::MAX_ROWS . ' rows were processed. Split larger files into batches.';
        }

        $failed = count($errors);

        $this->audit->log($enterprise, $actor, 'leads.imported', null, 'Leads imported from CSV', [
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'team_id' => $team?->id,
            'campaign_id' => $campaign?->id,
            'file' => $file->getClientOriginalName(),
        ]);

        $message = "Imported {$imported} lead(s).";
        if ($skipped > 0) {
            $message .= " {$skipped} duplicate(s) skipped.";
        }
        if ($failed > 0) {
            $message .= " {$failed} row(s) failed.";
        }
        if ($stopped && count($errors) >= self::MAX_ERRORS) {
            $message .= ' (Stopped after ' . self::MAX_ERRORS . ' errors)';
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
            'warnings' => $warnings,
            'stopped' => $stopped,
            'message' => $message,
        ];
    }

    private function resolveTeam(Enterprise $enterprise, EnterpriseMembership $membership, ?int $teamId): ?Team
    {
        $managedTeamIds = $membership->isManager()
            ? collect($this->leads->managerTeamIds($membership))
            : collect();

        if ($membership->isManager() && !$teamId) {
            $teamId = $membership->primaryTeamMembership?->team_id;
        }

        if (!$teamId) {
            return null;
        }

        $team = Team::where('enterprise_id', $enterprise->id)->find($teamId);

        if (!$team) {
            throw ValidationException::withMessages([
                'team_id' => ['Select a team from this enterprise.'],
            ]);
        }

        if ($membership->isManager() && !$managedTeamIds->contains((int) $team->id)) {
            throw ValidationException::withMessages([
                'team_id' => ['You can only import leads into your own team.'],
            ]);
        }

        return $team;
    }

    private function resolveCampaign(Enterprise $enterprise, ?int $campaignId): ?Campaign
    {
        if (!$campaignId) {
            return null;
        }

        $campaign = Campaign::where('enterprise_id', $enterprise->id)->find($campaignId);

        if (!$campaign) {
            throw ValidationException::withMessages([
                'campaign_id' => ['Select a campaign from this enterprise.'],
            ]);
        }

        return $campaign;
    }

    /**
     * Preload contacts already stored for this enterprise so duplicates are
     * skipped instead of aborting the whole upload.
     *
     * @return array{0:array<string,bool>,1:array<string,bool>}
     */
    private function existingContacts(Enterprise $enterprise, array $rows): array
    {
        $emails = collect($rows)->pluck('data.email')->filter()->map(fn ($email) => strtolower(trim($email)))->unique()->values();
        $phones = collect($rows)->pluck('data.phone')->filter()->map(fn ($phone) => trim($phone))->unique()->values();

        $existingEmails = [];
        $existingPhones = [];

        if ($emails->isEmpty() && $phones->isEmpty()) {
            return [$existingEmails, $existingPhones];
        }

        Lead::where('enterprise_id', $enterprise->id)
            ->where(function ($query) use ($emails, $phones) {
                if ($emails->isNotEmpty()) {
                    $query->whereIn('email', $emails->all());
                }
                if ($phones->isNotEmpty()) {
                    $method = $emails->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('phone', $phones->all());
                }
            })
            ->get(['email', 'phone'])
            ->each(function (Lead $lead) use (&$existingEmails, &$existingPhones) {
                $emailKey = strtolower(trim((string) $lead->email));
                if ($emailKey !== '') {
                    $existingEmails[$emailKey] = true;
                }
                $phoneKey = $this->normalizePhone((string) $lead->phone);
                if ($phoneKey !== '') {
                    $existingPhones[$phoneKey] = true;
                }
            });

        return [$existingEmails, $existingPhones];
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    private function validateHeader(?array $header): bool
    {
        if (empty($header)) {
            return false;
        }

        $normalizedHeader = array_map(fn ($col) => strtolower(trim((string) $col)), $header);

        foreach (self::EXPECTED_HEADERS as $expected) {
            if (!in_array(strtolower($expected), $normalizedHeader, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{name:?string,email:?string,phone:?string,company:?string}
     */
    private function mapRowToData(array $row, array $header): array
    {
        $normalizedHeader = array_map(fn ($col) => strtolower(trim((string) $col)), $header);
        $value = function (string $key) use ($row, $normalizedHeader): ?string {
            $index = array_search($key, $normalizedHeader, true);

            return $index !== false ? trim((string) ($row[$index] ?? '')) : null;
        };

        return [
            'name' => $value('name'),
            'email' => $value('email'),
            'phone' => $value('phone'),
            'company' => $value('company'),
        ];
    }

    /**
     * @return array{imported:int,skipped:int,failed:int,errors:string[],warnings:string[],stopped:bool,message:string}
     */
    private function blocked(string $message): array
    {
        return [
            'imported' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [$message],
            'warnings' => [],
            'stopped' => true,
            'message' => 'Import failed.',
        ];
    }
}
