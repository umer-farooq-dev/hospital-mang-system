@extends('web.layouts.front')
@section('title')
    {{ __('messages.web_home.terms_of_service') }}
@endsection
@section('content')
    <section class="space">
        <div class="container">
            <div class="legal-text">
                {!! $frontSetting['terms_conditions'] ?? '' !!}
            </div>
        </div>
    </section>
@endsection
