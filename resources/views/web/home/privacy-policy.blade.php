@extends('web.layouts.front')
@section('title')
    {{ __('messages.web_home.privacy_policy') }}
@endsection
@section('content')
    <section class="space">
        <div class="container">
            <div class="legal-text">
                {!! $frontSetting['privacy_policy'] ?? '' !!}
            </div>
        </div>
    </section>
@endsection
