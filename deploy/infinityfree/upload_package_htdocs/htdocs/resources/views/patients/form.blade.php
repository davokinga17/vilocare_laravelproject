<section class="clinical-form-section">
    <div class="clinical-section-title"><span>ID</span><div><h3>Identification</h3><p>Core patient identity and contact details.</p></div></div>
    <div class="clinical-form-grid">
        <div class="clinical-field clinical-col-3"><label>ART Number <span class="required">*</span></label><input type="text" name="art_number" value="{{ old('art_number', $patient->art_number ?? '') }}" class="form-control" placeholder="e.g. EE-TR-0001" required></div>
        <div class="clinical-field clinical-col-3"><label>First Name <span class="required">*</span></label><input type="text" name="first_name" value="{{ old('first_name', $patient->first_name ?? '') }}" class="form-control" placeholder="Enter first name" required></div>
        <div class="clinical-field clinical-col-3"><label>Last Name <span class="required">*</span></label><input type="text" name="last_name" value="{{ old('last_name', $patient->last_name ?? '') }}" class="form-control" placeholder="Enter last name" required></div>
        <div class="clinical-field clinical-col-3"><label>Sex <span class="required">*</span></label><select name="sex" class="form-select" required><option value="">Select sex</option>@foreach(['Male','Female','Other'] as $option)<option value="{{ $option }}" @selected(old('sex', $patient->sex ?? '') === $option)>{{ $option }}</option>@endforeach</select></div>
        <div class="clinical-field clinical-col-3"><label>Age (Years)</label><input type="number" name="age" value="{{ old('age', $patient->age ?? '') }}" class="form-control" min="0" max="130" placeholder="Enter age"></div>
        <div class="clinical-field clinical-col-3"><label>Patient Cellphone Number</label><input type="text" name="phone" value="{{ old('phone', $patient->phone ?? '') }}" class="form-control" placeholder="07xxxxxxxx"></div>
    </div>
</section>

<section class="clinical-form-section">
    <div class="clinical-section-title"><span>LC</span><div><h3>Profile &amp; Location</h3><p>Patient address and service location.</p></div></div>
    <div class="clinical-form-grid">
        <div class="clinical-field clinical-col-6"><label>Address</label><textarea name="address" class="form-control" rows="2" placeholder="Enter physical address">{{ old('address', $patient->address ?? '') }}</textarea></div>
        <div class="clinical-field clinical-col-3"><label>Facility</label><select name="facility_id" class="form-select"><option value="">Select facility</option>@foreach($facilities as $facility)<option value="{{ $facility['value'] }}" @selected((string) old('facility_id', $patient->facility_id ?? '') === $facility['value'])>{{ $facility['label'] }}</option>@endforeach</select></div>
        <div class="clinical-field clinical-col-3"><label>County</label><select name="county_id" class="form-select"><option value="">Select county</option>@foreach($counties as $county)<option value="{{ $county['value'] }}" @selected((string) old('county_id', $patient->county_id ?? '') === $county['value'])>{{ $county['label'] }}</option>@endforeach</select></div>
        <div class="clinical-field clinical-col-3"><label>State</label><select name="state_id" class="form-select"><option value="">Select state</option>@foreach($states as $state)<option value="{{ $state['value'] }}" @selected((string) old('state_id', $patient->state_id ?? '') === $state['value'])>{{ $state['label'] }}</option>@endforeach</select></div>
    </div>
</section>

<section class="clinical-form-section">
    <div class="clinical-section-title"><span>TX</span><div><h3>Treatment Information</h3><p>ART initiation, current regimen, and adherence.</p></div></div>
    <div class="clinical-form-grid">
        <div class="clinical-field clinical-col-4"><label>ART Start Date</label><input type="date" name="art_start_date" value="{{ old('art_start_date', !empty($patient->art_start_date) ? \Illuminate\Support\Carbon::parse($patient->art_start_date)->format('Y-m-d') : '') }}" class="form-control"></div>
        <div class="clinical-field clinical-col-4"><label>Current Regimen</label><input type="text" name="current_regimen" value="{{ old('current_regimen', $patient->current_regimen ?? '') }}" class="form-control" placeholder="Select or enter regimen"></div>
        <div class="clinical-field clinical-col-4"><label>ARV Adherence</label><select name="arv_adherence" class="form-select"><option value="">Select adherence</option>@foreach(['Good','Fair','Poor'] as $option)<option value="{{ $option }}" @selected(old('arv_adherence', $patient->arv_adherence ?? '') === $option)>{{ $option }}</option>@endforeach</select></div>
    </div>
</section>

<section class="clinical-form-section">
    <div class="clinical-section-title"><span>CL</span><div><h3>Clinical Status</h3><p>Pregnancy and breastfeeding information.</p></div></div>
    <div class="clinical-checks">
        <div class="form-check"><input type="hidden" name="is_pregnant" value="0"><input class="form-check-input" type="checkbox" name="is_pregnant" value="1" id="is_pregnant" @checked(old('is_pregnant', $patient->is_pregnant ?? false))><label class="form-check-label" for="is_pregnant">Patient is pregnant</label></div>
        <div class="form-check"><input type="hidden" name="is_breastfeeding" value="0"><input class="form-check-input" type="checkbox" name="is_breastfeeding" value="1" id="is_breastfeeding" @checked(old('is_breastfeeding', $patient->is_breastfeeding ?? false))><label class="form-check-label" for="is_breastfeeding">Patient is breastfeeding</label></div>
    </div>
</section>
