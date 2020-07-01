<?php

Route::namespace('Social\InstagramFeed\Module\Http\Controllers')
    ->group(function() {

        Route::post('instagram-feed', [
            'as' => 'social.instagram.feed.get-for-front',
            'uses' => 'SocialInstagramFeedController@getForFront'
        ]);

    })
;
