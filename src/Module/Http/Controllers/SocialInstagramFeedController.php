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
        if ($request->has('code')) {
            $data = new \stdClass();
            $data->success = true;
            $data->message = '<p>Please reload the page to reload the env file changes</p>';

            try {
                $this->repository->exchangeCodeForToken($request->get('code'));
            } catch (\Exception $exception) {
                $data->message = $exception->getMessage();
                $data->success = false;
            }
        } else {
            $limit = $request->get('limit') ?: 20;
            $data = $this->repository->getUserMedia($limit);
        }

        return response()->json($data);
    }
}
