<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MobileSetting;
use Modules\Entertainment\Models\Entertainment;
use Modules\Banner\Models\Banner;
use App\Models\Device;
use App\Models\User;
use Modules\Banner\Transformers\SliderResource;
use Modules\Subscriptions\Models\Plan;
use Modules\Subscriptions\Models\Subscription;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Modules\Entertainment\Transformers\MoviesResource;
use Modules\Entertainment\Transformers\TvshowResource;
use Modules\Tax\Models\Tax;
use Modules\Constant\Models\Constant;
use Modules\FAQ\Models\FAQ;
use Modules\Coupon\Models\Coupon;
use App\Services\RecommendationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Subscriptions\Trait\SubscriptionTrait;
use Illuminate\Support\Facades\Cache;
use Modules\Frontend\Models\PayPerView;
use Modules\Entertainment\Models\Subtitle;
use Modules\Entertainment\Models\EntertainmentDownload;
use Modules\Entertainment\Transformers\MovieDetailResource;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Models\Like;
use Modules\Entertainment\Models\EntertainmentGenerMapping;
use App\Models\UserSearchHistory;
use Modules\Season\Models\Season;
use Modules\Entertainment\Transformers\EpisodeDetailResource;
use Modules\Genres\Models\Genres;
use Modules\Episode\Models\Episode;
use Modules\Entertainment\Transformers\TvshowDetailResource;
use Modules\Video\Models\Video;
use Modules\Video\Transformers\VideoDetailResource;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\LiveTV\Transformers\LiveTvChannelResource;
use Modules\SEO\Models\Seo;

