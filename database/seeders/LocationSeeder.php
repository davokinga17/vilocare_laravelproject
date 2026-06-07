<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $stateIdColumn = $this->column('states', ['state_id', 'id']);
        $stateNameColumn = $this->column('states', ['state_name', 'name']);
        $countyIdColumn = $this->column('counties', ['county_id', 'id']);
        $countyNameColumn = $this->column('counties', ['county_name', 'name']);
        $facilityNameColumn = $this->column('facilities', ['facility_name', 'name']);
        $statesHaveTimestamps = Schema::hasColumn('states', 'created_at') && Schema::hasColumn('states', 'updated_at');
        $countiesHaveTimestamps = Schema::hasColumn('counties', 'created_at') && Schema::hasColumn('counties', 'updated_at');
        $facilitiesHaveTimestamps = Schema::hasColumn('facilities', 'created_at') && Schema::hasColumn('facilities', 'updated_at');

        if (! $stateIdColumn || ! $stateNameColumn || ! $countyIdColumn || ! $countyNameColumn || ! $facilityNameColumn) {
            return;
        }

        $states = [
            'Central Equatoria',
            'Eastern Equatoria',
            'Western Equatoria',
            'Jonglei',
            'Lakes',
            'Unity',
            'Upper Nile',
            'Warrap',
            'Northern Bahr el Ghazal',
            'Western Bahr el Ghazel',
            'Greater Pibor Administrative Area (GPAA)',
            'Abyei Administrative Area',
            'Ruweng Administrative Area',
        ];

        foreach ($states as $state) {
            $values = $statesHaveTimestamps
                ? ['updated_at' => $now, 'created_at' => $now]
                : [];

            DB::table('states')->updateOrInsert(
                [$stateNameColumn => $state],
                $values
            );
        }

        $easternEquatoriaId = DB::table('states')
            ->where($stateNameColumn, 'Eastern Equatoria')
            ->value($stateIdColumn);

        $counties = [
            'Budi County',
            'Ikotos County',
            'Kapoeta East County',
            'Kapoeta North County',
            'Kapoeta South County',
            'Lafon County',
            'Magwi County',
            'Torit County',
        ];

        foreach ($counties as $county) {
            $values = [
                'state_id' => $easternEquatoriaId,
            ];

            if ($countiesHaveTimestamps) {
                $values['updated_at'] = $now;
                $values['created_at'] = $now;
            }

            DB::table('counties')->updateOrInsert(
                [$countyNameColumn => $county],
                $values
            );
        }

        $countyIds = DB::table('counties')->pluck($countyIdColumn, $countyNameColumn);

        $facilities = [
            'Nimule Hospital' => 'Magwi County',
            'Pageri PHCC' => 'Magwi County',
            'Abara PHCC' => 'Magwi County',
            'Magwi PHCC' => 'Magwi County',
            'Owinykibul PHCC' => 'Magwi County',
            'Obbo PHCC' => 'Magwi County',
            'Pajok PHCC' => 'Magwi County',
            'Lobone PHCC' => 'Magwi County',
            'Torit State Hospital' => 'Torit County',
            'Nyong PHCC' => 'Torit County',
            'Hiyala PHCC' => 'Torit County',
            'St. Theresa Mission Hospital Isohe' => 'Torit County',
            'Kapoeta Civil Hospital' => 'Kapoeta South County',
            'Naknak PHCC' => 'Kapoeta South County',
            'Riwoto PHCC' => 'Kapoeta North County',
            'Narus PHCC' => 'Kapoeta East County',
        ];

        foreach ($facilities as $facility => $county) {
            $values = [
                'county_id' => $countyIds[$county] ?? null,
            ];

            if (Schema::hasColumn('facilities', 'state_id')) {
                $values['state_id'] = $easternEquatoriaId;
            }

            if ($facilitiesHaveTimestamps) {
                $values['updated_at'] = $now;
                $values['created_at'] = $now;
            }

            DB::table('facilities')->updateOrInsert(
                [$facilityNameColumn => $facility],
                $values
            );
        }
    }

    private function column(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }
}
