<?php

namespace App\Http\Controllers\Api\Social;

use App\Models\Social\SocialMention;
use Illuminate\Http\Request;

class SocialMentionController extends BaseSocialController
{
    /**
     * List mentions and tags across Facebook and Instagram.
     * GET /api/v1/social/mentions
     */
    public function index(Request $request)
    {
        $query = $this->scopeTenant(SocialMention::query(), $request)
            ->with(['page:id,name,username,platform'])
            ->orderBy('mentioned_at', 'desc');

        if ($request->has('platform') && in_array($request->query('platform'), ['facebook', 'instagram'])) {
            $query->where('platform', $request->query('platform'));
        }

        if ($request->has('mention_type')) {
            $query->where('mention_type', $request->query('mention_type'));
        }

        $mentions = $query->paginate($request->query('per_page', 20));

        return $this->paginated($mentions, 'Mentions retrieved successfully.');
    }
}

