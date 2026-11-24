<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Repositories\SettingRepository;
use Symfony\Component\HttpFoundation\Response;

class SetLanguage
{
    /**
     * use Illuminate\Support\Facades\Session;
     */
    public function handle(Request $request, Closure $next): Response
    {
        // the public website speaks the languages chosen in Front CMS (default first)
        $languages = websiteLanguages();
        $chosen = \Session::get('languageName');
        \App::setLocale(in_array($chosen, $languages, true) ? $chosen : $languages[0]);

        return $next($request);
    }
}
