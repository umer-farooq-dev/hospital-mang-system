@extends('layouts.app')
@section('title')
    {{ __('messages.access.roles') }}
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-column">
            @include('flash::message')
            {{ Form::hidden('roleUrl', url('roles'), ['id' => 'indexRoleUrl']) }}
            {{ Form::hidden('role', __('messages.access.role'), ['id' => 'Role']) }}
            <livewire:role-table/>
        </div>
    </div>
@endsection
{{--
        JS File :- assets/js/roles/roles.js
--}}
