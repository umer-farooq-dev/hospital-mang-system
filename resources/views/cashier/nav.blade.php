{{-- Links between the cashier pages. --}}
<div class="d-flex flex-wrap gap-2">
    @canroute('cashier.index')
    <a href="{{ route('cashier.index') }}" class="btn btn-sm {{ Request::is('cashier*') ? 'btn-primary' : 'btn-outline-primary' }}"><i class="fas fa-cash-register me-1"></i>{{ __('messages.cashier.cashier') }}</a>
    @endcanroute
    @canroute('receipts.index')
    <a href="{{ route('receipts.index') }}" class="btn btn-sm {{ Request::is('receipts*') ? 'btn-primary' : 'btn-outline-primary' }}"><i class="fas fa-receipt me-1"></i>{{ __('messages.receipts.receipts') }}</a>
    @endcanroute
    @canroute('cash-journal.index')
    <a href="{{ route('cash-journal.index') }}" class="btn btn-sm {{ Request::is('cash-journal*') ? 'btn-primary' : 'btn-outline-primary' }}"><i class="fas fa-book me-1"></i>{{ __('messages.cash_journal.title') }}</a>
    @endcanroute
    @canroute('patient-flow.index')
    <a href="{{ route('patient-flow.index') }}" class="btn btn-sm {{ Request::is('patient-flow*') ? 'btn-primary' : 'btn-outline-primary' }}"><i class="fas fa-chart-bar me-1"></i>{{ __('messages.patient_flow.title') }}</a>
    @endcanroute
    @canroute('cash-counters.index')
    <a href="{{ route('cash-counters.index') }}" class="btn btn-sm {{ Request::is('cash-counters*') ? 'btn-primary' : 'btn-outline-primary' }}"><i class="fas fa-cog me-1"></i>{{ __('messages.cashier.counters') }}</a>
    @endcanroute
</div>
