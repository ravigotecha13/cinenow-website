<?php

namespace Modules\Ad\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Ad\Models\CustomAdsSetting;
use Modules\Ad\Transformers\CustomAdsSettingResource;

class ActiveCustomAdsService
{
    public static function resolveUserForAds(?Request $request = null): ?User
    {
        $request = $request ?? request();
        if (auth()->check()) {
            return User::with('subscriptionPackage')->find(auth()->id());
        }
        $uid = $request->input('user_id');
        if (! empty($uid)) {
            return User::with('subscriptionPackage')->find($uid);
        }

        return null;
    }

    /**
     * Same rules as custom-ads API get-active, including ad-free / subscription.
     * Use for embedded payloads (e.g. v2/movie-details).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getForContent(
        Request $request,
        ?string $contentType,
        $contentId,
        ?string $contentVideoType = null
    ): array {
        $user = self::resolveUserForAds($request);
        if ($user && subscription_plan_blocks_streaming_ads($user)) {
            return [];
        }

        return self::queryActiveList($request, $contentType, $contentId, $contentVideoType);
    }

    /**
     * Same filtering as getForContent, but a single media URL for clients that do not need the full ad object.
     * Prefers placement "player", then "movie_detail" / "movie_detail_page", otherwise first ad with a media URL.
     */
    public static function getForContentSingleMediaUrl(
        Request $request,
        ?string $contentType,
        $contentId,
        ?string $contentVideoType = null
    ): ?string {
        $list = self::getForContent($request, $contentType, $contentId, $contentVideoType);
        if ($list === []) {
            return null;
        }

        $priorityPlacements = ['player', 'movie_detail', 'movie_detail_page'];
        foreach ($priorityPlacements as $placement) {
            foreach ($list as $ad) {
                if (strtolower((string) ($ad['placement'] ?? '')) === $placement && ! empty($ad['media'])) {
                    return (string) $ad['media'];
                }
            }
        }

        foreach ($list as $ad) {
            if (! empty($ad['media'])) {
                return (string) $ad['media'];
            }
        }

        return null;
    }

    /**
     * List active custom ads (status, dates, targeting, PPV) without subscription / ad-free checks.
     * Callers that need the "Ads are disabled" API message should test subscription first.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function queryActiveList(
        Request $request,
        ?string $contentType,
        $contentId,
        ?string $contentVideoType = null
    ): array {
        try {
            $user = self::resolveUserForAds($request);

            $currentDate = Carbon::now()->timezone(config('app.timezone'))->format('Y-m-d');

            if ($contentVideoType === 'trailer') {
                return [];
            }

            $query = CustomAdsSetting::where('status', 1);

            $query->where(function ($q) use ($currentDate) {
                $q->where(function ($dateQuery) use ($currentDate) {
                    $dateQuery->whereNotNull('start_date')
                        ->whereNotNull('end_date')
                        ->where('start_date', '<=', $currentDate)
                        ->where('end_date', '>=', $currentDate);
                })->orWhere(function ($dateQuery) use ($currentDate) {
                    $dateQuery->whereNotNull('start_date')
                        ->whereNull('end_date')
                        ->where('start_date', '<=', $currentDate);
                })->orWhere(function ($dateQuery) use ($currentDate) {
                    $dateQuery->whereNull('start_date')
                        ->whereNotNull('end_date')
                        ->where('end_date', '>=', $currentDate);
                })->orWhere(function ($dateQuery) {
                    $dateQuery->whereNull('start_date')
                        ->whereNull('end_date');
                });
            });

            if ($contentType) {
                $contentTypeNorm = strtolower((string) $contentType);
                $query->where(function ($q) use ($contentTypeNorm) {
                    $q->whereRaw('LOWER(TRIM(COALESCE(target_content_type, ?))) = ?', ['', $contentTypeNorm])
                        ->orWhereNull('target_content_type')
                        ->orWhereRaw('TRIM(COALESCE(target_content_type, ?)) = ?', ['', '']);
                });
            }

            $activeAds = $query->orderBy('id', 'asc')->get();

            // "Target categories" in admin = content IDs (movies, episodes, videos) from
            // getTargetCategories, not genre taxonomy. With a content context, only return ads
            // that explicitly include this content; empty target list must not match all titles.
            if ($contentId !== null && $contentId !== '' && $contentType) {
                $cid = (int) $contentId;
                $activeAds = $activeAds->filter(function ($ad) use ($cid) {
                    $ids = json_decode($ad->target_categories, true);
                    if (! is_array($ids) || count($ids) === 0) {
                        return false;
                    }

                    return in_array($cid, array_map('intval', $ids), true);
                })->values();
            }

            $reqContentId = $contentId;
            if ($reqContentId !== null && $reqContentId !== '') {
                $reqContentId = (int) $reqContentId;
            } else {
                $reqContentId = null;
            }

            $filteredAds = $activeAds->map(function ($ad) use ($user, $reqContentId) {
                $targetType = strtolower($ad->target_content_type ?? '');

                if (in_array($targetType, ['home_page', ''], true)) {
                    return $ad;
                }

                $targetIds = json_decode($ad->target_categories, true);
                if (! is_array($targetIds) || empty($targetIds)) {
                    return $ad;
                }

                $purchasedIds = [];
                if ($user) {
                    $payPerViewType = $targetType === 'tvshow' ? 'episode' : $targetType;

                    $purchasedIds = DB::table('pay_per_views')
                        ->where('user_id', $user->id)
                        ->where('type', $payPerViewType)
                        ->whereIn('movie_id', $targetIds)
                        ->pluck('movie_id')
                        ->toArray();
                }

                $placementLower = strtolower((string) ($ad->placement ?? ''));
                $playerPlacements = ['player', 'movie_detail', 'movie_detail_page'];
                $allowAdsWhenPurchased = in_array($placementLower, $playerPlacements, true);

                if ($reqContentId && in_array($reqContentId, $purchasedIds, true)) {
                    if (! $allowAdsWhenPurchased && ! in_array($reqContentId, [118, 126], true)) {
                        return null;
                    }
                    if ($allowAdsWhenPurchased) {
                        return $ad;
                    }
                }

                $remainingIds = array_values(array_diff($targetIds, $purchasedIds));
                if (count($remainingIds) === 0) {
                    return null;
                }
                $ad->target_categories = json_encode($remainingIds);

                return $ad;
            })->filter(function ($ad) {
                if ($ad === null) {
                    return false;
                }

                $targetType = strtolower($ad->target_content_type ?? '');

                if (in_array($targetType, ['home_page', ''], true)) {
                    return true;
                }

                $targets = json_decode($ad->target_categories, true);
                if (! is_array($targets)) {
                    return false;
                }
                if (count($targets) === 0) {
                    return true;
                }

                return count($targets) > 0;
            })->values();

            Log::info('CustomAds query result', [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'current_date' => $currentDate,
                'ads_found' => $filteredAds->count(),
            ]);

            return $filteredAds->map(function ($ad) use ($request) {
                return (new CustomAdsSettingResource($ad))->toArray($request);
            })->values()->all();
        } catch (\Exception $e) {
            Log::error('Error in ActiveCustomAdsService::queryActiveList', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }
}
