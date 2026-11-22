{{-- Modality and default report template of an imaging test. --}}
<div class="col-md-3">
    <div class="form-group mb-5">
        {{ Form::label('modality', __('messages.imaging.modality') . ':', ['class' => 'form-label']) }}
        {{ Form::select('modality', \App\Models\MasterListItem::options('imaging_modality', $radiologyTest->modality ?? null), $radiologyTest->modality ?? null, ['class' => 'form-select', 'placeholder' => __('messages.common.choose')]) }}
    </div>
</div>
<div class="col-md-3">
    <div class="form-group mb-5">
        {{ Form::label('report_template_id', __('messages.imaging.default_template') . ':', ['class' => 'form-label']) }}
        {{ Form::select('report_template_id', \App\Models\ImagingReportTemplate::forModality($radiologyTest->modality ?? null)->mapWithKeys(fn ($t) => [$t->id => $t->label])->all(), $radiologyTest->report_template_id ?? null, ['class' => 'form-select', 'placeholder' => __('messages.imaging.no_template')]) }}
    </div>
</div>