class FrontendController extends Controller
{
    use SubscriptionTrait;
    /**
     * Display a listing of the resource.
     */
    protected $recommendationService;
    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;

    }

    // public function index(Request $request)
    // {
    //     $user_id = auth()->id();
    //     $cacheKey = 'slider';
    //     Cache::flush();

    //     $sliders = Cache::get($cacheKey);
    //     if (!$sliders) {
    //       $sliderList = Banner::where('banner_for', 'home')->where('status', 1)->get();
    //       $sliders = SliderResource::collection($sliderList->map(function ($slider) use ($user_id) {
    //             return new SliderResource($slider, $user_id);
    //       }));

    //       $sliders = $sliders->toArray(request());
    //       Cache::put($cacheKey, $sliders);

    //     }


    //     return view('frontend::index', compact('user_id','sliders'));
    // }



    public function index(Request $request)
    {
        $user_id = auth()->id();
        $cacheKey = 'slider_'.app()->getLocale();

        $sliders = Cache::get($cacheKey);
        if (!$sliders) {
            $bannerList = Banner::where('banner_for', 'home')
                ->where('status', 1)
                ->get();
    
            $sliders = $bannerList->map(function ($banner) use ($user_id) {
                // Load related entertainment
                $entertainment = Entertainment::find($banner->type_id);
    
                if ($entertainment) {
                    // Attach banner's video_trailer_url to entertainment
                    $entertainment->video_trailer_url = $banner->video_trailer_url;
                    $entertainment->banner_id = $banner->id;
                    $entertainment->banner_type = $banner->type;
                }
    
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'poster_url' => $banner->poster_url,
                    'file_url' => $banner->file_url,
                    'type' => $banner->type,
                    'poster_tv_image' => $banner->poster_tv_url,
                    'data' => $entertainment, // ⚡ includes full entertainment + banner video_trailer_url
                ];
            })->toArray();
    
            Cache::put($cacheKey, $sliders);
        }
        
        $todayDate = date('Y-m-d');
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        
        $comingSoonMovies = Entertainment::where('start_date', '>', $todayDate)
            ->where('status', 1)
            ->limit(12)
            ->get();
        
        $leavingSoonMovies = Entertainment::whereBetween('end_date', [$todayDate, $nextWeek])
            ->where('status', 1)
            ->limit(12)
            ->get();
    
        // Debug to verify
        // dd($sliders);
    
        return view('frontend::index', compact('user_id', 'sliders', 'comingSoonMovies',
        'leavingSoonMovies',));
    }

    // public function checkWatchCount(Request $request)
    // {
    //     $userId = auth()->id();
    //     $entertainmentId = $request->entertainment_id;
    
    //     // Ensure user logged in
    //     if (!$userId) {
    //         return response()->json(['status' => 'error', 'message' => 'Please login to continue.']);
    //     }
    
    //     // Fetch current record
    //     $limit = DB::table('entertainment_watch_limits')
    //         ->where('user_id', $userId)
    //         ->where('entertainment_id', $entertainmentId)
    //         ->first();
    
    //     if (!$limit) {
    //         // maybe user never rented this movie
    //         return response()->json(['status' => 'error', 'message' => 'Movie not rented or access not granted.']);
    //     }
    
    //     if ($limit->watch_count_remaining <= 0) {
    //         return response()->json(['status' => 'error', 'message' => 'You have reached your watch limit.']);
    //     }
    
    //     // ✅ Decrement the remaining count
    //     DB::table('entertainment_watch_limits')
    //         ->where('id', $limit->id)
    //         ->update([
    //             'watch_count_remaining' => $limit->watch_count_remaining - 1,
    //             'updated_at' => now(),
    //         ]);
    
    //     return response()->json([
    //         'status' => 'success',
    //         'remaining' => $limit->watch_count_remaining - 1,
    //         'message' => "Remaining views: " . ($limit->watch_count_remaining - 1),
    //     ]);
    // }
    
    // public function checkWatchCount(Request $request)
    // {
    //     $userId = auth()->id();
    //     $entertainmentId = $request->entertainment_id;
    
    //     if (!$userId) {
    //         return response()->json(['status' => 'error', 'message' => 'Please login to continue.']);
    //     }
    
    //     // 1️⃣ Fetch movie access type
    //     $entertainment = DB::table('entertainments')
    //         ->select('id', 'movie_access')
    //         ->where('id', $entertainmentId)
    //         ->first();
    
    //     if (!$entertainment) {
    //         return response()->json(['status' => 'error', 'message' => 'Movie not found.']);
    //     }
    
    //     // 2️⃣ If movie is free → directly play
    //     if ($entertainment->movie_access === 'free') {
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'Free movie — unlimited watch allowed.',
    //         ]);
    //     }
    
    //     // 3️⃣ If movie is rental or pay-per-view → check user watch limit
    //     $limit = DB::table('entertainment_watch_limits')
    //         ->where('user_id', $userId)
    //         ->where('entertainment_id', $entertainmentId)
    //         ->first();
    
    //     if (!$limit) {
    //         return response()->json(['status' => 'error', 'message' => 'Movie not rented or access not granted.']);
    //     }
    
    //     if ($limit->watch_count_remaining <= 0) {
    //         return response()->json(['status' => 'error', 'message' => 'You have reached your watch limit.']);
    //     }
    
    //     // 4️⃣ Decrement remaining count
    //     DB::table('entertainment_watch_limits')
    //         ->where('id', $limit->id)
    //         ->update([
    //             'watch_count_remaining' => $limit->watch_count_remaining - 1,
    //             'updated_at' => now(),
    //         ]);
    
    //     return response()->json([
    //         'status' => 'success',
    //         'remaining' => $limit->watch_count_remaining - 1,
    //         'message' => 'Watch started successfully.',
    //     ]);
    // }
    
    public function checkWatchCount(Request $request)
    {
        $userId = auth()->id();
        $entertainmentId = $request->entertainment_id;
    
        if (!$userId) {
            return response()->json(['status' => 'unauthenticated']);
        }
    
        $entertainment = DB::table('entertainments')
            ->select('id', 'movie_access')
            ->where('id', $entertainmentId)
            ->first();
    
        if (!$entertainment) {
            return response()->json(['status' => 'error', 'message' => 'Movie not found.']);
        }
    
        if ($entertainment->movie_access === 'free') {
            return response()->json(['status' => 'success', 'message' => 'Free movie — unlimited watch allowed.']);
        }
    
        $limit = DB::table('entertainment_watch_limits')
            ->where('user_id', $userId)
            ->where('entertainment_id', $entertainmentId)
            ->first();
    
        if (!$limit) {
            return response()->json(['status' => 'error', 'message' => 'Movie not rented or access not granted.']);
        }
    
        if ($limit->watch_count_remaining <= 0) {
            return response()->json(['status' => 'error', 'message' => 'You have reached your watch limit.']);
        }
    
        DB::table('entertainment_watch_limits')
            ->where('id', $limit->id)
            ->update([
                'watch_count_remaining' => $limit->watch_count_remaining - 1,
                'updated_at' => now(),
            ]);
    
        return response()->json([
            'status' => 'success',
            'remaining' => $limit->watch_count_remaining - 1,
            'message' => 'Watch started successfully.',
        ]);
    }
    
    
    public function decrementWatchCount(Request $request)
    {
        $userId = auth()->id();
        $entertainmentId = $request->entertainment_id;
    
        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'Please login to continue.']);
        }
    
        $limit = DB::table('entertainment_watch_limits')
            ->where('user_id', $userId)
            ->where('entertainment_id', $entertainmentId)
            ->first();
    
        if (!$limit) {
            return response()->json(['status' => 'error', 'message' => 'Watch limit record not found.']);
        }
    
        // Don’t go below zero
        if ($limit->watch_count_remaining > 0) {
            DB::table('entertainment_watch_limits')
                ->where('id', $limit->id)
                ->update([
                    'watch_count_remaining' => $limit->watch_count_remaining - 1,
                    'updated_at' => now(),
                ]);
        }
    
        return response()->json([
            'status' => 'success',
            'remaining' => max(0, $limit->watch_count_remaining - 1),
            'message' => 'Watch count decremented after full playback.',
        ]);
    }

    // public function updateWatchProgress(Request $request)
    // {
    //     $userId = auth()->id();
    //     $entertainmentId = (int) $request->entertainment_id;
    //     $percentage = (int) $request->watched_percentage;
    
    //     if (!$userId) {
    //         return response()->json(['status' => 'unauthenticated']);
    //     }
    
    //     // Fetch tracking only for PAID movies
    //     $tracking = DB::table('movie_user_trackings')
    //         ->where('user_id', $userId)
    //         ->where('entertainment_id', $entertainmentId)
    //         ->first();
    
    //     // -------------------------------------------------------
    //     // 1. FREE MOVIE → NO TRACKING ENTRY SHOULD EXIST
    //     // -------------------------------------------------------
    //     if (!$tracking) {
    //         return response()->json([
    //             'status' => 'ok',
    //             'watched_percentage' => $percentage
    //         ]);
    //     }
    
    //     // Paid Movie Tracking Exists
    //     $now = now();
    
    //     // -------------------------------------------------------
    //     // 2. Paid & Purchased → Update 0 → 100 freely
    //     // -------------------------------------------------------
    //     if ($tracking->ticket_purchased == 1) {
    
    //         DB::table('movie_user_trackings')
    //             ->where('id', $tracking->id)
    //             ->update([
    //                 'watched_percentage' => $percentage,
    //                 'updated_at' => $now,
    //             ]);
    
    //         return response()->json([
    //             'status' => 'ok',
    //             'watched_percentage' => $percentage,
    //         ]);
    //     }
    
    //     // -------------------------------------------------------
    //     // 3. Paid BUT Not Purchased → Apply 25% rule
    //     // -------------------------------------------------------
    
    //     $previous = (int) $tracking->watched_percentage;
    
    //     // Already crossed 25 → block always
    //     if ($tracking->current_status === 'watched_25') {
    //         return response()->json([
    //             'status' => 'get_ticket',
    //             'watched_percentage' => $previous,
    //         ]);
    //     }
    
    //     // Update only if still under 25
    //     if ($previous < 25) {
    
    //         DB::table('movie_user_trackings')
    //             ->where('id', $tracking->id)
    //             ->update([
    //                 'watched_percentage' => $percentage,
    //                 'updated_at' => $now,
    //             ]);
    
    //         // Check if crossing now
    //         if ($percentage >= 25) {
    
    //             DB::table('movie_user_trackings')
    //                 ->where('id', $tracking->id)
    //                 ->update([
    //                     'current_status' => 'watched_25',
    //                     'updated_at' => $now,
    //                 ]);
    
    //             return response()->json([
    //                 'status' => 'get_ticket',
    //                 'watched_percentage' => $percentage,
    //             ]);
    //         }
    //     }
    
    //     return response()->json([
    //         'status' => 'ok',
    //         'watched_percentage' => $percentage,
    //     ]);
    // }
    
