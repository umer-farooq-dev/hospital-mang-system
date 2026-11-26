{{-- Sidebar brand and dark-mode readability, for the whole software --}}
<style>
    .sidebar-logo { display: flex; align-items: center; min-width: 0; gap: .6rem; }
    .sidebar-logo .image { width: 42px; height: 42px; object-fit: contain; flex-shrink: 0; }
    .sidebar-logo .navbar-brand-name { padding: 0 !important; font-size: .95rem; font-weight: 600; line-height: 1.25;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; word-break: break-word; }
    html[data-theme=dark] .sidebar-logo .navbar-brand-name { color: #fff !important; }
    html[data-theme=dark] .aside-menu-container__aside-menu .nav-item .nav-link { color: #e9ecef; font-weight: 500; }
    html[data-theme=dark] .aside-menu-container__aside-menu .nav-item .nav-link:hover,
    html[data-theme=dark] .aside-menu-container__aside-menu .nav-item.active > .nav-link { color: #fff; font-weight: 600; }
    html[data-theme=dark] .aside-menu-container__aside-menubar,
    html[data-theme=dark] .aside-menu-container .form-control { color: #e9ecef; }
    html[data-theme=dark] .text-muted,
    html[data-theme=dark] .text-gray-600,
    html[data-theme=dark] .text-gray-400 { color: #c3c9d0 !important; }
    html[data-theme=dark] .form-control,
    html[data-theme=dark] .form-select { color: #f1f3f5; }
    html[data-theme=dark] .form-control.bg-white { background-color: #13151f !important; }
    html[data-theme=light] .form-control.bg-light { background-color: #fff !important; }
    .header-language .dropdown-item.active, .header-language .dropdown-item:active { background-color: var(--bs-primary); color: #fff; }
    /* back arrow in the header: only in the installed app, the browser has its own */
    .header .app-back-button { display: none; width: 38px; height: 38px; border-radius: 50%; align-items: center; justify-content: center;
        flex-shrink: 0; color: var(--bs-primary); background: rgba(0, 0, 0, .04); font-size: 17px; text-decoration: none; }
    .header .app-back-button:hover { background: var(--bs-primary); color: #fff; }
    @media (display-mode: standalone) { .header .app-back-button { display: inline-flex; } }
</style>
