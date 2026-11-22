@if ($row->icd10_code)
    <span class="badge bg-light-primary" title="{{ $row->icd10_label }}">{{ $row->icd10_code }}</span>
@endif
@if ($row->diagnosis_status)
    <div class="text-muted fs-7">{{ \App\Models\MasterListItem::label('diagnosis_status', $row->diagnosis_status, '') }}</div>
@endif
