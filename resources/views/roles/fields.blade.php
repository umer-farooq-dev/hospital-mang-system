@php
    $isBuiltIn = isset($role) && $role->isBuiltIn();
    $isLocked = isset($role) && $role->isAdmin();
    $actions = config('access.actions');
    $allPermissions = collect($matrix)->flatten()->all();
    $allChecked = $allPermissions && ! array_diff($allPermissions, $rolePermissions);
@endphp
<div class="row">
    <div class="form-group col-sm-6 mb-5">
        {{ Form::label('name', __('messages.access.role_name') . ':', ['class' => 'form-label']) }}
        <span class="required"></span>
        @if ($isBuiltIn)
            {{ Form::text('name_display', $role->name, ['class' => 'form-control', 'disabled']) }}
            <div class="form-text">{{ __('messages.access.built_in_name_locked') }}</div>
        @else
            {{ Form::text('name', null, ['class' => 'form-control', 'required', 'maxlength' => 191, 'placeholder' => __('messages.access.role_name')]) }}
        @endif
    </div>
</div>

@if ($isLocked)
    <div class="alert alert-warning mb-5">{{ __('messages.access.admin_locked') }}</div>
@endif

<div class="d-md-flex align-items-center justify-content-between mb-5">
    <h2 class="fs-5 m-0">{{ __('messages.access.permissions') }}</h2>
    @unless ($isLocked)
        <label class="form-check form-check-custom mt-3 mt-md-0">
            <input class="form-check-input role-select-all" type="checkbox" {{ $allChecked ? 'checked' : '' }}>
            <span class="form-check-label ms-2">{{ __('messages.access.select_all') }}</span>
        </label>
    @endunless
</div>

@foreach ($matrix as $group => $modules)
    @php
        $groupPermissions = collect($modules)->flatten()->all();
        $groupChecked = ! array_diff($groupPermissions, $rolePermissions);
    @endphp
    <div class="card border mb-5">
        <div class="card-header d-flex align-items-center justify-content-between min-h-50px">
            <h3 class="card-title fs-6 m-0">{{ __('messages.access.groups.'.$group) }}</h3>
            @unless ($isLocked)
                <label class="form-check form-check-custom">
                    <input class="form-check-input role-select-group" type="checkbox" data-group="{{ $group }}"
                        {{ $groupChecked ? 'checked' : '' }}>
                    <span class="form-check-label ms-2">{{ __('messages.access.select_all') }}</span>
                </label>
            @endunless
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr class="{{ getCurrentLoginUserLanguageName() == 'ar' ? 'text-end' : 'text-start' }} text-muted fw-bolder fs-7 text-uppercase gs-0">
                        <th class="ps-5 w-25">{{ __('messages.access.module') }}</th>
                        @foreach ($actions as $action)
                            <th class="text-center">{{ __('messages.access.actions.'.$action) }}</th>
                        @endforeach
                        @unless ($isLocked)
                            <th class="text-center">{{ __('messages.access.all') }}</th>
                        @endunless
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($modules as $module => $modulePermissions)
                        <tr>
                            <td class="ps-5">{{ __('messages.access.modules.'.$module) }}</td>
                            @foreach ($actions as $action)
                                <td class="text-center">
                                    @isset($modulePermissions[$action])
                                        <input class="form-check-input role-permission" type="checkbox" name="permissions[]"
                                               value="{{ $modulePermissions[$action] }}"
                                               data-group="{{ $group }}" data-module="{{ $module }}"
                                               aria-label="{{ __('messages.access.modules.'.$module) }} - {{ __('messages.access.actions.'.$action) }}"
                                            {{ in_array($modulePermissions[$action], $rolePermissions, true) ? 'checked' : '' }}
                                            {{ $isLocked ? 'disabled' : '' }}>
                                    @endisset
                                </td>
                            @endforeach
                            @unless ($isLocked)
                                <td class="text-center">
                                    <input class="form-check-input role-select-row" type="checkbox"
                                           data-group="{{ $group }}" data-module="{{ $module }}"
                                           aria-label="{{ __('messages.access.modules.'.$module) }} - {{ __('messages.access.all') }}"
                                        {{ ! array_diff($modulePermissions, $rolePermissions) ? 'checked' : '' }}>
                                </td>
                            @endunless
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach

<div class="d-flex justify-content-end">
    @unless ($isLocked)
        {{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary me-2']) }}
    @endunless
    <a href="{{ route('roles.index') }}" class="btn btn-secondary me-2">{{ __('messages.common.cancel') }}</a>
</div>
