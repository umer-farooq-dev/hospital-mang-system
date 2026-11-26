{{-- Hospital brand on every page: favicon, installable app (desktop icon) and, for the software, its colour --}}
<link rel="icon" href="{{ brandFavicon() }}" type="image/png">
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<link rel="apple-touch-icon" href="{{ route('pwa.icon', 192) }}">
<meta name="theme-color" content="{{ frontSetting('theme_color_primary', '#009E49') }}">
<meta name="application-name" content="{{ getAppName() }}">
@if (empty($website) && ($brandCss = \App\Utils\Brand::stylesheet()))
    <link rel="stylesheet" href="{{ $brandCss }}">
@endif
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () { navigator.serviceWorker.register('{{ asset('sw.js') }}').catch(function () {}); });
    }
</script>
