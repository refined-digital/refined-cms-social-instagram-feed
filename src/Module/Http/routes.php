<?php

Route::namespace('Social\InstagramFeed\Module\Http\Controllers')
    ->group(function() {
        Route::patch('instagram', 'SocialInstagramFeedController@update')->name('instagram.update');
        Route::get('instagram', 'SocialInstagramFeedController@index')->name('instagram.index');
    })
;
