@php
    $notifications = getNotification(Auth::user()->roles->pluck('name')->first());
    $notificationCount = count($notifications);
@endphp
<header class='d-flex align-items-center justify-content-between flex-grow-1 header px-3 px-xl-0'>
    @include('layouts.back_button', ['fallback' => url('/home'), 'class' => 'me-3'])
    <button type="button" class="btn px-0 aside-menu-container__aside-menubar d-block d-xl-none sidebar-btn ">
        <i class="fa-solid fa-bars fs-1"></i>
    </button>
    <nav class="navbar navbar-expand-xl navbar-light top-navbar d-xl-flex d-block px-3 px-xl-0 py-4 py-xl-0"
        id="nav-header">
        <div class="container-fluid pe-0">
            <div class="navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @include('layouts.sub_menu')
                </ul>
            </div>
        </div>
    </nav>
    <ul class="nav align-items-center flex-nowrap">
        @role('Patient')
            @php
                $patientUniqueId = Auth::user()->patient->patient_unique_id;
                $isPatientIdCard = Auth::user()->patient->template_id;
            @endphp
            @if (!is_null($isPatientIdCard))
                <li class="px-xxl-3 px-2">
                    <div class="card-toolbar" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-trigger="hover"
                        title="" data-bs-original-title="Patient Smart Card">
                        <button type="button" class="btn px-1 text-primary fs-3 ShowPatientCardData"
                            data-id="{{ $patientUniqueId }}" data-bs-toggle="modal"
                            data-bs-target="#ShowPatientCardDataModal">
                            <i class="fa-solid fa-id-card fs-1 text-decoration-none"></i>
                        </button>
                    </div>
                </li>
            @endif
        @endrole
        @php $headerLanguages = getLanguages(); @endphp
        @php $headerLanguage = getCurrentLoginUserLanguageName(); @endphp
        @if (count($headerLanguages) > 1)
            <li class="px-xxl-3 px-2 header-language">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light d-flex align-items-center gap-2 px-3" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false" title="{{ __('messages.profile.change_language') }}">
                        @isset(\App\Models\User::LANGUAGES_IMAGE[$headerLanguage])
                            <img src="{{ asset(\App\Models\User::LANGUAGES_IMAGE[$headerLanguage]) }}" alt="" width="20" height="14" class="rounded-1">
                        @endisset
                        <span class="fw-bold text-uppercase">{{ $headerLanguage }}</span>
                        <i class="fa-solid fa-chevron-down fs-7"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end py-2">
                        @foreach ($headerLanguages as $code => $label)
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 py-2 {{ $code == $headerLanguage ? 'active' : '' }}"
                                    href="javascript:void(0)" data-header-language="{{ $code }}">
                                    @isset(\App\Models\User::LANGUAGES_IMAGE[$code])
                                        <img src="{{ asset(\App\Models\User::LANGUAGES_IMAGE[$code]) }}" alt="" width="20" height="14" class="rounded-1">
                                    @endisset
                                    {{ __('messages.languages.'.$code) !== 'messages.languages.'.$code ? __('messages.languages.'.$code) : $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </li>
        @endif
        <li class="px-xxl-3 px-2">
            <button type="button" class="btn p-0 border-0 bg-transparent" id="themeModeSwitch"
                data-light-title="{{ __('messages.theme.to_dark') }}" data-dark-title="{{ __('messages.theme.to_light') }}"
                title="{{ getLoggedInUser()->thememode ? __('messages.theme.to_light') : __('messages.theme.to_dark') }}">
                <i class="fas user-check-icon text-primary {{ getLoggedInUser()->thememode ? 'fa-sun' : 'fa-moon' }} fs-3"></i>
            </button>
            <script>
                (function () {
                    var token = document.querySelector('meta[name="csrf-token"]').content;
                    // language: saved on the user, page reloads in the new language
                    document.querySelectorAll('[data-header-language]').forEach(function (item) {
                        item.addEventListener('click', function () {
                            fetch('{{ url('update-language') }}', {
                                method: 'POST',
                                headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded'},
                                body: new URLSearchParams({language: item.dataset.headerLanguage})
                            }).then(function () { location.reload(); });
                        });
                    });
                    // light / dark: swap the stylesheets in place, then save the choice
                    var button = document.getElementById('themeModeSwitch');
                    button.addEventListener('click', function () {
                        var dark = document.documentElement.dataset.theme !== 'dark';
                        document.querySelectorAll('link[data-dark]').forEach(function (link) {
                            var next = link.cloneNode();
                            next.href = dark ? link.dataset.dark : link.dataset.light;
                            next.onload = function () { link.remove(); };
                            link.after(next);
                        });
                        document.querySelectorAll('link[data-dark-only]').forEach(function (link) { link.media = dark ? 'all' : 'not all'; });
                        document.documentElement.dataset.theme = dark ? 'dark' : 'light';
                        button.querySelector('i').classList.toggle('fa-sun', dark);
                        button.querySelector('i').classList.toggle('fa-moon', !dark);
                        button.title = dark ? button.dataset.darkTitle : button.dataset.lightTitle;
                        fetch('{{ route('user.mode') }}', {
                            method: 'POST',
                            headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded'},
                            body: new URLSearchParams({dark: dark ? 1 : 0})
                        });
                    });
                })();
            </script>
        </li>
        <li class="px-sm-3 px-2">
            <div class="dropdown custom-dropdown d-flex align-items-center py-4">
                <button class="btn hide-arrow p-0 position-relative" type="button" id="dropdownMenuButton1"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-bell text-primary fs-2"></i>
                    @if ($notificationCount != 0)
                        <span
                            class="position-absolute top-0 start-100 translate-middle badge badge-circle bg-info {{ $notificationCount > 9 ? 'end-1' : 'counter-0' }}"
                            id="counter">{{ $notificationCount }}</span>
                    @endif
                </button>
                <div class="dropdown-menu py-0 my-2" aria-labelledby="dropdownMenuButton1">
                    <div
                        class="{{ getCurrentLoginUserLanguageName() == 'ar' ? 'text-end' : 'text-start' }} border-bottom py-4 px-7">
                        <h3 class="text-gray-900 mb-0">{{ __('messages.notification.notifications') }}</h3>
                    </div>
                    <div class="px-7 mt-5 inner-scroll height-270">
                        @if ($notificationCount > 0)
                            @foreach ($notifications as $notification)
                                <a href="javascript:void(0)" data-id="{{ $notification->id }}"
                                    class="notification d-flex position-relative mb-5  d-flex position-relative mb-5 text-decoration-none text-hover-primary"
                                    id="notification">
                                    <span
                                        class="{{ getCurrentLoginUserLanguageName() == 'ar' ? 'ms-5' : 'me-5' }} text-primary fs-2 icon-label">
                                        <i class="{{ getNotificationIcon($notification->type) }}"></i>
                                    </span>
                                    <div>
                                        <h5
                                            class="text-gray-900 fs-6 mb-2 {{ getCurrentLoginUserLanguageName() == 'ar' ? 'text-end' : '' }}">
                                            {{ $notification->title }}</h5>
                                        <h6
                                            class="text-gray-600 fs-small fw-light mb-0 {{ getCurrentLoginUserLanguageName() == 'ar' ? 'text-end' : '' }}">
                                            {{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans(null, true) }}
                                        </h6>
                                    </div>
                                </a>
                            @endforeach
                        @else
                            <div>
                                <h5 class="text-gray-900 fs-6 mb-2 empty-state text-center">
                                    {{ __('messages.notification.you_don`t_have_any_new_notification') }}</h5>
                            </div>
                        @endif
                        <div>
                            <h5 class="text-gray-900 text-center fs-6 mb-2 empty-state empty-notification d-none">
                                {{ __('messages.notification.you_don`t_have_any_new_notification') }}</h5>
                        </div>
                    </div>
                    @if ($notificationCount > 0)
                        <div class="text-center border-top p-4 mark-read">
                            <h5><a href="#"
                                    class="text-primary mb-0 fs-5 read-all-notification text-decoration-none"
                                    id="readAllNotification">{{ __('messages.notification.mark_all_as_read') }}</a>
                            </h5>
                        </div>
                    @endif
                </div>
            </div>
        </li>
        <li class="px-2 d-none" id="installAppItem">
            <button type="button" class="btn btn-sm btn-light-primary my-4 text-nowrap" id="installAppButton">
                <i class="fas fa-desktop me-1"></i>{{ __('messages.pwa.install') }}
            </button>
            <script>
                // Chrome/Edge: "Install" puts the system on the desktop and opens it in its own window
                window.addEventListener('beforeinstallprompt', function (event) {
                    event.preventDefault();
                    window.hmsInstall = event;
                    document.getElementById('installAppItem').classList.remove('d-none');
                });
                document.getElementById('installAppButton').addEventListener('click', function () {
                    if (!window.hmsInstall) return;
                    window.hmsInstall.prompt();
                    window.hmsInstall.userChoice.finally(function () { document.getElementById('installAppItem').classList.add('d-none'); });
                });
            </script>
        </li>
        <li class="px-xxl-3 px-2">
            <div class="dropdown d-flex align-items-center py-4">
                <div class="image image-circle image-mini">
                    <img src="{{ Auth::user()->image_url ?? '' }}" class="img-fluid" alt="profile image">
                </div>
                <button class="btn ps-2 pe-0 text-gray-600" type="button" id="dropdownMenuButton1"
                    data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                    {{ Auth::user()->full_name ?? '' }}
                    <i class="fa-solid fa-angle-down"></i>
                </button>
                <div class="dropdown-menu py-7 pb-4 my-2" aria-labelledby="dropdownMenuButton1">
                    <div class="text-center border-bottom pb-5">
                        <div class="image image-circle image-tiny mb-5">
                            <img src="{{ Auth::user()->image_url ?? '' }}" class="img-fluid"
                                alt="{{ Auth::user()->full_name ?? '' }}" id="loginUserImage">
                        </div>
                        <h3 class="text-gray-900">{{ Auth::user()->full_name ?? '' }}</h3>
                        <a href="javascript:void(0)"
                            class="mb-0 fw-400 fs-6 text-decoration-none text-dark">{{ Auth::user()->email ?? '' }}</a>
                    </div>
                    <ul class="pt-4 admin-ul">
                        <li>
                            <a class="dropdown-item text-gray-900 editProfile" href="javascript:void(0)"
                                data-bs-toggle="modal" data-bs-target="#editProfileModal"
                                data-id="{{ getLoggedInUserId() }}">
                                <span
                                    class="dropdown-icon text-gray-600 {{ getCurrentLoginUserLanguageName() == 'ar' ? 'ms-4' : 'me-4' }}">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                {{ __('messages.user.edit_profile') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-gray-900" href="javascript:void(0)"
                                data-id="{{ getLoggedInUserId() }}" data-bs-toggle="modal"
                                data-bs-target="#changePasswordModal">
                                <span
                                    class="dropdown-icon {{ getCurrentLoginUserLanguageName() == 'ar' ? 'ms-4' : 'me-4' }} text-gray-600">
                                    <i class="fa-solid fa-lock"></i>
                                </span>
                                {{ __('messages.user.change_password') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-gray-900" href="javascript:void(0)"
                                data-id="{{ getLoggedInUserId() }}" data-bs-toggle="modal"
                                data-bs-target="#changeLanguageModal">
                                <span
                                    class="dropdown-icon {{ getCurrentLoginUserLanguageName() == 'ar' ? 'ms-4' : 'me-4' }} text-gray-600">
                                    <i class="fa-solid fa-globe"></i>
                                </span>
                                {{ __('messages.profile.change_language') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-gray-900" href="{{ route('enable-2fa') }}">
                                <span
                                    class="dropdown-icon {{ getCurrentLoginUserLanguageName() == 'ar' ? 'ms-4' : 'me-4' }} text-gray-600">
                                    <i class="fa-solid fa-user-secret"></i>
                                </span>
                                {{ __('messages.two_factor_auth.two_factor_authentication') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-gray-900" href="{{ route('logout.user') }}"
                                onclick="event.preventDefault(); localStorage.clear();  document.getElementById('logout-form').submit();">
                                <span
                                    class="dropdown-icon {{ getCurrentLoginUserLanguageName() == 'ar' ? 'ms-4' : 'me-4' }} text-gray-600">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                </span>
                                {{ __('messages.user.logout') }}
                                <form id="logout-form" action="{{ route('logout.user') }}" method="POST"
                                    class="d-none">
                                    {{ csrf_field() }}
                                </form>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </li>
        <li>
            <button type="button" class="btn px-0 d-block d-xl-none header-btn pb-2">
                <i class="fa-solid fa-bars fs-1"></i>
            </button>
        </li>
    </ul>
</header>
<div class="bg-overlay" id="nav-overly"></div>
