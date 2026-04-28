<?php

namespace Modules\Entertainment\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Transformers\WatchlistResource;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Transformers\ContinueWatchResource;
use Modules\Entertainment\Models\Entertainment;
use Modules\Video\Models\Video;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WatchlistController extends Controller
{
    public function watchList(Request $request)
    {
        $user_id = auth()->user()->id;
        $perPage = $request->input('per_page', 10);

        $profile_id=$request->has('profile_id') && $request->profile_id
        ? $request->profile_id
        : getCurrentProfile($user_id, $request);

        $watchList = Watchlist::where('user_id', $user_id)
            ->where('profile_id', $profile_id)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereIn('type', ['movie', 'tvshow']) // Entertainment types
                      ->whereHas('entertainment', function ($subQuery) {
                          $subQuery->where('status', 1);
                      });
                })
                ->orWhere(function ($q) {
                    $q->where('type', 'video') // Video type
                      ->whereHas('video', function ($subQuery) {
                          $subQuery->where('status', 1);
                      });
                });
            })
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);

        $responseData = WatchlistResource::collection($watchList);

        if ($request->has('is_ajax') && $request->input('is_ajax') == 1) {
            $html = '';

            foreach($responseData->toArray($request) as $watchData) {
                $userId = auth()->id();
                if ($userId)

                    $isInWatchList = Watchlist::where('entertainment_id', $watchData['entertainment_id'])
                                               ->where('user_id', $userId)
                                               ->exists();

                    $watchData['is_watch_list'] = $isInWatchList;

                    if ($watchData['entertainment_type'] === 'video') {

                        $videoData = Video::find($watchData['entertainment_id']);
                        if ($videoData) {

                            $watchData['name'] = $videoData->name;
                            $watchData['description'] = $videoData->description;
                            $watchData['duration'] = $videoData->duration;
                            $watchData['poster_image'] = setBaseUrlWithFileName( $videoData->poster_url);
                            $watchData['access']=$videoData->access;
                            $watchData['id']=$watchData['entertainment_id'];

                        }
                        $html .= view('frontend::components.card.card_video', ['data' => $watchData])->render();

                    }
                    else{
                        $watchData['id']=$watchData['entertainment_id'];
                        $html .= view('frontend::components.card.card_entertainment', ['value' => $watchData])->render();

                    }
                }

            $hasMore = $watchList->hasMorePages();

            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.watch_list'),
                'hasMore' => $hasMore,
            ], 200);
        }

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.watch_list'),
        ], 200);
    }


    public function saveWatchList(Request $request)
    {

        $user = auth()->user();
        $entertainmentId = $request->input('entertainment_id');

        $profile_id=$request->has('profile_id') && $request->profile_id
        ? $request->profile_id
        : getCurrentProfile($user->id, $request);

        $watchlistData = $request->except('user_id');

        $watchlistData['profile_id'] = $profile_id;

        $entertainment = Entertainment::find($entertainmentId);

        if (!$entertainment) {
            return response()->json(['status' => false, 'message' => __('movie.entertainment_not_found')]);
        }
        $watchlistData['user_id'] = $user->id;

        $watchlistEntry = Watchlist::updateOrCreate(
            ['entertainment_id' => $entertainmentId, 'user_id' => $user->id, 'profile_id'=>$profile_id , 'type'=>$request->entertainment_type],
            $watchlistData
        );


        Cache::flush();

        return response()->json(['status' => true, 'message' => __('movie.watchlist_add')]);
    }





    public function deleteWatchList(Request $request)
    {
        try {
            $user = auth()->user();

            // Get profile ID
            $profile_id = $request->has('profile_id') && $request->profile_id
                ? $request->profile_id
                : getCurrentProfile($user->id, $request);

            // Handle single or multiple IDs
            $ids = $request->is_ajax == 1 ? [$request->id] : explode(',', $request->id);

            // Validate if watchlist items exist before deletion
            $watchlistExists = Watchlist::whereIn('entertainment_id', $ids)
                ->where('user_id', $user->id)
                ->where('profile_id', $profile_id)
                ->exists();

            if (!$watchlistExists) {
                return response()->json([
                    'status' => false,
                    'message' => __('movie.watchlist_notfound')
                ], 404);
            }

            // Delete watchlist items
            $deleted = Watchlist::whereIn('entertainment_id', $ids)
                ->where('user_id', $user->id)
                ->where('profile_id', $profile_id)
                ->forceDelete();

            // Clear cache
            Cache::flush();

            return response()->json([
                'status' => true,
                'message' => __('movie.watchlist_delete')
            ]);

        } catch (\Exception $e) {
            \Log::error('Watchlist Delete Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error removing from watchlist'
            ], 500);
        }
    }

    public function continuewatchList(Request $request)
    {
        $user_id = auth()->user()->id;

        $perPage = $request->input('per_page', 10);
        $continuewatchList = ContinueWatch::query()
         ->whereNotNull('watched_time')
         ->whereNotNull('total_watched_time')
         ->where(function ($query) {
             $query->where(function ($q) {
                 $q->whereIn('entertainment_type', ['movie', 'tvshow'])
                   ->whereHas('entertainment', function ($subQ) {
                       $subQ->where('status', 1);
                   });
             })
             ->orWhere(function ($q) {
                 $q->where('entertainment_type', 'video')
                   ->whereHas('video', function ($subQ) {
                       $subQ->where('status', 1);
                   });
             })
             ->orWhere(function ($q) {
                 $q->where('entertainment_type', 'episode')
                   ->whereHas('episode', function ($subQ) {
                       $subQ->where('status', 1);
                   });
             });
         })
         ->with(['entertainment', 'episode', 'video']);

        $profile_id=$request->has('profile_id') && $request->profile_id
        ? $request->profile_id
        : getCurrentProfile($user_id, $request);


        $continuewatch = $continuewatchList
            ->where('user_id', $user_id)
            ->where('profile_id', $profile_id)
            ->orderBy('updated_at', 'desc');
        $continuewatch = $continuewatch->paginate($perPage);

        $responseData = ContinueWatchResource::collection($continuewatch);

        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';
            foreach ($responseData->toArray($request) as $continuewatchData) {
                $userId = auth()->id();
                $html .= view('frontend::components.card.card_continue_watch', ['value' => $continuewatchData])->render();
            }

            $hasMore = $continuewatch->hasMorePages();

            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.movie_list'),
                'hasMore' => $hasMore,
            ], 200);
        }
        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.watch_list'),
        ], 200);
    }

    public function saveContinueWatch(Request $request)
    {
        // dd('hello');
        $user = auth()->user();
        $watch_data = $request->all();

        \Log::info($watch_data);
        // Backward compatibility: some clients send total_time / watched_time in seconds.
        if (!isset($watch_data['total_watched_time']) && isset($watch_data['total_time'])) {
            $watch_data['total_watched_time'] = $watch_data['total_time'];
        }

        $watch_data['watched_time'] = $this->normalizeTimeValue($watch_data['watched_time'] ?? null);
        $watch_data['total_watched_time'] = $this->normalizeTimeValue($watch_data['total_watched_time'] ?? null);
        $watch_data['user_id'] = $user->id;

        $profile_id = resolveContinueWatchProfileId((int) $user->id, $request);

        if ($profile_id === null) {
            return response()->json(['status' => false, 'message' => 'Profile could not be resolved.'], 422);
        }

        $watch_data['profile_id'] = $profile_id;

        $result = ContinueWatch::updateOrCreate(['entertainment_id' => $request->entertainment_id, 'user_id' => $user->id, 'entertainment_type' => $request->entertainment_type,'profile_id'=>$profile_id,'episode_id'=>$request->episode_id], $watch_data);

        // Update PPV Tracking if percentage is provided
        if (($request->entertainment_type == 'movie' || $request->entertainment_type == 'video') && $request->has('percentage')) {
            \Log::info('Updating movie_user_trackings for user ' . $user->id . ' movie ' . $request->entertainment_id . ' percent ' . $request->percentage);
            
            if ($request->entertainment_type == 'movie') {
                \DB::table('movie_user_trackings')
                    ->updateOrInsert(
                        [
                            'user_id' => $user->id,
                            'entertainment_id' => $request->entertainment_id
                        ],
                        [
                            'watched_percentage' => $request->percentage,
                            'updated_at' => now()
                        ]
                    );
            }

            // Update watch_progress for the active ticket
            $ticket = \DB::table('ppv_tickets')
                ->where('user_id', $user->id)
                ->where('entertainment_id', $request->entertainment_id)
                ->where('status', 'active')
                ->latest()
                ->first();

            if ($ticket) {
                // Calculate seconds from HMS if available
                $lastTimeSeconds = 0;
                if (isset($watch_data['watched_time'])) {
                    $time = $watch_data['watched_time'];
                    if (strpos($time, ':') !== false) {
                        [$h, $m, $s] = array_pad(explode(':', $time), 3, 0);
                        $lastTimeSeconds = ((int)$h * 3600) + ((int)$m * 60) + (int)$s;
                    } else {
                        $lastTimeSeconds = (int)$time;
                    }
                }

                \DB::table('watch_progress')->updateOrInsert(
                    ['ticket_id' => $ticket->id],
                    [
                        'user_id' => $user->id,
                        'entertainment_id' => $request->entertainment_id,
                        'entertainment_type' => $request->entertainment_type,
                        'watched_percentage' => $request->percentage,
                        'last_time_seconds' => $lastTimeSeconds,
                        'updated_at' => now(),
                    ]
                );
            }
        }

        Cache::flush();

        return response()->json(['status' => true, 'message' => __('movie.save_msg')]);
    }

    private function normalizeTimeValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return gmdate('H:i:s', max(0, (int) $value));
        }

        $stringValue = trim((string) $value);
        if (substr_count($stringValue, ':') === 1) {
            return $stringValue . ':00';
        }

        return $stringValue;
    }
    public function deleteContinueWatch(Request $request)
    {
        $continuewatch = ContinueWatch::where('id', $request->id)->first();

        if ($continuewatch == null) {
            $message = __('movie.continuewatch_notfound');

            return response()->json(['status' => false, 'message' => $message]);
        }

        if($request->entertainment_type == 'movie'){
            $cacheKey = 'movie_'.$continuewatch ->entertainment_id;
            Cache::flush();

        }
        else if($request->entertainment_type == 'episode'){
            $cacheKey = 'episode_'.$continuewatch ->entertainment_id;
            Cache::flush();

        }

        $continuewatch->delete();

        $message = __('movie.continuewatch_delete');


        return response()->json(['status' => true, 'message' => $message]);
    }
}
