<?php

use \RefinedDigital\Social\InstagramFeed\Module\Http\Repositories\InstagramFeedRepository;

if (! function_exists('instagramFeed')) {
    function instagramFeed()
    {
        return app(InstagramFeedRepository::class);
    }
}
