<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="title" content="{{config('app.name')}}">

    <meta name="keywords" content="{{getCompanyName()}}"/>

    <meta name="description" content="{{getAppName()}}"/>
    <meta name="author" content="{{getCompanyName()}}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>404 | {{ getAppName() }}</title>
    <link rel="stylesheet" href="{{ mix('web_front/css/bootstrap.css') }}">
    @yield('page_css')
</head>
<body>
    <div class="container con-404 vh-100 d-flex justify-content-center">
        <div class="row justify-content-md-center d-block">
            <div class="col-md-12 mt-5">
                <img src="{{ asset('web/img/404.svg') }}" class="img-fluid img-404 mx-auto d-block">
            </div>
            <div class="col-md-12 text-center error-page-404">
                <h2>{{ __('messages.errors.not_found_title') }}</h2>
                <p class="not-found-subtitle">{{ __('messages.errors.not_found_text') }}</p>
                <a class="btn btn-success back-btn mt-3" href="{{ url()->previous() }}" >{{ __('messages.errors.back') }}</a>
            </div>
        </div>
    </div>
</body>
</html>
