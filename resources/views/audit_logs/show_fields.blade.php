@php
    $oldValues = $auditLog->old_values ?? [];
    $newValues = $auditLog->new_values ?? [];
    $fields = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
    $display = fn ($value) => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
@endphp
<div class="card mb-5 mb-xl-10">
    <div class="card-body">
        <div class="row mb-7">
            <div class="col-lg-3 d-flex flex-column mb-md-10 mb-5">
                <label class="pb-2 fs-5 text-gray-600">{{ __('messages.audit_log.date_time') . ':' }}</label>
                <span class="fs-5 text-gray-800">{{ \Carbon\Carbon::parse($auditLog->created_at)->isoFormat('Do MMMM YYYY, h:mm:ss A') }}</span>
            </div>
            <div class="col-lg-3 d-flex flex-column mb-md-10 mb-5">
                <label class="pb-2 fs-5 text-gray-600">{{ __('messages.audit_log.user') . ':' }}</label>
                <span class="fs-5 text-gray-800">{{ $auditLog->user->full_name ?? __('messages.audit_log.system') }}</span>
            </div>
            <div class="col-lg-3 d-flex flex-column mb-md-10 mb-5">
                <label class="pb-2 fs-5 text-gray-600">{{ __('messages.audit_log.event') . ':' }}</label>
                <span class="fs-5 text-gray-800">{{ __('messages.audit_log.'.$auditLog->event) }}</span>
            </div>
            <div class="col-lg-3 d-flex flex-column mb-md-10 mb-5">
                <label class="pb-2 fs-5 text-gray-600">{{ __('messages.audit_log.module') . ':' }}</label>
                <span class="fs-5 text-gray-800">{{ $auditLog->module_name ?: __('messages.common.n/a') }}</span>
            </div>
            <div class="col-lg-3 d-flex flex-column mb-md-10 mb-5">
                <label class="pb-2 fs-5 text-gray-600">{{ __('messages.audit_log.record_id') . ':' }}</label>
                <span class="fs-5 text-gray-800">{{ $auditLog->auditable_id ?? __('messages.common.n/a') }}</span>
            </div>
            <div class="col-lg-3 d-flex flex-column mb-md-10 mb-5">
                <label class="pb-2 fs-5 text-gray-600">{{ __('messages.audit_log.ip_address') . ':' }}</label>
                <span class="fs-5 text-gray-800">{{ $auditLog->ip_address ?? __('messages.common.n/a') }}</span>
            </div>
            <div class="col-lg-6 d-flex flex-column mb-md-10 mb-5">
                <label class="pb-2 fs-5 text-gray-600">{{ __('messages.audit_log.url') . ':' }}</label>
                <span class="fs-5 text-gray-800 text-break">{{ $auditLog->url ?? __('messages.common.n/a') }}</span>
            </div>
            <div class="col-lg-12 d-flex flex-column">
                <label class="pb-2 fs-5 text-gray-600">{{ __('messages.audit_log.user_agent') . ':' }}</label>
                <span class="fs-6 text-gray-800 text-break">{{ $auditLog->user_agent ?? __('messages.common.n/a') }}</span>
            </div>
        </div>
    </div>
</div>
<div class="d-md-flex align-items-center justify-content-between mb-7">
    <h1 class="fs-5 m-0">{{ __('messages.audit_log.changes') }}</h1>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="table-responsive viewList">
            <table class="table table-striped">
                <thead>
                <tr class="{{ getCurrentLoginUserLanguageName() == 'ar' ? 'text-end' : 'text-start' }} text-muted fw-bolder fs-7 text-uppercase gs-0">
                    <th class="w-25">{{ __('messages.audit_log.field') }}</th>
                    <th>{{ __('messages.audit_log.old_value') }}</th>
                    <th>{{ __('messages.audit_log.new_value') }}</th>
                </tr>
                </thead>
                <tbody class="fw-bold">
                @forelse($fields as $field)
                    <tr>
                        <td>{{ $field }}</td>
                        <td class="text-break text-danger">{{ array_key_exists($field, $oldValues) ? $display($oldValues[$field]) : '' }}</td>
                        <td class="text-break text-success">{{ array_key_exists($field, $newValues) ? $display($newValues[$field]) : '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-gray-600">{{ __('messages.audit_log.no_changes') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
