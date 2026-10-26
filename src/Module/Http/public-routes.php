<?php

use RefinedDigital\Social\InstagramFeed\Module\Http\Controllers\SocialInstagramFeedController;

Route::post('instagram-feed', [SocialInstagramFeedController::class, 'getForFront'])
    ->name('social.instagram.feed.get-for-front');
