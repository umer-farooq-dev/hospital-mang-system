{{-- Excel export of the tables of this screen (App\\Http\\Middleware\\ExportTables), for users with the export permission. --}}
@if (\App\Http\Middleware\ExportTables::allowed(request()))
    <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}" class="btn btn-outline-success d-print-none ms-md-3 mt-3 mt-md-0">
        <i class="fas fa-file-excel me-1"></i>{{ __('messages.common.export_excel') }}
    </a>
@endif
