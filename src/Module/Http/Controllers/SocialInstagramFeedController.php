<?php

namespace RefinedDigital\Social\InstagramFeed\Module\Http\Controllers;

use RefinedDigital\CMS\Modules\Core\Enums\PageContentType;
use RefinedDigital\CMS\Modules\Pages\Models\Page;
use RefinedDigital\CMS\Modules\Settings\Models\Setting;
use RefinedDigital\Social\InstagramFeed\Module\Http\Repositories\InstagramFeedRepository;
use RefinedDigital\Social\InstagramFeed\Module\Http\Requests\InstagramFeedRequest;
use Illuminate\Http\Request;

class SocialInstagramFeedController
{
    protected $settings;
    protected $repository;

    public function __construct()
    {
        $this->settings = settings()->get('instagram');

        $this->repository = new InstagramFeedRepository();
    }

    public function index()
    {
        if (request()->has('code')) {
            $code = str_replace('#_', '', request()->get('code'));

            return $this->repository->exchangeCodeForToken($code);
        }


        $settings = [
            'client_id' => $this->settings->client_id->value ?? null,
            'client_secret' => $this->settings->client_secret->value ?? null,
            'redirect_url' => $this->settings->redirect_url->value ?? request()->url(),
        ];

        return view('instagram::index')->with('repo', $this->repository)->with(compact('settings'));
    }

    public function update(InstagramFeedRequest $request)
    {
        $fields = ['client_id', 'client_secret', 'redirect_url'];

        foreach ($fields as $index => $field) {
            $value = [
                'note' => '',
                'content' => $request->get($field) ?? null,
                'page_content_type_id' => $index === 2 ? 10 : 3,
            ];

            Setting::updateOrCreate([
                'name' => $field,
                'model' => 'instagram',
            ], [
                'position' => $index,
                'value' => $value,
            ]);
        }

        return redirect()->route('refined.instagram.index')->with('status', 'Successfully updated');
    }

    public function getForFront(Request $request)
    {
        $limit = $request->get('limit') ?: 20;
        $data = $this->repository->getUserMedia($limit);

        return response()->json($data);
    }
}
