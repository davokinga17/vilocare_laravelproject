<?php

namespace App\Imports;

use App\Models\Patient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PatientsImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public int $skipped = 0;

    public array $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $data = $this->mapRow($row->toArray());

            if (! array_filter($data, fn ($value) => $value !== null && $value !== '')) {
                continue;
            }

            $validator = Validator::make($data, [
                'art_number' => ['required', 'string', 'max:100', Rule::unique('patients', 'art_number')],
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'sex' => ['required', Rule::in(['Male', 'Female', 'Other'])],
                'address' => ['nullable', 'string'],
                'phone' => ['nullable', 'string', 'max:20'],
                'art_start_date' => ['nullable', 'date'],
                'current_regimen' => ['nullable', 'string', 'max:100'],
                'age' => ['nullable', 'integer', 'min:0', 'max:130'],
                'is_pregnant' => ['nullable', 'boolean'],
                'is_breastfeeding' => ['nullable', 'boolean'],
                'arv_adherence' => ['nullable', 'string', 'max:50'],
                'facility_id' => ['nullable', 'integer'],
                'county_id' => ['nullable', 'integer'],
                'state_id' => ['nullable', 'integer'],
            ]);

            if ($validator->fails()) {
                $this->skipped++;
                $this->errors[] = 'Row ' . ($index + 2) . ': ' . implode(' ', $validator->errors()->all());
                continue;
            }

            Patient::create($validator->validated());
            $this->imported++;
        }
    }

    private function mapRow(array $row): array
    {
        return [
            'art_number' => $this->value($row, ['art_number', 'art no', 'art_no', 'art']),
            'first_name' => $this->value($row, ['first_name', 'first name', 'firstname']),
            'last_name' => $this->value($row, ['last_name', 'last name', 'lastname', 'surname']),
            'sex' => $this->normalizeSex($this->value($row, ['sex', 'gender'])),
            'address' => $this->value($row, ['address']),
            'phone' => $this->value($row, ['phone', 'telephone', 'contact']),
            'art_start_date' => $this->dateValue($this->value($row, ['art_start_date', 'art start date', 'art_start'])),
            'current_regimen' => $this->value($row, ['current_regimen', 'current regimen', 'regimen']),
            'age' => $this->value($row, ['age', 'patient_age', 'patient age']),
            'is_pregnant' => $this->booleanValue($this->value($row, ['is_pregnant', 'pregnant'])),
            'is_breastfeeding' => $this->booleanValue($this->value($row, ['is_breastfeeding', 'breastfeeding'])),
            'arv_adherence' => $this->value($row, ['arv_adherence', 'arv adherence', 'adherence']),
            'facility_id' => $this->value($row, ['facility_id', 'facility id']),
            'county_id' => $this->value($row, ['county_id', 'county id']),
            'state_id' => $this->value($row, ['state_id', 'state id']),
        ];
    }

    private function value(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            $normalized = str_replace(' ', '_', strtolower($key));

            if (array_key_exists($normalized, $row) && $row[$normalized] !== '') {
                return $row[$normalized];
            }
        }

        return null;
    }

    private function normalizeSex(mixed $sex): ?string
    {
        if ($sex === null) {
            return null;
        }

        return match (strtolower(trim($sex))) {
            'm', 'male' => 'Male',
            'f', 'female' => 'Female',
            'o', 'other' => 'Other',
            default => $sex,
        };
    }

    private function booleanValue(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return in_array(strtolower((string) $value), ['1', 'yes', 'y', 'true'], true);
    }

    private function dateValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        return $value;
    }
}
