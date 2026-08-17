<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardSummaryService
{
    public function summary(array $filters = []): array
    {
        $today = Carbon::today()->toDateString();
        $filterConfig = $this->filterConfig();

        $totalPatients = $this->safeCount(function () use ($filters, $filterConfig) {
            $query = DB::table('patients as p');
            $this->applyLocationFilters($query, $filters, $filterConfig, 'p');
            $this->applyDateFilters($query, $filters, 'p.art_start_date');

            return $query->count();
        });

        $totalVlDue = $this->safeCount(fn () => (clone $this->buildDueForVlQuery($filters, $filterConfig))->count());

        $totalVlDueToday = $this->safeCount(function () use ($filters, $filterConfig, $today) {
            return (clone $this->buildDueForVlQuery($filters, $filterConfig, false))
                ->whereDate('due.due_date', $today)
                ->count();
        });

        $unsuppressed = $this->safeCount(function () use ($filters, $filterConfig) {
            $query = DB::table('viral_load_results as vl')
                ->join('patients as p', 'p.patient_id', '=', 'vl.patient_id')
                ->where('vl.result_cpml', '>=', 1000);
            $this->applyLocationFilters($query, $filters, $filterConfig, 'p');
            $this->applyDateFilters($query, $filters, 'vl.sample_date');

            return $query->count();
        });

        $suppressed = $this->safeCount(function () use ($filters, $filterConfig) {
            $query = DB::table('viral_load_results as vl')
                ->join('patients as p', 'p.patient_id', '=', 'vl.patient_id')
                ->where('vl.result_cpml', '<', 1000);
            $this->applyLocationFilters($query, $filters, $filterConfig, 'p');
            $this->applyDateFilters($query, $filters, 'vl.sample_date');

            return $query->count();
        });

        $totalSamplesRejected = $this->safeCount(function () use ($filters, $filterConfig) {
            $query = DB::table('sample_rejections as sr');

            if (Schema::hasColumn('sample_rejections', 'sample_id')) {
                $query->join('sample_collection as sc', 'sc.sample_id', '=', 'sr.sample_id')
                    ->join('patients as p', 'p.patient_id', '=', 'sc.patient_id');
                $this->applyLocationFilters($query, $filters, $filterConfig, 'p');
            } elseif (Schema::hasColumn('sample_rejections', 'patient_id')) {
                $query->join('patients as p', 'p.patient_id', '=', 'sr.patient_id');
                $this->applyLocationFilters($query, $filters, $filterConfig, 'p');
            }

            $this->applyDateFilters($query, $filters, 'sr.rejection_date');

            return $query->count();
        });

        $upcomingAppointments = Appointment::query()
            ->with('patient')
            ->whereDate('appointment_date', '>=', Carbon::today()->toDateString())
            ->orderBy('appointment_date')
            ->limit(8)
            ->get();

        return [
            'period' => $this->periodLabel($filters),
            'totals' => [
                'patients' => $totalPatients,
                'vl_due_today' => $totalVlDueToday,
                'vl_due_total' => $totalVlDue,
                'suppressed' => $suppressed,
                'unsuppressed' => $unsuppressed,
                'samples_rejected' => $totalSamplesRejected,
            ],
            'upcomingAppointments' => $upcomingAppointments,
            'reports' => [
                'Summary PDF',
                'Summary Excel',
                'Patients PDF',
                'Patients Excel',
                'Viral Load PDF',
                'Viral Load Excel',
            ],
        ];
    }

    private function filterConfig(): array
    {
        return [
            'state_id' => [
                'column' => 'state_id',
                'available' => Schema::hasColumn('patients', 'state_id'),
            ],
            'county_id' => [
                'column' => 'county_id',
                'available' => Schema::hasColumn('patients', 'county_id'),
            ],
            'facility_id' => [
                'column' => 'facility_id',
                'available' => Schema::hasColumn('patients', 'facility_id'),
            ],
        ];
    }

    private function buildDueForVlQuery(array $filters, array $filterConfig, bool $applyDateFilters = true): Builder
    {
        $baseQuery = DB::table('eac_sessions as es')
            ->selectRaw('es.patient_id, MAX(es.session_date) as last_session_date, MAX(COALESCE(es.next_session_date, es.session_date)) as due_date')
            ->where('es.session_number', 3)
            ->where('es.completion_status', 'Completed')
            ->groupBy('es.patient_id');

        $query = DB::query()
            ->fromSub($baseQuery, 'due')
            ->join('patients as p', 'p.patient_id', '=', 'due.patient_id');

        $this->applyLocationFilters($query, $filters, $filterConfig, 'p');

        if ($applyDateFilters) {
            $this->applyDateFilters($query, $filters, 'due.due_date');
        }

        return $query;
    }

    private function applyLocationFilters(Builder $query, array $filters, array $filterConfig, string $patientAlias = 'p'): void
    {
        foreach ($filterConfig as $key => $config) {
            if (($config['available'] ?? false) && ! empty($filters[$key])) {
                $query->where("{$patientAlias}.{$config['column']}", $filters[$key]);
            }
        }
    }

    private function applyDateFilters(Builder $query, array $filters, string $dateColumn): void
    {
        if (! empty($filters['due_date'])) {
            $query->whereDate($dateColumn, $filters['due_date']);
        }

        if (! empty($filters['month'])) {
            $query->whereMonth($dateColumn, (int) $filters['month']);
        }

        if (! empty($filters['quarter'])) {
            $quarter = max(1, min(4, (int) $filters['quarter']));

            $query->where(function (Builder $quarterQuery) use ($dateColumn, $quarter) {
                foreach ($this->quarterMonths($quarter) as $index => $month) {
                    $method = $index === 0 ? 'whereMonth' : 'orWhereMonth';
                    $quarterQuery->{$method}($dateColumn, $month);
                }
            });
        }

        if (! empty($filters['year'])) {
            $query->whereYear($dateColumn, (int) $filters['year']);
        }
    }

    private function periodLabel(array $filters): string
    {
        if (! empty($filters['due_date'])) {
            return Carbon::parse($filters['due_date'])->format('d M Y');
        }

        $parts = [];

        if (! empty($filters['quarter'])) {
            $parts[] = 'Q' . (int) $filters['quarter'];
        }

        if (! empty($filters['month'])) {
            $parts[] = Carbon::create(null, (int) $filters['month'], 1)->format('F');
        }

        if (! empty($filters['year'])) {
            $parts[] = (string) $filters['year'];
        }

        return $parts ? implode(' ', $parts) : 'All time';
    }

    private function quarterMonths(int $quarter): array
    {
        return match ($quarter) {
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            default => [10, 11, 12],
        };
    }

    private function safeCount(callable $callback): int
    {
        try {
            return (int) $callback();
        } catch (\Throwable $exception) {
            return 0;
        }
    }
}
