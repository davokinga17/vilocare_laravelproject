@extends('layouts.app')

@section('page_title', 'Samples')

@push('styles')
    <link href="{{ asset('css/clinical-pages.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="clinical-page">
    <header class="clinical-page-header">
        <div><h2>Sample Management</h2><p>Track collected samples, reception status, and quality rejections.</p></div>
        <a href="{{ route('samples.create') }}" class="clinical-btn clinical-btn-primary"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Add Sample</a>
    </header>

    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="GET" action="{{ route('samples.index') }}" class="clinical-card clinical-toolbar compact-four">
        <div class="clinical-field clinical-search"><label for="sample_search">Search</label><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><input id="sample_search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search by sample, ART number or patient..."></div>
        <div class="clinical-field"><label for="sample_status">Status</label><select id="sample_status" name="status" class="form-select"><option value="">All statuses</option>@foreach(['Collected','Received','Rejected','Processed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
        <div class="clinical-field"><label for="sample_type">Sample Type</label><select id="sample_type" name="sample_type" class="form-select"><option value="">All types</option>@foreach($sampleTypes as $type)<option value="{{ $type }}" @selected(request('sample_type') === $type)>{{ $type }}</option>@endforeach</select></div>
        <div class="clinical-toolbar-actions"><button class="clinical-btn clinical-btn-primary">Apply Filters</button><a href="{{ route('samples.index') }}" class="clinical-btn">Reset</a></div>
    </form>

    <div class="clinical-split">
        <section class="clinical-card clinical-table-card">
            <div class="clinical-table-meta"><strong>Sample Collection Register</strong><span>{{ $samples->total() }} record(s)</span></div>
            <div class="table-responsive">
                <table class="table clinical-table">
                    <thead><tr><th>Sample ID</th><th>Patient</th><th>Collection</th><th>Type</th><th>Collector</th><th>Status</th><th>Reception</th><th>Facility</th><th>Quality</th></tr></thead>
                    <tbody>
                    @forelse($samples as $sample)
                        <tr>
                            <td class="primary-cell">SMP-{{ str_pad($sample->sample_id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="primary-cell">{{ optional($sample->patient)->first_name }} {{ optional($sample->patient)->last_name }}<span class="secondary-line">{{ optional($sample->patient)->art_number }}</span></td>
                            <td>{{ optional($sample->collection_date)->format('d M Y') ?: '—' }}</td>
                            <td>{{ $sample->sample_type ?: '—' }}</td>
                            <td>{{ $sample->collector ?: '—' }}</td>
                            <td><span class="clinical-badge {{ $sample->status === 'Rejected' ? 'clinical-badge-danger' : ($sample->status === 'Processed' ? 'clinical-badge-success' : 'clinical-badge-neutral') }}">{{ $sample->status ?: 'Collected' }}</span></td>
                            <td>{{ optional($sample->sample_reception_date)->format('d M Y') ?: '—' }}</td>
                            <td>{{ $sample->health_facility_code ?: '—' }}</td>
                            <td>@if($sample->rejections->isNotEmpty())<span class="clinical-badge clinical-badge-danger">Rejected</span>@else<span class="clinical-badge clinical-badge-success">Accepted</span>@endif</td>
                        </tr>
                    @empty<tr><td colspan="9" class="clinical-table-empty">No sample records match the selected filters.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
            <div class="clinical-pagination">{{ $samples->links() }}</div>
        </section>

        <aside class="clinical-card clinical-side-card">
            <h3>Record Sample Rejection</h3>
            <form method="POST" action="{{ route('samples.reject') }}" class="d-grid gap-2">
                @csrf
                <div class="clinical-field"><label>Sample <span class="required">*</span></label><select name="sample_id" class="form-select" required><option value="">Select sample</option>@foreach($rejectableSamples as $sample)<option value="{{ $sample->sample_id }}">SMP-{{ str_pad($sample->sample_id, 5, '0', STR_PAD_LEFT) }} · {{ optional($sample->patient)->art_number }}</option>@endforeach</select></div>
                <div class="clinical-field"><label>Rejection Date</label><input type="date" name="rejection_date" value="{{ old('rejection_date', now()->toDateString()) }}" class="form-control"></div>
                <div class="clinical-field"><label>Reason</label><textarea name="reason" class="form-control" rows="2" placeholder="State the rejection reason">{{ old('reason') }}</textarea></div>
                <div class="clinical-field"><label>Action Taken</label><textarea name="action_taken" class="form-control" rows="2">{{ old('action_taken') }}</textarea></div>
                <div class="clinical-field"><label>Corrective Action</label><textarea name="corrective_action" class="form-control" rows="2">{{ old('corrective_action') }}</textarea></div>
                <button class="clinical-btn clinical-btn-danger" type="submit">Save Rejection</button>
            </form>
        </aside>
    </div>

    <section class="clinical-card clinical-table-card">
        <div class="clinical-table-meta"><strong>Recent Sample Rejections</strong><span>Latest quality events</span></div>
        <div class="table-responsive"><table class="table clinical-table"><thead><tr><th>ID</th><th>Sample</th><th>Patient</th><th>Date</th><th>Reason</th><th>Action Taken</th><th>Corrective Action</th></tr></thead><tbody>
            @forelse($rejections as $rejection)<tr><td class="primary-cell">REJ-{{ str_pad($rejection->rejection_id, 4, '0', STR_PAD_LEFT) }}</td><td>SMP-{{ str_pad($rejection->sample_id, 5, '0', STR_PAD_LEFT) }}</td><td>{{ optional(optional($rejection->sample)->patient)->art_number }} · {{ optional(optional($rejection->sample)->patient)->first_name }} {{ optional(optional($rejection->sample)->patient)->last_name }}</td><td>{{ optional($rejection->rejection_date)->format('d M Y') ?: '—' }}</td><td>{{ $rejection->reason ?: '—' }}</td><td>{{ $rejection->action_taken ?: '—' }}</td><td>{{ $rejection->corrective_action ?: '—' }}</td></tr>
            @empty<tr><td colspan="7" class="clinical-table-empty">No sample rejections recorded.</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
