<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix "Specified key was too long" on older MySQL / MariaDB versions
        Schema::defaultStringLength(191);

        // Use Bootstrap 5 pagination views
        Paginator::useBootstrapFive();

        // Share app name, description, and update notification to every view
        View::composer('*', function ($view) {
            try {
                $appName = Setting::get('app_name', 'Members Portal');
                $appDesc = Setting::get('app_description', 'Member Database');
            } catch (\Throwable) {
                $appName = 'Members Portal';
                $appDesc = 'Member Database';
            }

            // Weekly update check — only compute for authenticated admins
            $updateAvailable = false;
            $updateVersion   = null;
            try {
                if (auth()->check() && auth()->user()->isAdmin()) {
                    $cacheKey  = 'update_check_result';
                    $cacheDays = (int) config('update.cache_days', 7);
                    $info = Cache::remember($cacheKey, now()->addDays($cacheDays), function () {
                        $repo   = config('update.repo');
                        $branch = config('update.branch', 'main');
                        $url    = "https://raw.githubusercontent.com/{$repo}/{$branch}/version.json";
                        try {
                            $resp = Http::timeout(5)->withOptions(['verify' => false])->get($url);
                            if (!$resp->successful()) return null;
                            $data = $resp->json();
                            if (!isset($data['version'])) return null;
                            $local = '0.0.0';
                            try {
                                $lf = json_decode(file_get_contents(base_path('version.json')), true);
                                $local = $lf['version'] ?? '0.0.0';
                            } catch (\Throwable) {}
                            $data['has_update']  = version_compare($data['version'], $local, '>');
                            $data['local']       = $local;
                            $data['checked_at']  = now()->toDateTimeString();
                            return $data;
                        } catch (\Throwable) {
                            return null;
                        }
                    });
                    if ($info && !empty($info['has_update'])) {
                        $updateAvailable = true;
                        $updateVersion   = $info['version'];
                    }
                }
            } catch (\Throwable) {}

            $certificateNavVisible = auth()->check() && !auth()->user()->isViewer();

            $view->with(compact('appName', 'appDesc', 'updateAvailable', 'updateVersion', 'certificateNavVisible'));
        });
    }
}
