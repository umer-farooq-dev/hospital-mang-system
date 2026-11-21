<span class="badge bg-light-{{ $row->payer_type == 2 ? 'info' : 'primary' }}">{{ $row->payer_type_name ?? __('messages.common.n/a') }}</span>
