<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Modules\Currency\Models\Currency;
use Modules\Tax\Models\Tax;
use App\Models\MobileSetting;
use Modules\Subscriptions\Models\Subscription;
use App\Models\Device;
use Modules\Subscriptions\Models\PlanLimitation;

class SettingController extends Controller
{
    public function appConfiguraton(Request $request)
    {
        $header = request()->headers->all();
        $device_type = !empty($header['device-type'])? $header['device-type'][0] : []; //for tv
        $settings = Setting::all()->pluck('val', 'name');

        $response = [];

        $gatewayRegistry = config('payment_gateways.gateways', []);
        $gatewaySettingNames = [];
        foreach ($gatewayRegistry as $gateway) {
            foreach (($gateway['settings'] ?? []) as $settingName) {
                $gatewaySettingNames[] = $settingName;
            }
        }

        // Define the specific names you want to include
        $specificNames = array_values(array_unique(array_merge([
            'app_name', 'footer_text', 'primary',
            'onesignal_app_id', 'onesignal_rest_api_key', 'onesignal_channel_id',
            'google_maps_key', 'helpline_number', 'copyright', 'inquriy_email', 'site_description',
            'customer_app_play_store', 'customer_app_app_store',
            'isForceUpdate', 'version_code',
        ], $gatewaySettingNames)));
        foreach ($settings as $name => $value) {
            if (in_array($name, $specificNames)) {
                if (strpos($name, 'onesignal_') === 0 && $request->is_authenticated == 1) {
                    $nestedKey = 'onesignal_customer_app';
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$name] = $value;
                }

                foreach ($gatewayRegistry as $gatewayCode => $gateway) {
                    $enabledSetting = $gateway['enabled_setting'] ?? null;
                    $nestedKey = $gateway['nested_key'] ?? null;
                    $settingsList = $gateway['settings'] ?? [];

                    if (! $enabledSetting || ! $nestedKey || empty($settingsList)) {
                        continue;
                    }

                    if ($request->is_authenticated != 1) {
                        continue;
                    }

                    if (($settings[$enabledSetting] ?? 0) != 1) {
                        continue;
                    }

                    if (! in_array($name, $settingsList, true)) {
                        continue;
                    }

                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$name] = $value;
                }

                if (strpos($name, 'onesignal_') !== 0) {
                    $response[$name] = $value;
                }
            }
        }
        // Fetch currency data
        $currency = Currency::where('is_primary',1)->first();

        $currencyData = null;
        if ($currency) {

            $currencyData = [
                'currency_name' => $currency->currency_name,
                'currency_symbol' => $currency->currency_symbol,
                'currency_code' => $currency->currency_code,
                'currency_position' => $currency->currency_position,
                'no_of_decimal' => $currency->no_of_decimal,
                'thousand_separator' => $currency->thousand_separator,
                'decimal_separator' => $currency->decimal_separator,
            ];
        }

        $taxes = Tax::active()->get();
        $ads_val= MobileSetting::where('slug', 'banner')->first();
        $rate_our_app= MobileSetting::where('slug', 'rate-our-app')->first();
        $ads_val= MobileSetting::where('slug', 'banner')->first();
        $continue_watch= MobileSetting::where('slug', 'continue-watching')->first();
        $VideoCast= PlanLimitation::where('slug','video-cast')->first();
        $downloadOption= PlanLimitation::where('slug','download-status')->first();


        if (isset($settings['isForceUpdate']) && isset($settings['version_code'])) {
            $response['isForceUpdate'] = intval($settings['isForceUpdate']);

            $response['version_code'] = intval($settings['version_code']);
        } else {
            $response['isForceUpdate'] = 0;

            $response['version_code'] = 0;
        }
        if(!empty($request->user_id)){
            $getDeviceTypeData = Subscription::checkPlanSupportDevice($request->user_id , $device_type);
            $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
        }

        $response['tax'] = $taxes;

        $response['currency'] = $currencyData;
        $response['google_login_status'] = (int)$settings['is_google_login'] ?? 0;
        $response['apple_login_status'] = (int)$settings['is_apple_login'] ?? 0;
        $response['otp_login_status'] = (int)$settings['is_otp_login'] ?? 0;
        $response['site_description'] = $settings['site_description'] ?? null;
        $response['enable_social_login'] = isset($settings['is_social_login']) ? ($settings['is_social_login'] == '1') : false;
        // Add locale language to the response
        $response['application_language'] = app()->getLocale();
        $response['status'] = true;
        $response['enable_movie'] = isset($settings['movie']) ? intval($settings['movie']) : 0;
        $response['enable_livetv'] = isset($settings['livetv']) ? intval($settings['livetv']) : 0;
        $response['enable_tvshow'] = isset($settings['tvshow']) ? intval($settings['tvshow']) : 0;
        $response['enable_video'] = isset($settings['video']) ? intval($settings['video']) : 0;
        $response['enable_ads'] = isset($ads_val->value) ? (int) $ads_val->value : 0;
        $response['continue_watch'] = isset($continue_watch->value) ? (int) $continue_watch->value : 0;
        $response['enable_rate_us'] = isset($rate_our_app->value) ? (int) $rate_our_app->value : 0;
        $response['enable_in_app'] = isset($settings['iap_payment_method']) ? intval($settings['iap_payment_method']) : 0;
        $response['entitlement_id'] = isset($settings['entertainment_id']) ? $settings['entertainment_id'] : null;
        $response['apple_api_key'] = isset($settings['apple_api_key']) ? $settings['apple_api_key'] : null;
        $response['google_api_key'] = isset($settings['google_api_key']) ? $settings['google_api_key'] : null;
        $response['is_login'] = 0;
        $response['is_casting_available'] = isset($VideoCast) ? ($VideoCast['status'] ?? 0) : 0;
        $response['is_download_available'] = isset($downloadOption) ? ($downloadOption['status'] ?? 0) : 0;
        $response['banner_ad_id'] = isset($settings['banner_ad_id']) ? $settings['banner_ad_id'] : null;
        $response['ios_banner_id'] = isset($settings['ios_banner_id']) ? $settings['ios_banner_id'] : null;
        
        if ($request->has('device_id') && $request->device_id != null && $request->has('user_id') && $request->user_id) {
            $device = Device::where('user_id', $request->user_id)
                ->where('device_id', $request->device_id)
                ->first();

            $response['is_login'] = $device ? 1 : 0;
        }
        if(!empty($request->user_id)){
            $response['is_device_supported'] = $deviceTypeResponse['isDeviceSupported'];
        }
        return response()->json($response);
    }

    public function Configuraton(Request $request)
    {
        $googleMeetSettings = Setting::whereIn('name', ['google_meet_method', 'google_clientid', 'google_secret_key'])
            ->pluck('val', 'name');
        $settings = $googleMeetSettings->toArray();
        return $settings;
    }
}
