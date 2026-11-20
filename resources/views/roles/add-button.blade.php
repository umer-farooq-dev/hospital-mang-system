<div class="card-toolbar">
    <div class="d-flex align-items-center">
        @canroute('roles.create')
            <a href="{{ route('roles.create') }}" class="btn btn-primary">{{ __('messages.access.new_role') }}</a>
        @endcanroute
    </div>
</div>
