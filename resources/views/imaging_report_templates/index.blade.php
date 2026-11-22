@extends('layouts.app')
@section('title')
    {{ __('messages.imaging.templates_title') }}
@endsection
@section('header_toolbar')
    <div class="container-fluid">
        <div class="d-md-flex align-items-center justify-content-between mb-7">
            <h1 class="mb-0">@yield('title')</h1>
            <a href="{{ route('lab-worklist.index', ['type' => 'radiology_test']) }}" class="btn btn-outline-primary">{{ __('messages.lab.worklist') }}</a>
        </div>
    </div>
@endsection
@section('content')
    @php($canEdit = canAccessRoute('imaging-report-templates.update'))
    <div class="container-fluid">
        @include('flash::message')
        @include('layouts.errors')
        {{ Form::open(['route' => 'imaging-report-templates.update', 'method' => 'post']) }}
        @foreach ($templates->push(null) as $template)
            @continue(! $template && ! $canEdit)
            @php($f = $template ? 'templates[' . $template->id . ']' : 'new')
            <div class="card mb-7 {{ $template ? '' : 'border border-dashed border-primary' }}">
                <div class="card-body row">
                    <div class="col-md-5 mb-4">
                        <label class="form-label">{{ $template ? __('messages.certificate.template') : __('messages.certificate.new_template') }}</label>
                        <input type="text" name="{{ $f }}[name]" value="{{ $template?->label }}" class="form-control" maxlength="150" {{ $template ? 'required' : '' }} {{ $canEdit ? '' : 'disabled' }}>
                    </div>
                    <div class="col-md-3 mb-4">
                        <label class="form-label">{{ __('messages.imaging.modality') }}</label>
                        {{ Form::select($f . '[modality]', $modalities, $template?->modality, ['class' => 'form-select', 'placeholder' => __('messages.imaging.any_modality')] + ($canEdit ? [] : ['disabled' => 'disabled'])) }}
                    </div>
                    <div class="col-md-2 mb-4">
                        <label class="form-label">{{ __('messages.master_list.sort_order') }}</label>
                        <input type="number" name="{{ $f }}[sort_order]" value="{{ $template?->sort_order }}" class="form-control" min="0" max="9999" {{ $canEdit ? '' : 'disabled' }}>
                    </div>
                    <div class="col-md-2 mb-4 d-flex align-items-end">
                        @if ($template)
                            <label class="form-check form-switch"><input type="checkbox" class="form-check-input" name="{{ $f }}[is_active]" value="1" {{ $template->is_active ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}><span class="form-check-label">{{ __('messages.common.active') }}</span></label>
                        @endif
                    </div>
                    <div class="col-12">
                        <textarea name="{{ $f }}[body]" rows="9" class="form-control" maxlength="50000" {{ $template ? 'required' : '' }} {{ $canEdit ? '' : 'disabled' }}>{{ $template?->text }}</textarea>
                    </div>
                </div>
            </div>
        @endforeach
        @if ($canEdit)
            <div class="d-flex justify-content-end">{{ Form::submit(__('messages.common.save'), ['class' => 'btn btn-primary']) }}</div>
        @endif
        {{ Form::close() }}
    </div>
@endsection
