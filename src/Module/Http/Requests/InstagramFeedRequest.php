<?php

namespace RefinedDigital\Social\InstagramFeed\Module\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstagramFeedRequest extends FormRequest {
    /**
     * Determine if the service is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $args = [
            'client_id'                => ['required' => 'required'],
            'client_secret'            => ['required' => 'required'],
            'redirect_url'             => ['required' => 'required'],
        ];

        // return the results to set for validation
        return $args;
    }
}
