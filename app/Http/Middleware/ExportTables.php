<?php

namespace App\Http\Middleware;

use App\Exports\TableExport;
use App\Utils\RouteAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * "?export=xlsx" on a report screen: the screen is built as usual, then its tables (in the main content)
 * are written to an Excel file. Needs the export permission of the screen's module.
 */
class ExportTables
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->query('export') !== 'xlsx') {
            return $next($request);
        }
        abort_unless(self::allowed($request), 403);
        $request->attributes->set('exporting', true);
        $response = $next($request);
        $html = method_exists($response, 'getContent') ? (string) $response->getContent() : '';
        if ($response->getStatusCode() !== 200 || ! str_contains($html, '<table')) {
            return $response;
        }
        $title = trim(html_entity_decode(strip_tags((string) Str::of($html)->match('/<title>(.*?)<\/title>/s')), ENT_QUOTES, 'UTF-8'));
        $title = trim(explode('|', $title)[0]) ?: 'export';

        return Excel::download(new TableExport(self::rows($html, $title), $title), Str::slug($title).'-'.now()->format('Ymd-His').'.xlsx');
    }

    public static function allowed(Request $request): bool
    {
        $user = $request->user();
        $route = $request->route();
        if (! $user || ! $route) {
            return false;
        }
        $permission = RouteAccess::permission($route);
        if ($permission === null) {
            return RouteAccess::allows($user, $route);
        }

        return $user->getAllPermissions()->pluck('name')->contains($permission[0].'.export');
    }

    /** Every table of the page body (not the menus or modals), one after the other with a blank row between. */
    public static function rows(string $html, string $title): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $rows = [[$title], []];
        $tables = $xpath->query("//table[not(ancestor::*[contains(concat(' ', normalize-space(@class), ' '), ' modal ')])][not(ancestor::table)]");
        foreach ($tables as $table) {
            foreach ($xpath->query('.//tr', $table) as $tr) {
                $cells = [];
                foreach ($xpath->query('./th|./td', $tr) as $cell) {
                    if (str_contains(' '.$cell->getAttribute('class').' ', ' d-print-none ')) {
                        continue;
                    }
                    $cells[] = self::value(trim(preg_replace('/\s+/u', ' ', $cell->textContent)));
                    for ($i = 1; $i < (int) $cell->getAttribute('colspan'); $i++) {
                        $cells[] = '';
                    }
                }
                if (array_filter($cells, fn ($c) => $c !== '') !== []) {
                    $rows[] = $cells;
                }
            }
            $rows[] = [];
        }

        return $rows;
    }

    /** Amounts shown as "1 250", "1 250,50" or "-3 000 FCFA" become numbers; codes with a leading zero stay text. */
    public static function value(string $text)
    {
        // A number with an optional currency word before or after it, nothing else.
        if (! preg_match('/^([-+]?)\s*(?:[^\d\s%\/:-]{1,5}\s*)?([\d][\d\s\x{00A0}\x{202F}.,]*)(?:\s*[^\d\s%\/:-]{1,5})?$/u', $text, $m)) {
            return $text;
        }
        $number = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', $m[2]);
        if (preg_match('/^0\d/', $number)) {
            return $text; // a code, not an amount
        }
        $number = preg_match('/^(.*)[.,](\d{1,2})$/', $number, $d)
            ? str_replace([',', '.'], '', $d[1]).'.'.$d[2]
            : str_replace([',', '.'], '', $number);

        return is_numeric($number) ? (float) ($m[1].$number) : $text;
    }
}
