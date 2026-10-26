@extends('core::layouts.master')

@section('title', 'Instagram Feed Settings')

@section('template')
    <div class="app__content-header">
        <h2><a href="{{ route('refined.instagram.index') }}">Instagram Feed</a></h2>
        <aside>
            <a href="#" class="button button--blue" @click.prevent.stop="submitForm('save')">Save</a>
        </aside>
    </div>

    {!!
        html()
            ->form('PATCH', route('refined.instagram.update'))
            ->attributes([
                'id' => 'model-form',
                'novalidate',
            ])
            ->open()
    !!}
        <div class="tab__pane">
            <div class="tab__groups">
                <div class="tab__left">
                    <div class="block">
                        <header>
                            <h3>Instagram Settings</h3>
                        </header>
                        <div class="form__group form__group--3">
                            <div class="form__row form__row--required">
                                <label for="form--client-id" class="form__label">Client ID</label>
                                {!!
                                    html()
                                        ->text('client_id', $settings['client_id'])
                                        ->class('form__control')
                                        ->id('form--client-id')
                                        ->required()
                                !!}
                            </div>

                            <div class="form__row form__row--required">
                                <label for="form--client-secret" class="form__label">Client Secret</label>
                                {!!
                                    html()
                                        ->input('password', 'client_secret', $settings['client_secret'])
                                        ->class('form__control')
                                        ->id('form--client-secret')
                                        ->attribute('autocomplete', 'off')
                                        ->required()
                                !!}
                            </div>

                            <div class="form__row form__row--required">
                                <label for="form--redirect-url" class="form__label">Redirect Url</label>
                                {!!
                                    html()
                                        ->text('redirect_url', $settings['redirect_url'])
                                        ->class('form__control')
                                        ->id('form--redirect-url')
                                        ->required()
                                !!}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab__right">
                    <div class="block">
                        <header>
                            <h3>Status</h3>
                        </header>
                        <div class="form__row">
                            @if ($repo->isConnected())
                                Connected
                            @elseif ($settings['client_id'])
                                <a href="{{ $repo->getAuthorizeLink() }}" class="button button--small button--green">Connect</a>
                            @else
                                Save the Client ID and Secret, then connect the Instagram account.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="action" value="save" id="form--submit"/>
    {!! html()->form()->close() !!}
@stop
