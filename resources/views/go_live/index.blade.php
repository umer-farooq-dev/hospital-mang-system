@extends('layouts.app')
@section('title')
    {{ __('messages.go_live.title') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            @canroute('data-import.index')
            <a href="{{ route('data-import.index') }}" class="btn btn-outline-primary">{{ __('messages.data_import.title') }}</a>
            @endcanroute
        </div>
    </div>
@endsection
@section('content')
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="row">
            <div class="col-lg-7">
                @foreach ($checks as $group => $rows)
                    <div class="card mb-5">
                        <div class="card-body py-4">
                            <h3 class="mb-3">{{ __('messages.go_live.groups.' . $group) }}</h3>
                            @foreach ($rows as [$status, $label])
                                <div class="d-flex align-items-start py-2 border-bottom border-gray-200">
                                    <span class="badge me-3 {{ ['ok' => 'bg-success', 'warn' => 'bg-warning text-dark', 'fail' => 'bg-danger', 'info' => 'bg-light text-dark'][$status] }}">{{ __('messages.go_live.status.' . $status) }}</span>
                                    <span>{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="col-lg-5">
                <div class="card mb-5">
                    <div class="card-body">
                        <h3 class="mb-3">{{ __('messages.go_live.night_tasks') }}</h3>
                        <div class="form-text mb-4">{{ __('messages.go_live.cron_hint') }} <code>* * * * * php {{ base_path('artisan') }} schedule:run</code></div>
                        {{ Form::open(['route' => 'go-live.settings', 'method' => 'post']) }}
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="backup_enabled" value="1" id="backupEnabled" {{ $settings['backup_enabled'] === '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="backupEnabled">{{ __('messages.go_live.backup_enabled') }}</label>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">{{ Form::label('backup_time', __('messages.go_live.backup_time') . ':', ['class' => 'form-label']) }}
                                <input type="time" name="backup_time" class="form-control" value="{{ $settings['backup_time'] }}" required></div>
                            <div class="col-6">{{ Form::label('backup_keep', __('messages.go_live.backup_keep') . ':', ['class' => 'form-label']) }}
                                <input type="number" name="backup_keep" min="1" max="365" class="form-control" value="{{ $settings['backup_keep'] }}" required></div>
                        </div>
                        <div class="mb-3">{{ Form::label('backup_dump_path', __('messages.go_live.backup_dump_path') . ':', ['class' => 'form-label']) }}
                            {{ Form::text('backup_dump_path', $settings['backup_dump_path'], ['class' => 'form-control', 'required']) }}</div>
                        <div class="mb-3">{{ Form::label('backup_copy_path', __('messages.go_live.backup_copy_path') . ':', ['class' => 'form-label']) }}
                            {{ Form::text('backup_copy_path', $settings['backup_copy_path'], ['class' => 'form-control']) }}
                            <div class="form-text">{{ __('messages.go_live.backup_copy_hint') }}</div></div>
                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="backup_include_files" value="1" id="backupFiles" {{ $settings['backup_include_files'] === '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="backupFiles">{{ __('messages.go_live.backup_include_files') }}</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="accounting_auto_post" value="1" id="autoPost" {{ $settings['accounting_auto_post'] === '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="autoPost">{{ __('messages.go_live.auto_post') }}</label>
                        </div>
                        <div class="mb-4" style="max-width: 10rem">{{ Form::label('accounting_post_time', __('messages.go_live.post_time') . ':', ['class' => 'form-label']) }}
                            <input type="time" name="accounting_post_time" class="form-control" value="{{ $settings['accounting_post_time'] }}" required></div>
                        <button type="submit" class="btn btn-primary">{{ __('messages.common.save') }}</button>
                        {{ Form::close() }}
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">{{ __('messages.go_live.backups') }}</h3>
                            {{ Form::open(['route' => 'go-live.backup', 'method' => 'post']) }}
                            <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('messages.go_live.backup_now') }}</button>
                            {{ Form::close() }}
                        </div>
                        @if ($lastError)
                            <div class="alert alert-danger py-2">{{ $lastError }}</div>
                        @endif
                        @forelse ($backups as $backup)
                            <div class="d-flex justify-content-between py-1 border-bottom border-gray-200">
                                <a href="{{ route('go-live.backup.download', $backup['name']) }}">{{ $backup['name'] }}</a>
                                <span class="text-muted">{{ number_format($backup['size'] / 1048576, 1) }} MB · {{ date('d/m/Y H:i', $backup['time']) }}</span>
                            </div>
                        @empty
                            <div class="text-muted">{{ __('messages.go_live.no_backup') }}</div>
                        @endforelse
                        <div class="form-text mt-3">{{ __('messages.go_live.restore_hint') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
