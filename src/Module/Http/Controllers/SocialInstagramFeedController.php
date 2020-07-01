<?php

namespace RefinedDigital\Social\InstagramFeed\Module\Http\Controllers;

use RefinedDigital\Social\InstagramFeed\Module\Http\Repositories\InstagramFeedRepository;
use Illuminate\Http\Request;

class SocialInstagramFeedController {

    protected $repository;

    public function __construct(InstagramFeedRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getForFront(Request $request)
    {
        $limit = $request->get('limit') ?: 20;
        $feed = $this->repository->getUserMedia($limit);

        return response()->json($feed);
    }
}
