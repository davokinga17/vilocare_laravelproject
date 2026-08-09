<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">ART Number</label>
        <input type="text" name="art_number" value="{{ old('art_number', $patient->art_number ?? '') }}" class="form-control" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">First Name</label>
        <input type="text" name="first_name" value="{{ old('first_name', $patient->first_name ?? '') }}" class="form-control" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Last Name</label>
        <input type="text" name="last_name" value="{{ old('last_name', $patient->last_name ?? '') }}" class="form-control" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Sex</label>
        <select name="sex" class="form-control" required>
            <option value="">Select Sex</option>
            @foreach(['Male', 'Female', 'Other'] as $sexOption)
                <option value="{{ $sexOption }}" {{ old('sex', $patient->sex ?? '') == $sexOption ? 'selected' : '' }}>{{ $sexOption }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $patient->phone ?? '') }}" class="form-control">
    </div>

    <div class="col-md-4">
        <label class="form-label">ART Start Date</label>
        <input type="date" name="art_start_date" value="{{ old('art_start_date', !empty($patient->art_start_date) ? \Illuminate\Support\Carbon::parse($patient->art_start_date)->format('Y-m-d') : '') }}" class="form-control">
    </div>

    <div class="col-md-6">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2">{{ old('address', $patient->address ?? '') }}</textarea>
    </div>

    <div class="col-md-3">
        <label class="form-label">Current Regimen</label>
        <input type="text" name="current_regimen" value="{{ old('current_regimen', $patient->current_regimen ?? '') }}" class="form-control">
    </div>

    <div class="col-md-3">
        <label class="form-label">ARV Adherence</label>
        <input type="text" name="arv_adherence" value="{{ old('arv_adherence', $patient->arv_adherence ?? '') }}" class="form-control" placeholder="Good, Fair, Poor">
    </div>

    <div class="col-md-4">
        <label class="form-label">Age</label>
        <input type="number" name="age" value="{{ old('age', $patient->age ?? '') }}" class="form-control" min="0" max="130" placeholder="Patient age in years">
    </div>

    <div class="col-md-4">
        <label class="form-label">State</label>
        <select name="state_id" class="form-control">
            <option value="">Select State</option>
            @foreach($states as $state)
                <option value="{{ $state['value'] }}" {{ (string) old('state_id', $patient->state_id ?? '') === $state['value'] ? 'selected' : '' }}>{{ $state['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">County</label>
        <select name="county_id" class="form-control">
            <option value="">Select County</option>
            @foreach($counties as $county)
                <option value="{{ $county['value'] }}" {{ (string) old('county_id', $patient->county_id ?? '') === $county['value'] ? 'selected' : '' }}>{{ $county['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Facility</label>
        <select name="facility_id" class="form-control">
            <option value="">Select Facility</option>
            @foreach($facilities as $facility)
                <option value="{{ $facility['value'] }}" {{ (string) old('facility_id', $patient->facility_id ?? '') === $facility['value'] ? 'selected' : '' }}>{{ $facility['label'] }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4 d-flex align-items-end gap-4">
        <div class="form-check">
            <input type="hidden" name="is_pregnant" value="0">
            <input class="form-check-input" type="checkbox" name="is_pregnant" value="1" id="is_pregnant" {{ old('is_pregnant', $patient->is_pregnant ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_pregnant">Pregnant</label>
        </div>
        <div class="form-check">
            <input type="hidden" name="is_breastfeeding" value="0">
            <input class="form-check-input" type="checkbox" name="is_breastfeeding" value="1" id="is_breastfeeding" {{ old('is_breastfeeding', $patient->is_breastfeeding ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_breastfeeding">Breastfeeding</label>
        </div>
    </div>
</div>
