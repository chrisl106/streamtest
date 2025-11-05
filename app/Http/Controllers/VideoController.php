<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Category;
use App\Models\VideoView;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VideoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'require.auth', 'tenant.scope']);
    }

    /**
     * Display the home page with videos
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get user's preferred categories
        $preferredCategories = $user->category_prefs ?? [];

        // Get categories with videos
        $categories = Category::ordered()
            ->with(['videos' => function ($query) {
                $query->orderBy('created_at', 'desc')
                      ->limit(12)
                      ->select(['id', 'title', 'thumbnail', 'category_id', 'duration', 'views_count']);
            }])
            ->get();

        // Get continue watching videos
        $continueWatching = $this->getContinueWatchingVideos($user);

        // Get featured/latest videos
        $featuredVideos = Video::with('category')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        return Inertia::render('Home', [
            'categories' => $categories,
            'continueWatching' => $continueWatching,
            'featuredVideos' => $featuredVideos,
        ]);
    }

    /**
     * Display the specified video
     */
    public function show(Video $video)
    {
        $user = Auth::user();

        // Record view
        $this->recordVideoView($video, $user);

        // Get related videos
        $relatedVideos = Video::where('category_id', $video->category_id)
            ->where('id', '!=', $video->id)
            ->orderBy('views_count', 'desc')
            ->limit(8)
            ->get();

        // Get series videos if this video is part of a series
        $seriesVideos = null;
        if ($video->series_id) {
            $seriesVideos = Video::where('series_id', $video->series_id)
                ->orderBy('created_at', 'asc')
                ->get(['id', 'title', 'thumbnail', 'duration']);
        }

        return Inertia::render('Video/Show', [
            'video' => $video->load('category', 'series'),
            'relatedVideos' => $relatedVideos,
            'seriesVideos' => $seriesVideos,
        ]);
    }

    /**
     * Get videos by category
     */
    public function category(Category $category, Request $request)
    {
        $videos = $category->videos()
            ->paginate(24);

        return Inertia::render('Category/Show', [
            'category' => $category,
            'videos' => $videos,
        ]);
    }

    /**
     * Get videos by series
     */
    public function series(Series $series, Request $request)
    {
        $videos = $series->videos()
            ->orderBy('created_at', 'asc')
            ->paginate(24);

        return Inertia::render('Series/Show', [
            'series' => $series,
            'videos' => $videos,
        ]);
    }

    /**
     * Search videos
     */
    public function search(Request $request)
    {
        $query = $request->get('q');

        $videos = Video::where('title', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->with('category')
            ->paginate(24);

        return Inertia::render('Search/Results', [
            'query' => $query,
            'videos' => $videos,
        ]);
    }

    /**
     * Track video progress
     */
    public function trackProgress(Request $request, Video $video)
    {
        $request->validate([
            'seconds_watched' => 'required|integer|min:0',
            'completed' => 'boolean',
        ]);

        $user = Auth::user();

        VideoView::updateOrCreate(
            [
                'video_id' => $video->id,
                'user_id' => $user->id,
            ],
            [
                'seconds_watched' => $request->seconds_watched,
                'completed' => $request->completed ?? false,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'tenant_id' => $user->tenant_id,
            ]
        );

        // Update video analytics
        if ($request->completed) {
            $video->incrementViews();
        }

        $video->addWatchTime($request->seconds_watched);

        return response()->json(['success' => true]);
    }

    /**
     * Get continue watching videos for user
     */
    private function getContinueWatchingVideos($user)
    {
        return VideoView::where('user_id', $user->id)
            ->where('completed', false)
            ->where('seconds_watched', '>', 0)
            ->with(['video' => function ($query) {
                $query->select(['id', 'title', 'thumbnail', 'duration']);
            }])
            ->orderBy('updated_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($view) {
                $video = $view->video;
                $progress = $video ? ($view->seconds_watched / max($video->duration, 1)) * 100 : 0;

                return [
                    'id' => $video->id,
                    'title' => $video->title,
                    'thumbnail' => $video->thumbnail_url,
                    'progress' => min($progress, 100),
                    'seconds_watched' => $view->seconds_watched,
                    'duration' => $video->duration,
                ];
            });
    }

    /**
     * Record a video view
     */
    private function recordVideoView(Video $video, $user): void
    {
        // Only count unique views per user per day
        $existingView = VideoView::where('video_id', $video->id)
            ->where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->first();

        if (!$existingView) {
            $video->incrementViews();
        }

        // Always create/update the view record for progress tracking
        VideoView::updateOrCreate(
            [
                'video_id' => $video->id,
                'user_id' => $user->id,
            ],
            [
                'seconds_watched' => 0,
                'completed' => false,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'tenant_id' => $user->tenant_id,
            ]
        );
    }
}
