@extends('layouts.app')
@section('title')
    {{ __('messages.lab_import.profiles') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ route('lab-worklist.index') }}" class="btn btn-outline-primary">{{ __('messages.lab.worklist') }}</a>
        </div>
    </div>
@endsection
@section('content')
    @php
        $canEdit = canAccessRoute('lab-import-profiles.update');
    @endphp
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        <div class="form-text mb-5">{{ __('messages.lab_import.hint') }}</div>
        {{ Form::open(['route' => 'lab-import-profiles.update', 'method' => 'post']) }}
        @foreach ($profiles->push(null) as $profile)
            @continue(! $profile && ! $canEdit)
            @php
                $f = $profile ? 'profiles[' . $profile->id . ']' : 'new';
                $lines = collect($profile?->mappings ?? [])->map(fn ($id, $title) => ['title' => $title, 'id' => $id])->values()->all();
                $lines = array_merge($lines, array_fill(0, $canEdit ? 4 : 0, ['title' => '', 'id' => null]));
            @endphp
            <div class="card mb-7 {{ $profile ? '' : 'border border-dashed border-primary' }}">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-4"><label class="form-label">{{ $profile ? __('messages.lab_import.profile') : __('messages.lab_import.new_profile') }}</label>
                            <input type="text" name="{{ $f }}[name]" value="{{ $profile?->name }}" class="form-control" maxlength="150" {{ $profile ? 'required' : '' }} {{ $canEdit ? '' : 'disabled' }}></div>
                        <div class="col-md-2 mb-4"><label class="form-label">{{ __('messages.lab_import.header_row') }}</label>
                            <input type="number" name="{{ $f }}[header_row]" value="{{ $profile?->header_row ?? 1 }}" class="form-control" min="1" max="100" {{ $canEdit ? '' : 'disabled' }}></div>
                        <div class="col-md-4 mb-4"><label class="form-label">{{ __('messages.lab_import.sample_column') }}</label>
                            <input type="text" name="{{ $f }}[sample_column]" value="{{ $profile?->sample_column }}" class="form-control" maxlength="100" placeholder="Sample ID" {{ $profile ? 'required' : '' }} {{ $canEdit ? '' : 'disabled' }}></div>
                        <div class="col-md-2 mb-4 d-flex align-items-end">
                            @if ($profile)<label class="form-check form-switch"><input type="checkbox" class="form-check-input" name="{{ $f }}[is_active]" value="1" {{ $profile->is_active ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}><span class="form-check-label">{{ __('messages.common.active') }}</span></label>@endif
                        </div>
                    </div>
                    <table class="table table-row-dashed align-middle mb-0">
                        <thead><tr class="text-muted fs-7 text-uppercase"><th>{{ __('messages.lab_import.column') }}</th><th>{{ __('messages.lab.parameter') }}</th></tr></thead>
                        @foreach ($lines as $line)
                            <tr>
                                <td><input type="text" name="{{ $f }}[columns][]" value="{{ $line['title'] }}" class="form-control form-control-sm" maxlength="100" {{ $canEdit ? '' : 'disabled' }}></td>
                                <td>{{ Form::select($f . '[parameters][]', $parameters, $line['id'], ['class' => 'form-select form-select-sm', 'placeholder' => __('messages.common.choose')] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        @endforeach
        @if ($canEdit)
            <div class="d-flex justify-content-end">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>
        @endif
        {{ Form::close() }}
    </div>
@endsection
