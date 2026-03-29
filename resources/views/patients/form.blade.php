<input type="text" name="art_number" value="{{ old('art_number', $patient->art_number ?? '') }}" class="form-control mb-2" placeholder="ART Number">

<input type="text" name="first_name" value="{{ old('first_name', $patient->first_name ?? '') }}" class="form-control mb-2" placeholder="First Name">

<input type="text" name="last_name" value="{{ old('last_name', $patient->last_name ?? '') }}" class="form-control mb-2" placeholder="Last Name">

<select name="sex" class="form-control mb-2">
    <option value="">Select Sex</option>
    <option value="Male" {{ (old('sex', $patient->sex ?? '') == 'Male') ? 'selected' : '' }}>Male</option>
    <option value="Female" {{ (old('sex', $patient->sex ?? '') == 'Female') ? 'selected' : '' }}>Female</option>
</select>

<input type="text" name="phone" value="{{ old('phone', $patient->phone ?? '') }}" class="form-control mb-2" placeholder="Phone">