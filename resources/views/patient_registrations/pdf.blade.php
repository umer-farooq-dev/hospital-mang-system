<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('messages.reception_report.title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 16px; margin: 0; }
        h2 { font-size: 12px; margin: 14px 0 6px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 4px 5px; text-align: left; }
        th { background: #eee; }
        .num { text-align: right; }
        .summary td { border: none; padding: 2px 12px 2px 0; }
    </style>
</head>
<body>
    @php($settings = getSettingValue())
    <h1>{{ $settings['company_name']->value ?? getAppName() }}</h1>
    <div class="muted">{{ $settings['hospital_address']->value ?? '' }} · {{ $settings['hospital_phone']->value ?? '' }}</div>
    <h2>{{ __('messages.reception_report.title') }} : {{ $filters['from']->format('d/m/Y') }} – {{ $filters['to']->format('d/m/Y') }}</h2>
    <div class="muted">{{ __('messages.reception_report.printed_at') }} {{ now()->format('d/m/Y H:i') }}</div>

    <table class="summary" style="margin-top:8px; width:auto">
        <tr>
            <td>{{ __('messages.reception_report.total') }} : <b>{{ $summary['total'] }}</b></td>
            <td>{{ __('messages.reception_report.male') }} : <b>{{ $summary['male'] }}</b></td>
            <td>{{ __('messages.reception_report.female') }} : <b>{{ $summary['female'] }}</b></td>
            <td>{{ __('messages.reception_report.with_id') }} : <b>{{ $summary['with_id'] }}</b></td>
            <td>{{ __('messages.reception_report.insured') }} : <b>{{ $summary['insured'] }}</b></td>
        </tr>
    </table>

    @if ($summary['visits']->isNotEmpty())
        <h2>{{ __('messages.reception_report.visits_by_department') }}</h2>
        <table>
            <tr>
                <th>{{ __('messages.reception_report.department') }}</th>
                <th class="num">{{ __('messages.reception_report.visits') }}</th>
                <th class="num">{{ __('messages.reception_report.new_patients') }}</th>
                <th class="num">{{ __('messages.reception_report.returning_patients') }}</th>
            </tr>
            @foreach ($summary['visits'] as $department => $row)
                <tr>
                    <td>{{ $department }}</td>
                    <td class="num">{{ $row['visits'] }}</td>
                    <td class="num">{{ $row['new'] }}</td>
                    <td class="num">{{ $row['returning'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h2>{{ __('messages.reception_report.registered_patients') }}</h2>
    @include('patient_registrations.table', ['patients' => $patients, 'print' => true])
</body>
</html>
