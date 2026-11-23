<?php

namespace App\Http\Controllers;

use App\Console\Commands\BackupDatabase;
use App\Models\Account;
use App\Models\Doctor;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\StockBatch;
use App\Models\Store;
use App\Models\User;
use App\Utils\Posting;
use App\Utils\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laracasts\Flash\Flash;

/** Go-live check: what must be right before the hospital works on the system, backups and night tasks. */
class GoLiveController extends AppBaseController
{
    // Password of the accounts made by the installer's seeders.
    const SEED_PASSWORDS = ['123456789'];

    const SETTINGS = ['backup_enabled' => '1', 'backup_time' => '01:00', 'backup_keep' => '14', 'backup_dump_path' => 'mysqldump',
        'backup_copy_path' => '', 'backup_include_files' => '0', 'accounting_auto_post' => '0', 'accounting_post_time' => '02:00'];

    public function index()
    {
        $settings = [];
        foreach (self::SETTINGS as $key => $default) {
            $settings[$key] = (string) Stock::setting($key, $default);
        }
        $backups = collect(glob(BackupDatabase::folder().DIRECTORY_SEPARATOR.'*.{sql.gz,zip}', GLOB_BRACE) ?: [])
            ->sortDesc()->take(10)->map(fn ($f) => ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)])->values();

        return view('go_live.index', ['checks' => self::checks(), 'settings' => $settings, 'backups' => $backups,
            'lastError' => (string) Stock::setting('backup_last_error', '')]);
    }

    /** [group => [[status ok|warn|fail|info, label, detail]]] */
    public static function checks(): array
    {
        $t = fn ($key, $replace = []) => __('messages.go_live.checks.'.$key, $replace);
        $row = fn (bool|string $ok, string $key, array $replace = [], string $bad = 'fail') => [is_string($ok) ? $ok : ($ok ? 'ok' : $bad), $t($key, $replace)];

        $admins = User::role('Admin')->limit(20)->get();
        $weak = $admins->filter(fn ($u) => collect(self::SEED_PASSWORDS)->contains(fn ($p) => Hash::check($p, (string) $u->password)));
        $seen = (int) Cache::get('scheduler_last_seen', 0);
        $lastBackup = Stock::setting('backup_last_run');
        $today = now()->toDateString();
        $year = FiscalYear::whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->first();
        $postable = Account::where('is_postable', true)->where('status', 1)->pluck('code')->filter()->flip();
        $chosen = Posting::accounts();
        $missing = collect(Posting::accountKeys())->filter(function ($label, $key) use ($chosen, $postable) {
            $code = $chosen[$key] ?? Posting::defaultCode($key);

            return ! str_starts_with($key, 'counter.') && ($code === null || ! isset($postable[$code]));
        });
        $mail = config('mail.default');

        return [
            'server' => [
                $row(app()->environment('production'), 'production', ['env' => app()->environment()]),
                $row(! config('app.debug'), 'debug_off'),
                $row(str_starts_with((string) config('app.url'), 'https://'), 'https', ['url' => config('app.url')], 'warn'),
                $row(! in_array($mail, ['log', 'array'], true), 'mail', ['mailer' => $mail], 'warn'),
                $row(file_exists(public_path('storage')), 'storage_link'),
                $row(is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')), 'writable'),
                $row($seen > now()->subMinutes(5)->timestamp, 'scheduler', ['time' => $seen ? date('d/m/Y H:i', $seen) : '—']),
                $row(Stock::setting('backup_enabled', '1') === '1' && $lastBackup && strtotime($lastBackup) > now()->subHours(26)->timestamp,
                    'backup', ['time' => $lastBackup ?: '—']),
            ],
            'security' => [
                $row($weak->isEmpty(), 'seed_password', ['users' => $weak->isEmpty() ? '' : '('.$weak->pluck('email')->implode(', ').')']),
                $row(! User::where('email', 'admin@hms.com')->exists(), 'seed_email', [], 'warn'),
            ],
            'setup' => [
                ['info', $t('hospital', ['name' => getAppName(), 'currency' => strtoupper((string) Stock::setting('current_currency', '')), 'email' => (string) Stock::setting('hospital_email', '')])],
                $row((bool) $year && $year->status === 'open', 'fiscal_year', ['year' => $year->name ?? '—']),
                $row($missing->isEmpty(), 'posting', ['list' => $missing->isEmpty() ? '' : '('.$missing->take(8)->implode(', ').')'], 'warn'),
                $row(Stock::setting('accounting_auto_post', '0') === '1', 'auto_post', [], 'warn'),
                $row(Store::where('is_active', true)->exists(), 'stores'),
            ],
            'data' => [
                ['info', $t('counts', ['patients' => Patient::count(), 'doctors' => Doctor::count(), 'medicines' => Medicine::count(),
                    'batches' => StockBatch::where('quantity', '>', 0)->count()])],
                $row(! $year || JournalEntry::where('fiscal_year_id', $year->id)->where('source_event', 'opening')->exists(), 'opening', [], 'warn'),
            ],
        ];
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'backup_time' => ['required', 'date_format:H:i'], 'accounting_post_time' => ['required', 'date_format:H:i'],
            'backup_keep' => ['required', 'integer', 'min:1', 'max:365'], 'backup_dump_path' => ['required', 'string', 'max:255'],
            'backup_copy_path' => ['nullable', 'string', 'max:255'],
        ]);
        foreach (['backup_enabled', 'backup_include_files', 'accounting_auto_post'] as $flag) {
            $data[$flag] = $request->boolean($flag) ? '1' : '0';
        }
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }
        Flash::success(__('messages.go_live.saved'));

        return redirect()->route('go-live.index');
    }

    /** Back up now. */
    public function storeBackup()
    {
        @set_time_limit(0);
        $code = Artisan::call('db:backup');
        $code === 0 ? Flash::success(trim(Artisan::output())) : Flash::error(trim(Artisan::output()));

        return redirect()->route('go-live.index');
    }

    /** Download one backup (to keep it off the server). */
    public function exportBackup(string $name)
    {
        abort_unless(preg_match('/^[\w.-]+\.(sql\.gz|zip)$/', $name), 404);
        $path = BackupDatabase::folder().DIRECTORY_SEPARATOR.$name;
        abort_unless(is_file($path), 404);

        return response()->download($path);
    }
}
