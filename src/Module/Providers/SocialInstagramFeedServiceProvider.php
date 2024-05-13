<?php

namespace RefinedDigital\Social\InstagramFeed\Module\Providers;

use Illuminate\Support\ServiceProvider;
use RefinedDigital\CMS\Modules\Core\Aggregates\ModuleAggregate;
use RefinedDigital\CMS\Modules\Core\Aggregates\PublicRouteAggregate;
use RefinedDigital\CMS\Modules\Core\Aggregates\RouteAggregate;
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
        view()->addNamespace('instagram', [
            base_path().'/resources/views',
            __DIR__.'/../Resources/views',
        ]);

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

        app(RouteAggregate::class)
            ->addRouteFile('socialInstagramFeed', __DIR__.'/../Http/routes.php');

        app(PublicRouteAggregate::class)
            ->addRouteFile('socialInstagramFeed', __DIR__.'/../Http/public-routes.php');



        $menuConfig = [
            'order' => 700,
            'name' => 'Instagram',
            'icon' => 'fab fa-instagram',
            'route' => 'instagram',
            'activeFor' => ['instagram'],
        ];

        app(ModuleAggregate::class)
            ->addMenuItem($menuConfig);

        $this->mergeConfigFrom(__DIR__.'/../../../config/instagram-feed.php', 'instagram-feed');
    }
}
