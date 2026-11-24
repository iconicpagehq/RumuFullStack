<?php

namespace App\Services\Social\Meta;

use App\Models\Social\SocialInsight;
use App\Models\Social\SocialPage;
use Carbon\Carbon;

class MetaInsightsService
{
    protected FacebookService $facebookService;
    protected InstagramService $instagramService;

    public function __construct(FacebookService $facebookService, InstagramService $instagramService)
    {
        $this->facebookService = $facebookService;
        $this->instagramService = $instagramService;
    }

    /**
     * Fetch and persist Facebook Page insights.
     */
    public function syncFacebookInsights(SocialPage $page): array
    {
        $results = $this->facebookService->getPageInsights($page->external_id, $page->access_token);
        $synced = [];

        foreach ($results['data'] ?? [] as $metric) {
            $metricName = $metric['name'];
            $period = $metric['period'];

            foreach ($metric['values'] ?? [] as $valueEntry) {
                $date = isset($valueEntry['end_time']) 
                    ? Carbon::parse($valueEntry['end_time'])->toDateString() 
                    : Carbon::today()->toDateString();

                $synced[] = SocialInsight::updateOrCreate(
                    [
                        'social_page_id' => $page->id,
                        'metric_type' => $metricName,
                        'recorded_date' => $date,
                    ],
                    [
                        'company_id' => $page->company_id,
                        'user_id' => $page->user_id,
                        'platform' => 'facebook',
                        'period' => $period,
                        'metrics' => [
                            'value' => $valueEntry['value'],
                            'title' => $metric['title'] ?? null,
                            'description' => $metric['description'] ?? null,
                        ],
                    ]
                );
            }
        }

        return $synced;
    }

    /**
     * Fetch and persist Instagram account insights.
     */
    public function syncInstagramInsights(SocialPage $page): array
    {
        $results = $this->instagramService->getAccountInsights($page->external_id, $page->access_token);
        $synced = [];

        foreach ($results['data'] ?? [] as $metric) {
            $metricName = $metric['name'];
            $period = $metric['period'];

            foreach ($metric['values'] ?? [] as $valueEntry) {
                $date = isset($valueEntry['end_time']) 
                    ? Carbon::parse($valueEntry['end_time'])->toDateString() 
                    : Carbon::today()->toDateString();

                $synced[] = SocialInsight::updateOrCreate(
                    [
                        'social_page_id' => $page->id,
                        'metric_type' => $metricName,
                        'recorded_date' => $date,
                    ],
                    [
                        'company_id' => $page->company_id,
                        'user_id' => $page->user_id,
                        'platform' => 'instagram',
                        'period' => $period,
                        'metrics' => [
                            'value' => $valueEntry['value'],
                            'title' => $metric['title'] ?? null,
                            'description' => $metric['description'] ?? null,
                        ],
                    ]
                );
            }
        }

        return $synced;
    }
}

