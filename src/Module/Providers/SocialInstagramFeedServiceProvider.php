<?php

namespace RefinedDigital\Social\InstagramFeed\Module\Providers;

use Illuminate\Support\ServiceProvider;
use RefinedDigital\Team\Commands\Install;
use RefinedDigital\CMS\Modules\Core\Models\PackageAggregate;
use RefinedDigital\CMS\Modules\Core\Models\ModuleAggregate;
use RefinedDigital\CMS\Modules\Core\Models\RouteAggregate;

class SocialInstagramFeedServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../../config/instagram-feed.php' => config_path('instagram-feed.php'),
        ], 'instagram-feed');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
