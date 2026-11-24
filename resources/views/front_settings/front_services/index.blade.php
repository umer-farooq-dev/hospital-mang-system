@extends('layouts.app')
@section('title')
    {{ __('messages.front_web.type_'.$type) }}
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-column">
            @include('flash::message')
            {{Form::hidden('defaultDocumentImageUrl',asset('web_front/images/services/medicine.png'),['id'=>'indexServiceDefaultDocumentImageUrl'])}}
            {{Form::hidden('fontServicesCreateUrl',route('front.cms.services.store'),['id'=>'indexFrontServicesCreateUrl'])}}
            <ul class="nav nav-tabs mb-5 pb-1 overflow-auto flex-nowrap text-nowrap">
                @foreach (\App\Models\FrontService::TYPES as $typeId => $typeKey)
                    <li class="nav-item position-relative me-7 mb-3">
                        <a class="nav-link p-0 {{ $type == $typeId ? 'active' : '' }}" href="{{ route('front.cms.services.index', ['type' => $typeId]) }}">{{ __('messages.front_web.type_'.$typeId) }}</a>
                    </li>
                @endforeach
            </ul>
            {{Form::hidden('fontServicesUrl',route('front.cms.services.index'),['id'=>'indexFrontServicesUrl'])}}
            {{ Form::hidden('front_service', __('messages.package.service'), ['id' => 'frontService']) }}
            <livewire:front-cms-service-table :type="$type"/>@include('front_settings.front_services.add_modal')
            @include('front_settings.front_services.edit_modal')
        </div>
    </div>
@endsection
@section('scripts')
    {{-- assets/js/front_settings/front_services/front_services.js --}}
@endsection
