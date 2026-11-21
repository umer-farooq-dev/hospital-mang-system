@extends('layouts.app')
@section('title')
    {{ __('messages.master_list.master_lists') }}
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-column">
            @include('flash::message')
            {{ Form::hidden('masterListCreateUrl', route('master-lists.store'), ['id' => 'indexMasterListCreateUrl']) }}
            {{ Form::hidden('masterListUrl', url('master-lists'), ['id' => 'indexMasterListUrl']) }}
            {{ Form::hidden('master_list_item', __('messages.master_list.item'), ['id' => 'masterListItemLabel']) }}

            <ul class="nav nav-tabs mb-5 fs-6 border-0">
                @foreach ($types as $listType)
                    <li class="nav-item">
                        <a class="nav-link {{ $listType === $type ? 'active' : '' }}"
                           href="{{ route('master-lists.index', ['type' => $listType]) }}">
                            {{ __('messages.master_list.types.'.$listType) }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="text-gray-600 mb-5">{{ __('messages.master_list.inactive_hint') }}</div>

            <livewire:master-list-table :type="$type" :key="$type"/>
            @include('master_lists.add_modal')
            @include('master_lists.edit_modal')
        </div>
    </div>
@endsection
{{--
        JS File :- assets/js/master_lists/master_lists.js
                   assets/js/custom/new-edit-modal-form.js
--}}
