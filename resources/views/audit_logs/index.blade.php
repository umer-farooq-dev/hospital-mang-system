@extends('layouts.app')
@section('title')
    {{ __('messages.audit_log.audit_logs') }}
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex flex-column">
            @include('flash::message')
            <livewire:audit-log-table/>
        </div>
    </div>
@endsection
