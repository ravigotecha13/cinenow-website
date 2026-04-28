<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class UseRequestAppUrl
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->shouldApply()) {
            $root = rtrim($request->getSchemeAndHttpHost().$request->getBasePath(), '/');

            URL::forceRootUrl($root);
            config([
                'app.url' => $root,
                'app.mix_url' => '',
            ]);

            if (config('app.use_request_host_for_public_files')) {
                config(['app.media_base_url' => null]);
            }

            $storage = $root.'/storage';
            config([
                'filesystems.disks.public.url' => $storage,
                'filesystems.disks.images.url' => $storage.'/images',
                'filesystems.disks.files.url' => $storage.'/files',
                'filesystems.disks.media.url' => $storage,
            ]);
        }

        return $next($request);
    }

    protected function shouldApply(): bool
    {
        if (app()->runningInConsole()) {
            return false;
        }

        $flag = env('USE_REQUEST_URL_FOR_APP');
        if ($flag !== null) {
            return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
        }

        return app()->environment('local');
    }
}
