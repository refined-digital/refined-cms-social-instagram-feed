<?php

use RefinedDigital\Social\InstagramFeed\Module\Http\Controllers\SocialInstagramFeedController;

Route::get('instagram', [SocialInstagramFeedController::class, 'index'])->name('instagram.index');
Route::patch('instagram', [SocialInstagramFeedController::class, 'update'])->name('instagram.update');
