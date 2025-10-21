@extends('core::layouts.master')

@section('title', 'Instagram Feed Settings')

@section('template')
    <div class="pages settings">
        <section class="pages__content">
            <div class="pages__header"><h3>Instagram Feed</h3>
                <aside><a href="#" class="button button--blue button--small">Save</a></aside>
            </div>
            <div class="pages__info">
                <div class="form">
                    {!!
                        html()
                            ->form('PATCH', route('refined.instagram.update'))
                            ->attributes([
                                'id' => 'model-form',
                                'novalidate'
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
                                                        ->input('text', 'client_id')
                                                        ->class('form__control')
                                                        ->id('form--client-id')
                                                        ->value($settings['client_id'] ?? old('client_id'))
                                                        ->attributes([
                                                            'required' => true
                                                    ])
                                                !!}
                                            </div>

                                            <div class="form__row form__row--required"><label for="form--client-secret" class="form__label">Client Secret</label>
                                                {!!
                                                    html()
                                                        ->input('password', 'client_secret')
                                                        ->class('form__control')
                                                        ->id('form--client-secret')
                                                        ->value($settings['client_secret'] ?? old('client_secret'))
                                                        ->attributes([
                                                            'required' => true
                                                    ])
                                                !!}
                                            </div>

                                            <div class="form__row form__row--required"><label for="form--redirect-url" class="form__label">Redirect Url</label>
                                                {!!
                                                    html()
                                                        ->input('text', 'redirect_url')
                                                        ->class('form__control')
                                                        ->id('form--redirect-url')
                                                        ->value($settings['redirect_url'] ?? old('redirect_url'))
                                                        ->attributes([
                                                            'required' => true
                                                    ])
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
                                            @if (file_exists($repo->getTokenFile()))
                                                Connected
                                            @else
                                                @php
                                                    $link = $repo->getAuthorizeLink();
                                                @endphp
                                                <a href="{{ $link }}" class="button button--small button--green">Connect</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="action" value="save" id="form--submit"/>
                    {!! html()->closeModelForm() !!}
                </div>
            </div>
        </section>
    </div>
@stop


@section('scripts')
    <script>
        var buttons = document.querySelectorAll('.pages__content .pages__header aside .button--blue');
        if (buttons.length) {
            buttons.forEach(button => {
                button.addEventListener('click', e => {
                    console.log('button has been clicked');
                    e.preventDefault();
                    window.app.submitForm('save');
                })
            })
        }
    </script>
@append
