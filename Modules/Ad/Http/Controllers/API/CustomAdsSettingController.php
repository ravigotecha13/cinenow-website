<?php

namespace Modules\Ad\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Ad\Services\ActiveCustomAdsService;

class CustomAdsSettingController extends Controller
{
    public function getActiveAds(Request $request)
    {
        try {
            $user = ActiveCustomAdsService::resolveUserForAds($request);

            if ($user && subscription_plan_blocks_streaming_ads($user)) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Ads are disabled in your subscription.',
                ]);
            }

            $contentId = $request->input('content_id');
            $contentType = $request->input('type');
            $contentVideoType = $request->input('video_type');

            Log::info('CustomAds API called', [
                'content_id' => $contentId,
                'content_type' => $contentType,
                'current_date' => now()->timezone(config('app.timezone'))->format('Y-m-d'),
                'request_params' => $request->all(),
            ]);

            if ($contentVideoType === 'trailer') {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $data = ActiveCustomAdsService::queryActiveList(
                $request,
                $contentType,
                $contentId,
                $contentVideoType
            );

            Log::info('CustomAds query result', [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'ads_found' => count($data),
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getActiveAds:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'content_id' => $request->input('content_id'),
                'category_id' => $request->input('category_id'),
                'type' => $request->input('type'),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
