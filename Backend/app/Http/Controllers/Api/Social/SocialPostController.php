<?php

namespace App\Http\Controllers\Api\Social;

use App\Models\Social\SocialPost;
use Illuminate\Http\Request;

class SocialPostController extends BaseSocialController
{
    /**
     * List social posts with filtering and pagination.
     * GET /api/v1/social/posts
     */
    public function index(Request $request)
    {
        $query = $this->scopeTenant(SocialPost::query(), $request)
            ->with(['page:id,name,username,platform'])
            ->withCount('comments');

        if ($request->has('platform') && in_array($request->query('platform'), ['facebook', 'instagram'])) {
            $query->where('platform', $request->query('platform'));
        }

        if ($request->has('social_page_id')) {
            $query->where('social_page_id', (int) $request->query('social_page_id'));
        }

        if ($request->has('search')) {
            $term = '%' . $request->query('search') . '%';
            $query->where('message', 'like', $term);
        }

        $posts = $query->orderBy('comments_count', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate($request->query('per_page', 50));

        return $this->paginated($posts, 'Posts retrieved successfully.');
    }

    /**
     * Get single post details with top comments.
     * GET /api/v1/social/posts/{id}
     */
    public function show(Request $request, int $id)
    {
        $post = $this->scopeTenant(SocialPost::query(), $request)
            ->with([
                'page:id,name,username,platform',
                'comments' => function ($q) {
                    $q->whereNull('parent_comment_id')->with('replies')->orderBy('created_time', 'desc')->limit(50);
                }
            ])
            ->findOrFail($id);

        return $this->success($post, 'Post details retrieved successfully.');
    }
}