//     public function updateWatchProgress(Request $request)
// {
//     $userId = auth()->id();
//     $entertainmentId = (int) $request->entertainment_id;
//     $percentage = min(100, max(0, (int) $request->watched_percentage));

//     if (!$userId) {
//         return response()->json(['status' => 'unauthenticated']);
//     }

//     $tracking = DB::table('movie_user_trackings')
//         ->where('user_id', $userId)
//         ->where('entertainment_id', $entertainmentId)
//         ->first();

//     // FREE MOVIE
//     if (!$tracking) {
//         return response()->json(['status' => 'ok']);
//     }

//     $now = now();

//     /**
//      * -----------------------------------------
//      * CASE 1: MOVIE ALREADY EXPIRED (100%)
//      * -----------------------------------------
//      */
//     if ($tracking->current_status === 'expired') {
//         return response()->json([
//             'status' => 'get_ticket',
//             'locked' => true
//         ]);
//     }

//     /**
//      * -----------------------------------------
//      * CASE 2: USER HAS PURCHASED
//      * -----------------------------------------
//      */
//     if ($tracking->ticket_purchased == 1) {

//         // 🔒 HIT 100% → EXPIRE MOVIE (ONE TIME WATCH)
//         if ($percentage >= 100) {
//             DB::table('movie_user_trackings')
//                 ->where('id', $tracking->id)
//                 ->update([
//                     'watched_percentage' => 100,
//                     'current_status' => 'expired',
//                     'ticket_purchased' => 0, // force re-purchase
//                     'updated_at' => $now,
//                 ]);

