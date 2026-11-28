<?php

namespace App\Http\Controllers;

use App\Models\DataImportRun;
use App\Models\Journal;
use App\Models\LabImportProfile;
use App\Models\Store;
use App\Utils\DataImport;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laracasts\Flash\Flash;

/** Go-live import: choose what the file holds, check it (nothing written), then import it. */
class DataImportController extends AppBaseController
{
    const SESSION = 'data_import_file';

    public function index(Request $request)
    {
        $kind = in_array($request->input('kind'), DataImport::KINDS, true) ? $request->input('kind') : DataImport::KINDS[0];
        $pending = session(self::SESSION);

        return view('data_import.index', [
            'kind' => $kind,
            'fields' => DataImport::fields($kind),
            'required' => DataImport::REQUIRED[$kind],
            'stores' => Store::where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all(),
            'journals' => Journal::where('is_active', true)->orderBy('code')->get()->mapWithKeys(fn ($j) => [$j->code => $j->code.' '.$j->name])->all(),
            'preview' => $pending && $pending['kind'] === $kind ? session('data_import_preview') : null,
            'pending' => $pending && $pending['kind'] === $kind ? $pending : null,
            'runs' => DataImportRun::with('user')->latest('id')->limit(20)->get(),
        ]);
    }

    /** Upload and check the file; the file is kept until it is imported or replaced. */
    public function storePreview(Request $request)
    {
        $options = $request->validate([
            'kind' => ['required', 'in:'.implode(',', DataImport::KINDS)],
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:51200'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'prefix' => ['nullable', 'regex:/^\+?\d{1,4}$/'],
            'date' => ['nullable', 'required_if:kind,opening_balances', 'date'],
            'journal' => ['nullable', 'exists:journals,code'],
        ]);
        $this->forgetFile();
        $file = $request->file('file');
        $path = $file->storeAs('imports', uniqid('import_', true).'.'.strtolower($file->getClientOriginalExtension()));
        unset($options['file']);
        session([self::SESSION => ['path' => $path, 'name' => $file->getClientOriginalName(), 'kind' => $options['kind'], 'options' => $options]]);
        $preview = (new DataImport($options['kind'], $options))->preview(self::rows($path, $file->getClientOriginalName(), $options['kind']));

        return redirect()->route('data-import.index', ['kind' => $options['kind']])->with('data_import_preview', $preview);
    }

    /** Import the checked file. */
    public function store(Request $request)
    {
        $pending = session(self::SESSION);
        if (! $pending || ! Storage::exists($pending['path'])) {
            Flash::error(__('messages.data_import.no_file'));

            return redirect()->route('data-import.index');
        }
        $run = DataImportRun::create(['kind' => $pending['kind'], 'file_name' => mb_substr($pending['name'], 0, 255), 'user_id' => auth()->id(), 'options' => $pending['options']]);
        $run = (new DataImport($pending['kind'], $pending['options']))->run(self::rows($pending['path'], $pending['name'], $pending['kind']), $run);
        $this->forgetFile();
        $message = __('messages.data_import.done', ['created' => $run->created, 'updated' => $run->updated, 'existing' => $run->existing, 'failed' => $run->failed]);
        $run->failed ? Flash::warning($message) : Flash::success($message);

        return redirect()->route('data-import.index', ['kind' => $run->kind]);
    }

    /** Empty file with the column titles, in the current language. */
    public function template(string $kind)
    {
        abort_unless(in_array($kind, DataImport::KINDS, true), 404);
        $separator = app()->getLocale() === 'en' ? ',' : ';';
        $line = implode($separator, array_map(fn ($t) => '"'.str_replace('"', '""', $t).'"', DataImport::template($kind)));

        return response("\xEF\xBB\xBF".$line."\r\n", 200, ['Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$kind.'.csv"']);
    }

    /** Every template in one ZIP, with a guide (order of the files, accepted values), in the current language. */
    public function templatePack()
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'tpl');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::OVERWRITE);
        $separator = app()->getLocale() === 'en' ? ',' : ';';
        $guide = [__('messages.data_import.pack_intro'), ''];
        foreach (DataImport::KINDS as $i => $kind) {
            $name = sprintf('%02d_%s.csv', $i + 1, $kind);
            $zip->addFromString($name, "\xEF\xBB\xBF".implode($separator, array_map(fn ($t) => '"'.str_replace('"', '""', $t).'"', DataImport::template($kind)))."\r\n");
            $required = array_map(fn ($f) => DataImport::label($f), DataImport::REQUIRED[$kind]);
            $guide[] = $name.' - '.__('messages.data_import.kinds.'.$kind);
            $guide[] = '  '.__('messages.data_import.hints.'.$kind);
            $guide[] = '  '.__('messages.data_import.required').' '.implode(', ', $required);
            $guide[] = '';
        }
        $guide[] = __('messages.data_import.pack_values');
        $zip->addFromString(__('messages.data_import.pack_guide').'.txt', "\xEF\xBB\xBF".implode("\r\n", $guide)."\r\n");
        $zip->close();

        return response()->download($zipPath, 'templates.zip')->deleteFileAfterSend();
    }

    /** The lines that failed, to fix them in the file and import it again. */
    public function exportErrors(DataImportRun $run)
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [__('messages.data_import.line'), __('messages.data_import.error')], ';');
        foreach ($run->errors ?? [] as $error) {
            fputcsv($out, [$error['line'], $error['message']], ';');
        }
        rewind($out);

        return response(stream_get_contents($out), 200, ['Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="errors_'.$run->kind.'_'.$run->id.'.csv"']);
    }

    public static function rows(string $path, string $name, ?string $kind = null): array
    {
        $file = new UploadedFile(Storage::path($path), $name, null, null, true);
        $grid = LabImportProfile::readGrid($file);

        return LabImportProfile::rowsFromGrid($grid, $kind ? DataImport::headerRow($grid, $kind) : 1);
    }

    private function forgetFile(): void
    {
        if ($old = session(self::SESSION)) {
            Storage::delete($old['path']);
        }
        session()->forget([self::SESSION, 'data_import_preview']);
    }
}
