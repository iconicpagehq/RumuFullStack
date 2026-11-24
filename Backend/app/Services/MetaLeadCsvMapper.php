<?php

namespace App\Services;

class MetaLeadCsvMapper
{
    private const NAME_ALIASES = ['full_name', 'name', 'full name'];
    private const EMAIL_ALIASES = ['email', 'email_address', 'email address'];
    private const PHONE_ALIASES = ['phone_number', 'phone', 'mobile', 'phone number'];
    private const COMPANY_ALIASES = ['company_name', 'company', 'company name'];
    private const META_ID_ALIASES = ['id', 'lead_id', 'lead id'];
    private const AD_NAME_ALIASES = ['ad_name', 'ad name'];
    private const CAMPAIGN_NAME_ALIASES = ['campaign_name', 'campaign name'];
    private const CREATED_TIME_ALIASES = ['created_time', 'created time', 'created'];

    /**
     * Map a CSV row using Meta-style headers.
     *
     * @return array{name: ?string, email: ?string, phone: ?string, company: ?string, meta_lead_id: ?string, custom_fields: array}
     */
    public function mapRow(array $row, array $header): array
    {
        $normalizedHeader = $this->normalizeHeader($header);
        $indexed = [];
        foreach ($normalizedHeader as $i => $col) {
            $indexed[$col] = trim($row[$i] ?? '');
        }

        $firstName = $this->valueForAliases($indexed, ['first_name', 'first name']);
        $lastName = $this->valueForAliases($indexed, ['last_name', 'last name']);
        $name = $this->valueForAliases($indexed, self::NAME_ALIASES);
        if (!$name && ($firstName || $lastName)) {
            $name = trim($firstName . ' ' . $lastName);
        }

        $customFields = [];
        $adName = $this->valueForAliases($indexed, self::AD_NAME_ALIASES);
        $campaignName = $this->valueForAliases($indexed, self::CAMPAIGN_NAME_ALIASES);
        $createdTime = $this->valueForAliases($indexed, self::CREATED_TIME_ALIASES);
        if ($adName) {
            $customFields['ad_name'] = $adName;
        }
        if ($campaignName) {
            $customFields['campaign_name'] = $campaignName;
        }
        if ($createdTime) {
            $customFields['created_time'] = $createdTime;
        }
        $customFields['lead_source'] = 'facebook';

        return [
            'name' => $name ?: null,
            'email' => $this->valueForAliases($indexed, self::EMAIL_ALIASES) ?: null,
            'phone' => $this->valueForAliases($indexed, self::PHONE_ALIASES) ?: null,
            'company' => $this->valueForAliases($indexed, self::COMPANY_ALIASES) ?: null,
            'meta_lead_id' => $this->valueForAliases($indexed, self::META_ID_ALIASES) ?: null,
            'custom_fields' => $customFields,
        ];
    }

    public function hasValidLeadData(array $data): bool
    {
        if (!empty($data['name'])) {
            return true;
        }

        return !empty($data['email']) || !empty($data['phone']);
    }

    public function resolveDisplayName(array $data): string
    {
        if (!empty($data['name'])) {
            return $data['name'];
        }
        if (!empty($data['email'])) {
            return $data['email'];
        }
        if (!empty($data['phone'])) {
            return $data['phone'];
        }

        return 'Meta Lead';
    }

    /**
     * @param array<int, string> $header
     * @return array<int, string>
     */
    private function normalizeHeader(array $header): array
    {
        return array_map(function ($col) {
            return strtolower(trim((string) $col));
        }, $header);
    }

    /**
     * @param array<string, string> $indexed
     * @param array<int, string> $aliases
     */
    private function valueForAliases(array $indexed, array $aliases): string
    {
        foreach ($aliases as $alias) {
            if (isset($indexed[$alias]) && $indexed[$alias] !== '') {
                return $indexed[$alias];
            }
        }

        return '';
    }
}