//             return response()->json([
//                 'status' => 'get_ticket',
//                 'locked' => true
//             ]);
//         }

//         // 🟡 CROSS 25% → CHANGE UI ONLY
//         if ($percentage >= 25 && $tracking->current_status !== 'watched_25') {
//             DB::table('movie_user_trackings')
//                 ->where('id', $tracking->id)
//                 ->update([
//                     'watched_percentage' => $percentage,
//                     'current_status' => 'watched_25',
//                     'updated_at' => $now,
//                 ]);

//             return response()->json([
//                 'status' => 'get_ticket',
//                 'locked' => false
//             ]);
//         }

//         // 🟢 NORMAL WATCH
//         DB::table('movie_user_trackings')
//             ->where('id', $tracking->id)
//             ->update([
//                 'watched_percentage' => $percentage,
//                 'current_status' => 'watch_now',
//                 'updated_at' => $now,
//             ]);

//         return response()->json([
//             'status' => 'ok'
//         ]);
//     }

//     /**
//      * -----------------------------------------
//      * CASE 3: NOT PURCHASED → BLOCK ALWAYS
//      * -----------------------------------------
//      */
//     return response()->json([
//         'status' => 'get_ticket',
//         'locked' => true
//     ]);
// }

public function updateWatchProgress(Request $request)
{
    $userId = auth()->id();
    $entertainmentId = (int) $request->entertainment_id;
    $percentage = min(100, max(0, (int) $request->watched_percentage));

    if (!$userId) {
        return response()->json(['status' => 'unauthenticated']);
    }

    $now = now();

    /**
     * ----------------------------------------------------
     * 1️⃣ CHECK ACTIVE PPV TICKET (AUTHORITATIVE)
     * ----------------------------------------------------
     */
    $activeTicket = DB::table('ppv_tickets')
        ->where('user_id', $userId)
        ->where('entertainment_id', $entertainmentId)
        ->where('status', 'active')
        ->first();

    /**
     * ----------------------------------------------------
     * 2️⃣ UPSERT WATCH PROGRESS
     * ----------------------------------------------------
     */
    $ticketId = $activeTicket ? $activeTicket->id : null;
    $existingProgress = null;

    if ($ticketId) {
        $existingProgress = DB::table('watch_progress')
            ->where('ticket_id', $ticketId)
            ->first();
    }

    if (!$existingProgress) {
        $existingProgress = DB::table('watch_progress')
            ->where('user_id', $userId)
            ->where('entertainment_id', $entertainmentId)
            ->first();
    }

    if ($existingProgress) {
        DB::table('watch_progress')
            ->where('id', $existingProgress->id)
            ->update([
                'watched_percentage' => max($existingProgress->watched_percentage, $percentage),
                'last_time_seconds' => (int) $request->last_time_seconds,
                'ticket_id' => $ticketId, // Ensure ticket_id is linked
                'updated_at' => $now,
            ]);
    } else {
        DB::table('watch_progress')->insert([
            'user_id' => $userId,
            'entertainment_id' => $entertainmentId,
            'watched_percentage' => $percentage,
            'last_time_seconds' => (int) $request->last_time_seconds,
            'ticket_id' => $ticketId, // Ensure ticket_id is linked
            'updated_at' => $now,
        ]);
    }

    /**
     * ----------------------------------------------------
     * 3️⃣ COMPLETION → REQUIRE REPURCHASE
     * ----------------------------------------------------
     */
    if ($percentage >= 98 && !$activeTicket) {
        return response()->json([
            'status' => 'expired',
            'show_repurchase' => true,
        ]);
    }

    /**
     * ----------------------------------------------------
     * 4️⃣ 25% CROSSED → SHOW UI ONLY (NO BLOCK)
     * ----------------------------------------------------
     */
    if ($percentage >= 25 && $activeTicket) {
        return response()->json([
            'status' => 'watched_25',
            'show_repurchase' => true,
        ]);
    }

    /**
     * ----------------------------------------------------
     * 5️⃣ NORMAL WATCH
     * ----------------------------------------------------
     */
    return response()->json([
        'status' => 'ok',
        'show_repurchase' => false,
    ]);
}



    public function searchList()
    {
        $entertainment_list = Entertainment::query()
        ->with([
            'entertainmentGenerMappings',
            'plan',
            'entertainmentTalentMappings',
            'entertainmentStreamContentMappings',
            'entertainmentReviews' => function ($query) {
                $query->whereBetween('rating', [4, 5]);
            }
        ])
        ->where('status', 1)
        ->where('deleted_at', null);


    // Fetch movies
    $movieList = $entertainment_list->where('type', 'movie')->take(10)->get();
    $movieData = (isenablemodule('movie') == 1) ? MoviesResource::collection($movieList) : [];

    $entertainment_data = Entertainment::query()
    ->with([
        'entertainmentGenerMappings',
        'plan',
        'entertainmentTalentMappings',
        'entertainmentStreamContentMappings',
        'entertainmentReviews' => function ($query) {
            $query->whereBetween('rating', [4, 5]);
        }
    ])
    ->where('status', 1)
    ->where('deleted_at', null);


    // Fetch TV shows
    $tvshowList = $entertainment_data->where('type', 'tvshow')->take(10)->get();
    $tvshowData = (isenablemodule('tvshow') == 1) ? TvshowResource::collection($tvshowList) : [];


        return view('frontend::search', compact('movieData', 'tvshowData'));
    }

    public function tvshowList()
    {

        return view('frontend::movie');
    }

    public function continueWatchList()
    {
        return view('frontend::continueWatch');
    }

    public function languageList()
    {
        $languageIds = MobileSetting::getValueBySlug('enjoy-in-your-native-tongue');
        $popular_language = Constant::whereIn('id',json_decode($languageIds))->get();

        return view('frontend::language',compact('popular_language'));
    }
    public function languageData(Request $request){
        $perPage = $request->input('per_page', 10);
        $languageIds = MobileSetting::getValueBySlug('enjoy-in-your-native-tongue');
        $popular_language = Constant::whereIn('id',json_decode($languageIds));

        $html = '';
        $popular_language = $popular_language->paginate($perPage);
            foreach($popular_language as $language) {
                $html .= view('frontend::components.card.card_language',['popular_language' => $language])->render();
            }
            $hasMore = $popular_language->hasMorePages();
            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.tvshow_list'),
                'hasMore' => $hasMore,
            ], 200);
    }
    public function topChannelList()
    {
        return view('frontend::topChannel');
    }
    public function genresList()
    {
        return view('frontend::genres');
    }

    public function comingsoon()
    {
        return view('frontend::comingsoon');
    }
    public function livetv()
    {
        return view('frontend::livetv');
    }
    public function subscriptionPlan()
    {

        $plans = Plan::with('planLimitation')->where('status',1)->get();
        $activeSubscriptions = Subscription::where('user_id', auth()->id())->where('status', 'active')->where('end_date', '>', now())->orderBy('id','desc')->first();
        $currentPlanId = $activeSubscriptions ? $activeSubscriptions->plan_id : null;
        $subscriptions = Subscription::where('user_id', auth()->id())
        ->with('subscription_transaction')
        ->where('end_date', '<', now())
        ->get();

        return view('frontend::subscriptionplan', compact('plans','currentPlanId','activeSubscriptions'));
    }
    public function watchList()
    {
        return view('frontend::watchlist');
    }

    public function accountSetting()
    {
        $user = auth()->user();

         $subscriptions = Subscription::where('user_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('id','desc')
            ->first();

        $devices = $user->devices;

        $your_device = null;
        $other_devices = [];

        $currentDeviceIp = request()->getClientIp();

        foreach ($devices as $device) {
            if ($device->device_id == $currentDeviceIp) {
                $your_device = $device;
            } else {
                $other_devices[] = $device;
            }
        }

        return view('frontend::accountSetting', compact('subscriptions', 'user', 'your_device', 'other_devices'));
    }


    public function deviceLogout(Request $request)
    {
        $userId = auth()->user()->id;

        $deviceQuery = Device::where('user_id', $userId);

        if ($request->has('device_id')) {
            $deviceQuery->where('device_id', $request->device_id);
        }

        if ($request->has('id')) {
            $deviceQuery->orWhere('id', $request->id);
        }

        $device = $deviceQuery->first();
        if (!$device) {
            return redirect()->back()->with('error', __('users.device_not_found'));
        }

        $device->delete();

        $sessionQuery = DB::table('sessions')->where('user_id', $userId);

        if ($request->has('device_id')) {
            $sessionQuery->where('ip_address', $request->device_id);
        }

        if ($request->has('id')) {
            $sessionQuery->orWhere('id', $request->id);
        }

        $session = $sessionQuery->first();
        if ($session) {
            $sessionQuery->delete();
        }

        return redirect()->back()->with('success', __('users.device_logout'));
    }


    public function faq()
    {
        $content = FAQ::where('status',1)->get();
        return view('frontend::faq',compact('content'));
    }


  public function PaymentHistory()
{
    $subscriptions = Subscription::where('user_id', auth()->id())
        ->with('subscription_transaction', 'plan')
        ->orderBy('id', 'desc')  // <--- add this line
        ->get();

    $activeSubscriptions = Subscription::where('user_id', auth()->id())
        ->where('status', 'active')
        ->where('end_date', '>', now())
        ->orderBy('id', 'desc')
        ->first();

    return view('frontend::paymentHistory', compact('activeSubscriptions', 'subscriptions'));
}


    public function transactionHistory()
    {
        $payPerViews = PayPerView::where('user_id', auth()->id())
        ->with(['movie', 'episode', 'video'])
        ->orderBy('created_at', 'desc')
        ->get();

        return view('frontend::transactionHistory', compact('payPerViews'));
    }

    public function allReview($id)
    {
        $entertainment = Entertainment::findOrFail($id);
        $reviews = $entertainment->entertainmentReviews;
        $ratingCounts = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ];
        foreach ($reviews as $review) {
            if (isset($ratingCounts[$review->rating])) {
                $ratingCounts[$review->rating]++;
            }
        }
        $totalRating = $reviews->sum('rating');
        $reviewCount = $reviews->count();
        $averageRating = $reviewCount > 0 ? $totalRating / $reviewCount : 0;

        return view('frontend::review', compact('entertainment', 'reviews', 'averageRating', 'ratingCounts', 'reviewCount'));
    }

    public function EpisodeDetails()
    {
        return view('frontend::episode_detail');
    }

    public function VideoDetails()
    {
     return view('frontend::video_detail');
    }

    public function profile()
    {
        return view('frontend::components.user.profile');
    }

    public function cancelSubscription(Request $request)
    {
        try {
            $planId = $request->input('plan_id');
            Subscription::where('user_id', auth()->id())
                ->where('id', $request->id)
                ->where('status', 'active')
                ->update(['status' => 'cancel']);

            $otherSubscription=Subscription::where('user_id', auth()->id())
                ->where('status', 'active')->get();

            if($otherSubscription->isEmpty()){

                $user=User::where('id',auth()->id() )->first();

                $user->update(['is_subscribe'=>0]);

            }



            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function decryptUrl(Request $request)
  {
      $encryptedUrl = $request->input('encrypted_url');

      try {
          $decryptedUrl = Crypt::decryptString($encryptedUrl);
          return response()->json(['url' => $decryptedUrl], 200);
      } catch (\Exception $e) {
          return response()->json(['error' => 'Invalid URL'], 400);
      }
  }
public function getPaymentDetails(Request $request)
{
    $planId = $request->input('plan_id');
    $promotionId = $request->input('promotion_id'); // Optional promotion
    $plan = Plan::find($planId);
    $discount_percentage = $plan->discount_percentage;

    $discount_amount = ($discount_percentage * $plan->price) / 100;

    $taxes = Tax::where('status', 1)->get();
    $baseAmount = $plan->total_price;
    $totalTaxamount = 0;
    $taxesArray = [];

    $subtotalBeforePromotion = $baseAmount;
    $promotionDiscountAmount = 0;

    // Apply promotion if provided
    if ($promotionId) {
        $promotion = Coupon::where('id', $promotionId)
            ->where('status', 1)
            ->whereHas('subscriptionPlans', function ($query) use ($planId) {
                $query->where('subscription_plan_id', $planId);
            })
            ->first();

        if ($promotion) {
            if ($promotion->discount_type === 'percentage') {
                $promotionDiscountAmount = ($promotion->discount * $baseAmount) / 100;
            } elseif ($promotion->discount_type === 'fixed') {
                $promotionDiscountAmount = $promotion->discount;
            }
        } else {
            return response()->json(['error' => 'Invalid or expired promotion.'], 400);
        }
    }

    // Calculate the subtotal after applying the promotion discount
    $subtotalAfterPromotion = max(0, $subtotalBeforePromotion - $promotionDiscountAmount);

    // Recalculate taxes based on the updated subtotal
    foreach ($taxes as $tax) {
        $taxAmount = 0;

        if (strtolower($tax->type) == 'fixed') {
            $taxAmount = $tax->value;
        } elseif (strtolower($tax->type) == 'percentage') {
            $taxAmount = ($subtotalAfterPromotion * $tax->value) / 100;
        }

        $taxesArray[] = [
            'name' => $tax->title,
            'type' => $tax->type,
            'value' => $tax->value,
            'tax_amount' => $taxAmount
        ];

        $totalTaxamount += $taxAmount;
    }

    // Calculate the total amount
    $totalAfterPromotion = $subtotalAfterPromotion + $totalTaxamount;

    return response()->json([
        'price' => $plan->price,
        'total_price' => $plan->total_price,
        'subtotal' => $subtotalAfterPromotion,
        'discount_percentage' => $discount_percentage,
        'plan_discount_amount' => $discount_amount,
        'tax' => $totalTaxamount,
        'tax_array' => $taxesArray,
        'promotion_id' => $promotionId,
        'promotion_discount_amount' => $promotionDiscountAmount,
        'total' => $totalAfterPromotion,
    ]);
}

    public function checkSubscription($planId)
   {
    $user = auth()->user();
    $currentSubscription = Subscription::where('user_id', $user->id)->where('status', 'active')->get();

    $planData=Plan::Where('id',$planId)->first();

    $level=$planData->level;

    foreach($currentSubscription as $plan)
    {

        if ($plan->level >= $level) {
            return response()->json(['isActive' => true]);
        }
    }
    return response()->json(['isActive' => false]);
   }


   public function checkDeviceType() {
        $checkDeviceType = Subscription::checkPlanSupportDevice(auth()->id());
        return $checkDeviceType;
    }



    public function downloadInvoice(Request $request)
    {

        $data = Subscription::with('plan','subscription_transaction','user')->find($request->id);
            if (!$data) {
                return response()->json(['status' => false, 'message' => 'subscription not found'], 404);
            }

        $pdf = PDF::loadView('frontend::components.partials.invoice', compact('data'))
        ->setOptions([
            'defaultFont' => 'Noto Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
        ]);
        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->output();
            },
            "invoice.pdf",
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="invoice.pdf"',
            ]
        );
    }


}


