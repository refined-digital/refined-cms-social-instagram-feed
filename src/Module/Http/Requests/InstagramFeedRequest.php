<?php

namespace RefinedDigital\Social\InstagramFeed\Module\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstagramFeedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['required', 'string', 'max:255'],
            'redirect_url' => ['required', 'url', 'max:255'],
        ];
    }
}
