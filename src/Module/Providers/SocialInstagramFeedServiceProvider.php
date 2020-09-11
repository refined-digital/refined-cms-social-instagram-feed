<?php

namespace RefinedDigital\Social\InstagramFeed\Module\Providers;

use Illuminate\Support\ServiceProvider;
use RefinedDigital\CMS\Modules\Core\Aggregates\PublicRouteAggregate;
use RefinedDigital\Social\InstagramFeed\Commands\Install;

class SocialInstagramFeedServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

        if ($this->app->runningInConsole()) {
            $this->commands([
                Install::class
            ]);
        }

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
        app(PublicRouteAggregate::class)
            ->addRouteFile('socialInstagramFeed', __DIR__.'/../Http/public-routes.php');
    }
}
