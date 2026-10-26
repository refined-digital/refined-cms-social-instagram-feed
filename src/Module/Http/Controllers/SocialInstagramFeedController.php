<?php

namespace RefinedDigital\Social\InstagramFeed\Module\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use RefinedDigital\CMS\Modules\Core\Enums\PageContentType;
use RefinedDigital\CMS\Modules\Settings\Models\Setting;
use RefinedDigital\Social\InstagramFeed\Module\Http\Repositories\InstagramFeedRepository;
use RefinedDigital\Social\InstagramFeed\Module\Http\Requests\InstagramFeedRequest;

class SocialInstagramFeedController
{
    /**
     * The settings form, or the oauth callback when instagram redirects back with a code.
     */
    public function index(Request $request, InstagramFeedRepository $repository): View|RedirectResponse
    {
        if ($request->filled('code')) {
            $code = str_replace('#_', '', (string) $request->get('code'));

            return $repository->exchangeCodeForToken($code);
        }

        $settings = settings()->get('instagram');

        $settings = [
            'client_id' => old('client_id', $settings->client_id->value ?? null),
            'client_secret' => old('client_secret', $settings->client_secret->value ?? null),
            'redirect_url' => old('redirect_url', $settings->redirect_url->value ?? $request->url()),
        ];

        return view('instagram::index')
            ->with('repo', $repository)
            ->with(compact('settings'));
    }

    /**
     * Stores the app credentials as settings rows under the "instagram" model.
     */
    public function update(InstagramFeedRequest $request): RedirectResponse
    {
        $fields = [
            'client_id' => PageContentType::PLAIN,
            'client_secret' => PageContentType::PASSWORD,
            'redirect_url' => PageContentType::PLAIN,
        ];

        $position = 0;
        foreach ($fields as $field => $type) {
            Setting::updateOrCreate([
                'name' => $field,
                'model' => 'instagram',
            ], [
                'position' => $position++,
                'value' => [
                    'note' => '',
                    'content' => $request->validated($field),
                    'page_content_type_id' => $type->value,
                ],
            ]);
        }

        // settings()->get() caches per model, so drop it or the form shows the old values
        Cache::forget('settings-instagram');
        Cache::forget('settings-all-instagram');

        return redirect()->route('refined.instagram.index')->with('status', 'Successfully updated');
    }

    /**
     * The public json feed used by the front end script.
     */
    public function getForFront(Request $request, InstagramFeedRepository $repository): JsonResponse
    {
        $limit = max(1, min(100, (int) ($request->get('limit') ?: 20)));

        return response()->json($repository->getUserMedia($limit));
    }
}
