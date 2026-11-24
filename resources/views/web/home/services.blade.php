@extends('web.layouts.front')
@php
    use App\Models\FrontService;
    $route = [FrontService::SERVICE => 'service-details', FrontService::CASE_STUDY => 'case-studies', FrontService::POST => 'news'][$type];
    $link = fn ($row) => route($route, $row->id);
    $fallback = [
        FrontService::SERVICE => ['service/serD-img1.jpg', 'service/ser-img-h3.jpg', 'service/ser-img1-h3.jpg', 'service/ser-img2-h3.jpg'],
        FrontService::CASE_STUDY => ['doctors/case-h1-img1.jpg', 'doctors/case-h1-img2.jpg', 'doctors/case-h1-img4.jpg', 'doctors/caseDimg.jpg'],
        FrontService::POST => ['blog/blog-img1.jpg', 'blog/blog-img2.jpg', 'blog/blog-h1-img1.jpg', 'blog/blog-h1-img2.jpg'],
    ][$type];
    $picture = fn ($row, $i) => $row->image_url ?: asset('web_front/kliniko/img/'.$fallback[$i % count($fallback)]);
    $icons = ['fa-stethoscope', 'fa-truck-medical', 'fa-person-pregnant', 'fa-flask-vial', 'fa-x-ray', 'fa-syringe'];
    $listTitle = $type === FrontService::SERVICE ? __('messages.web_home.services') : __('messages.front_web.type_'.$type);
    $pageTitle = $item->name ?? $listTitle;
    // theme pagination for the list pages
    $pager = function () use ($frontServices) {
        if ($frontServices->lastPage() < 2) {
            return '';
        }
        $html = '<div class="vs-pagination">';
        $html .= $frontServices->onFirstPage() ? '' : '<a href="'.e($frontServices->previousPageUrl()).'" class="pagi-btn" aria-label="previous"><i class="fa-solid fa-arrow-left"></i></a>';
        $html .= '<ul>';
        foreach ($frontServices->getUrlRange(1, $frontServices->lastPage()) as $page => $url) {
            $html .= '<li><a href="'.e($url).'"'.($page == $frontServices->currentPage() ? ' class="active"' : '').'>'.$page.'</a></li>';
        }
        $html .= '</ul>';
        $html .= $frontServices->hasMorePages() ? '<a href="'.e($frontServices->nextPageUrl()).'" class="pagi-btn" aria-label="next"><i class="fa-solid fa-arrow-right"></i></a>' : '';

        return $html.'</div>';
    };
@endphp
@section('title')
    {{ $pageTitle }}
@endsection
@section('content')
    @if ($item)
        <section class="vs-service service-Dpage z-index-common space">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="vs-service__wrap">
                            <div class="vs-service__img">
                                <img src="{{ $picture($item, 0) }}" alt="{{ $item->name }}">
                            </div>
                            <div class="vs-service__Dtxt">
                                @if ($item->category || $item->published_at)
                                    <p class="content-meta">
                                        @if ($item->published_at)<span><i class="fa-regular fa-calendar"></i> {{ $item->published_at->translatedFormat('d F Y') }}</span>@endif
                                        @if ($item->category)<span><i class="fa-solid fa-tag"></i> {{ $item->category }}</span>@endif
                                    </p>
                                @endif
                                <h2>{{ $item->name }}</h2>
                                {!! $item->body_html !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        @if ($others->isNotEmpty())
                            <div class="widget service-widget mt-40 mt-lg-0">
                                <h3>{{ $listTitle }}</h3>
                                <nav class="services-nav">
                                    @foreach ($others as $other)
                                        <a class="nav-item" href="{{ $link($other) }}"><i class="fa-solid fa-angles-right"></i> {{ $other->name }}</a>
                                    @endforeach
                                </nav>
                            </div>
                        @endif
                        <div class="widget newsletter-widget">
                            <h3>{{ __('messages.web_home.book_appointment') }}</h3>
                            <p>{{ frontSetting('appointment_description') }}</p>
                            <a href="{{ route('appointment') }}" class="vsBtn">{{ __('messages.web_home.book_appointment') }}<span></span></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @elseif ($type === FrontService::SERVICE)
        <section class="vs-service service-page z-index-common space space-extra-bottom">
            <div class="container">
                <div class="row justify-content-center">
                    @foreach ($frontServices as $i => $service)
                        <div class="col-lg-4 col-md-6">
                            <div class="vs-service__wrap wow animate__fadeInUp" data-wow-delay="0.{{ $i % 3 + 2 }}s">
                                <div class="vs-service__icon">
                                    <div class="ser_icon">
                                        @if ($service->icon_url)
                                            <img src="{{ $service->icon_url }}" alt="">
                                        @else
                                            <i class="fa-solid {{ $icons[$i % 6] }}"></i>
                                        @endif
                                    </div>
                                    <span class="ser_number">{{ sprintf('%02d', $frontServices->firstItem() + $i) }}</span>
                                </div>
                                <div class="vs-service__txt">
                                    <a href="{{ $link($service) }}"><h2>{{ $service->name }}</h2></a>
                                    <p>{{ $service->short_description }}</p>
                                    <a href="{{ $link($service) }}" class="ser_btn">{{ __('messages.web_front.read_more') }}</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                {!! $pager() !!}
            </div>
        </section>
    @elseif ($type === FrontService::CASE_STUDY)
        <div class="vs-case z-index-common space">
            <div class="container">
                <div class="vs-case__casePage">
                    @foreach ($frontServices as $i => $case)
                        <div class="vs-case__wrap wow animate__fadeInUp" data-wow-delay="0.{{ $i % 2 + 2 }}s">
                            <div class="vs-case__img">
                                <div class="case_img">
                                    <a href="{{ $link($case) }}"><img src="{{ $picture($case, $i) }}" alt="{{ $case->name }}" loading="lazy"></a>
                                </div>
                                @if ($case->category)<span class="label">{{ $case->category }}</span>@endif
                            </div>
                            <div class="case_txt">
                                <a href="{{ $link($case) }}" class="case-link">
                                    <span>{{ $case->name }}</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
                {!! $pager() !!}
            </div>
        </div>
    @else
        <section class="vs-blog z-index-common space">
            <div class="container">
                <div class="row">
                    @foreach ($frontServices as $i => $post)
                        <div class="col-lg-4 col-md-6 mb-40">
                            <div class="vs-blog__wrap wow animate__fadeInUp" data-wow-delay="0.2s">
                                <div class="blog_img">
                                    <a href="{{ $link($post) }}"><img src="{{ $picture($post, $i) }}" alt="{{ $post->name }}" loading="lazy"></a>
                                    @if ($post->published_at)
                                        <p class="bdate style2"><span>{{ $post->published_at->format('d') }}</span> {{ $post->published_at->translatedFormat('M') }}</p>
                                    @endif
                                </div>
                                <div class="blog_content">
                                    <div class="blog-meta">
                                        <span><i class="fa-solid fa-hospital"></i> {{ getAppName() }}</span>
                                        @if ($post->category)<span><i class="fa-solid fa-tag"></i> {{ $post->category }}</span>@endif
                                    </div>
                                    <div class="blog-txt">
                                        <a href="{{ $link($post) }}"><h3>{{ $post->name }}</h3></a>
                                        <p>{{ $post->short_description }}</p>
                                        <div class="blog-btn">
                                            <a class="readMore" href="{{ $link($post) }}">{{ __('messages.web_front.read_more') }}</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                {!! $pager() !!}
            </div>
        </section>
    @endif
@endsection
