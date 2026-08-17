<?php

namespace App\Http\Controllers;

use App\Exports\DueForVlExport;
use App\Models\Patient;
use App\Services\ChatbotService;
use App\Services\DashboardSummaryService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardSummaryService $dashboardSummaryService,
        private readonly ChatbotService $chatbotService
    ) {
    }

    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $filterConfig = $this->getFilterConfig();
        $activeFilters = $request->only(['state_id', 'county_id', 'facility_id', 'due_date', 'month', 'quarter', 'year']);
        $filterQuery = array_filter($activeFilters, fn ($value) => $value !== null && $value !== '');
        $filterOptions = $this->getFilterOptions($filterConfig);
        $periodLabel = $this->periodLabel($request);
        $trendYear = (int) ($request->input('year') ?: Carbon::today()->year);

        $totalPatients = $this->safeCount(function () use ($request, $filterConfig) {
            $query = DB::table('patients as p');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyDateFilters($query, $request, 'p.art_start_date');

            return $query->count();
        });

        $totalVlDue = $this->safeCount(fn () => (clone $this->buildDueForVlQuery($request, $filterConfig))->count());

        $totalVlDueToday = $this->safeCount(function () use ($request, $filterConfig, $today) {
            return (clone $this->buildDueForVlQuery($request, $filterConfig, false))
                ->whereDate('due.due_date', $today)
                ->count();
        });

        $totalDueEAC = $this->safeCount(function () use ($request, $filterConfig) {
            $query = DB::table('viral_load_results as vl')
                ->join('patients as p', 'p.patient_id', '=', 'vl.patient_id')
                ->where('vl.result_cpml', '>=', 1000);
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyDateFilters($query, $request, 'vl.sample_date');

            return $query->distinct('vl.patient_id')->count('vl.patient_id');
        });

        $totalDueRepeatVL = $this->safeCount(function () use ($request, $filterConfig) {
            $query = DB::table('eac_sessions as es')
                ->join('patients as p', 'p.patient_id', '=', 'es.patient_id')
                ->where('es.session_number', 3)
                ->where('es.completion_status', 'Completed');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyDateFilters($query, $request, 'es.next_session_date');

            return $query->distinct('es.patient_id')->count('es.patient_id');
        });

        $totalAppointmentsToday = $this->safeCount(function () use ($request, $filterConfig, $today) {
            $query = DB::table('appointments as a')
                ->join('patients as p', 'p.patient_id', '=', 'a.patient_id')
                ->whereDate('a.appointment_date', $today);
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');

            return $query->count();
        });

        $totalSamplesCollected = $this->safeCount(function () use ($request, $filterConfig) {
            $query = DB::table('sample_collection as sc')
                ->join('patients as p', 'p.patient_id', '=', 'sc.patient_id');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyDateFilters($query, $request, 'sc.collection_date');

            return $query->count();
        });

        $totalSamplesRejected = $this->safeCount(function () use ($request, $filterConfig) {
            $query = DB::table('sample_rejections as sr')
                ->join('patients as p', 'p.patient_id', '=', 'sr.patient_id');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyDateFilters($query, $request, 'sr.rejection_date');

            return $query->count();
        });

        $appointmentStatus = $this->appointmentStatus($request, $filterConfig);
        $vlSuppression = $this->viralLoadSuppression($request, $filterConfig);
        $eacStatus = $this->eacStatus($request, $filterConfig);
        $vlDueTrend = $this->vlDueTrend($request, $filterConfig, $trendYear);
        $facilityDue = $this->facilityDue($request, $filterConfig);
        $ageMix = $this->ageMix($request, $filterConfig);

        $scheduled = $appointmentStatus['Scheduled'] ?? 0;
        $completed = $appointmentStatus['Completed'] ?? 0;
        $missed = $appointmentStatus['Missed'] ?? 0;
        $suppressed = $vlSuppression['Suppressed'] ?? 0;
        $unsuppressed = $vlSuppression['Unsuppressed'] ?? 0;
        $completedEAC = $eacStatus['Completed'] ?? 0;
        $ongoingEAC = ($eacStatus['Ongoing'] ?? 0) + ($eacStatus['Pending'] ?? 0);
        $totalHighVL = $unsuppressed;
        $highVL = $unsuppressed;
        $dueEAC = $totalDueEAC;
        $repeatVL = $totalDueRepeatVL;
        $supportSummary = $this->dashboardSummaryService->summary($activeFilters);
        $supportAccess = (bool) $request->user()?->canManageUsers();
        $assistantEnabled = $this->chatbotService->isConfigured();
        $assistantProvider = $this->chatbotService->providerLabel();

        return view('dashboard', compact(
            'totalPatients',
            'totalVlDueToday',
            'totalVlDue',
            'totalDueEAC',
            'totalDueRepeatVL',
            'totalAppointmentsToday',
            'totalSamplesCollected',
            'totalSamplesRejected',
            'totalHighVL',
            'scheduled',
            'completed',
            'missed',
            'suppressed',
            'unsuppressed',
            'completedEAC',
            'ongoingEAC',
            'highVL',
            'dueEAC',
            'repeatVL',
            'vlDueTrend',
            'facilityDue',
            'ageMix',
            'trendYear',
            'periodLabel',
            'filterConfig',
            'filterOptions',
            'activeFilters',
            'filterQuery',
            'supportAccess',
            'assistantEnabled',
            'assistantProvider'
        ));
    }

    public function dueForVlList(Request $request)
    {
        $filterConfig = $this->getFilterConfig();
        $dueForVlQuery = $this->buildDueForVlQuery($request, $filterConfig);
        $patients = $dueForVlQuery
            ->orderByDesc('due_date')
            ->orderBy('full_name')
            ->paginate(15)
            ->appends($request->query());

        $filterOptions = $this->getFilterOptions($filterConfig);
        $activeFilters = $request->only(['state_id', 'county_id', 'facility_id', 'due_date', 'month', 'quarter', 'year']);
        $filterQuery = array_filter($activeFilters, fn ($value) => $value !== null && $value !== '');

        return view('dashboard.vl_due_list', compact(
            'patients',
            'filterConfig',
            'filterOptions',
            'activeFilters',
            'filterQuery'
        ));
    }

    public function exportDueForVlList(Request $request)
    {
        $filterConfig = $this->getFilterConfig();
        $rows = $this->buildDueForVlQuery($request, $filterConfig)
            ->orderByDesc('due_date')
            ->orderBy('full_name')
            ->get();

        return Excel::download(
            new DueForVlExport($rows, $filterConfig),
            'patients_due_for_vl.xlsx'
        );
    }

    private function getFilterConfig(): array
    {
        return [
            'state_id' => [
                'label' => 'State',
                'column' => 'state_id',
                'available' => Schema::hasColumn('patients', 'state_id'),
            ],
            'county_id' => [
                'label' => 'County',
                'column' => 'county_id',
                'available' => Schema::hasColumn('patients', 'county_id'),
            ],
            'facility_id' => [
                'label' => 'Facility',
                'column' => 'facility_id',
                'available' => Schema::hasColumn('patients', 'facility_id'),
            ],
        ];
    }

    private function getFilterOptions(array $filterConfig): array
    {
        $options = [];
        $lookupTables = [
            'state_id' => ['table' => 'states', 'id' => 'state_id', 'label' => 'state_name'],
            'county_id' => ['table' => 'counties', 'id' => 'county_id', 'label' => 'county_name'],
            'facility_id' => ['table' => 'facilities', 'id' => 'facility_id', 'label' => 'facility_name'],
        ];

        foreach ($filterConfig as $key => $config) {
            $lookup = $lookupTables[$key];

            if (! $config['available'] && ! Schema::hasTable($lookup['table'])) {
                $options[$key] = [];
                continue;
            }

            if (Schema::hasTable($lookup['table'])) {
                $idColumn = $this->lookupColumn($lookup['table'], [$lookup['id'], 'id']);
                $labelColumn = $this->lookupColumn($lookup['table'], [$lookup['label'], 'name']);

                if ($idColumn && $labelColumn) {
                    $options[$key] = DB::table($lookup['table'])
                    ->whereNotNull($labelColumn)
                    ->distinct()
                    ->orderBy($labelColumn)
                    ->get([$idColumn . ' as value', $labelColumn . ' as label'])
                    ->map(fn ($option) => [
                        'value' => (string) $option->value,
                        'label' => (string) $option->label,
                    ])
                    ->values()
                    ->all();

                    continue;
                }
            }

            $options[$key] = Patient::query()
                    ->whereNotNull($config['column'])
                    ->distinct()
                    ->orderBy($config['column'])
                    ->pluck($config['column'])
                    ->map(fn ($value) => [
                        'value' => (string) $value,
                        'label' => (string) $value,
                    ])
                    ->values()
                    ->all();
        }

        return $options;
    }

    private function lookupColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function buildDueForVlQuery(Request $request, array $filterConfig, bool $applyDateFilters = true): Builder
    {
        $sessionDateExpression = 'MAX(es.session_date)';
        $dueDateExpression = 'MAX(COALESCE(es.next_session_date, es.session_date))';

        $baseQuery = DB::table('eac_sessions as es')
            ->selectRaw("
                es.patient_id,
                MAX(es.eac_id) as eac_id,
                {$sessionDateExpression} as last_session_date,
                {$dueDateExpression} as due_date
            ")
            ->where('es.session_number', 3)
            ->where('es.completion_status', 'Completed')
            ->groupBy('es.patient_id');

        $query = DB::query()
            ->fromSub($baseQuery, 'due')
            ->join('patients as p', 'p.patient_id', '=', 'due.patient_id')
            ->select([
                'p.patient_id',
                'p.art_number',
                'p.first_name',
                'p.last_name',
                DB::raw($this->fullNameExpression() . ' as full_name'),
                'p.sex',
                'p.phone',
                'due.eac_id',
                'due.last_session_date',
                'due.due_date',
            ]);

        foreach ($filterConfig as $key => $config) {
            if ($config['available']) {
                $query->addSelect("p.{$config['column']} as {$key}");
            }
        }

        $this->applyLocationFilters($query, $request, $filterConfig, 'p');

        if ($applyDateFilters) {
            $this->applyDateFilters($query, $request, 'due.due_date');
        }

        return $query;
    }

    private function applyLocationFilters(Builder $query, Request $request, array $filterConfig, string $patientAlias = 'p'): void
    {
        foreach ($filterConfig as $key => $config) {
            if ($config['available'] && $request->filled($key)) {
                $query->where("{$patientAlias}.{$config['column']}", $request->input($key));
            }
        }
    }

    private function applyDateFilters(Builder $query, Request $request, string $dateColumn): void
    {
        if ($request->filled('due_date')) {
            $query->whereDate($dateColumn, $request->input('due_date'));
        }

        if ($request->filled('month')) {
            $query->whereMonth($dateColumn, (int) $request->input('month'));
        }

        if ($request->filled('quarter')) {
            $quarter = max(1, min(4, (int) $request->input('quarter')));
            $query->where(function (Builder $quarterQuery) use ($dateColumn, $quarter) {
                foreach ($this->quarterMonths($quarter) as $index => $month) {
                    $method = $index === 0 ? 'whereMonth' : 'orWhereMonth';
                    $quarterQuery->{$method}($dateColumn, $month);
                }
            });
        }

        if ($request->filled('year')) {
            $query->whereYear($dateColumn, (int) $request->input('year'));
        }
    }

    private function appointmentStatus(Request $request, array $filterConfig): array
    {
        return $this->safeArray(function () use ($request, $filterConfig) {
            $query = DB::table('appointments as a')
                ->join('patients as p', 'p.patient_id', '=', 'a.patient_id')
                ->selectRaw("COALESCE(a.status, 'Scheduled') as status, COUNT(*) as total")
                ->groupBy('status');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyDateFilters($query, $request, 'a.appointment_date');

            return $query->pluck('total', 'status')->map(fn ($value) => (int) $value)->all();
        });
    }

    private function viralLoadSuppression(Request $request, array $filterConfig): array
    {
        return $this->safeArray(function () use ($request, $filterConfig) {
            $query = DB::table('viral_load_results as vl')
                ->join('patients as p', 'p.patient_id', '=', 'vl.patient_id')
                ->selectRaw("CASE WHEN vl.result_cpml >= 1000 THEN 'Unsuppressed' ELSE 'Suppressed' END as status, COUNT(*) as total")
                ->whereNotNull('vl.result_cpml')
                ->groupBy('status');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyDateFilters($query, $request, 'vl.sample_date');

            return $query->pluck('total', 'status')->map(fn ($value) => (int) $value)->all();
        });
    }

    private function eacStatus(Request $request, array $filterConfig): array
    {
        return $this->safeArray(function () use ($request, $filterConfig) {
            $query = DB::table('eac_sessions as es')
                ->join('patients as p', 'p.patient_id', '=', 'es.patient_id')
                ->selectRaw("COALESCE(es.completion_status, 'Pending') as status, COUNT(*) as total")
                ->groupBy('status');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyDateFilters($query, $request, 'es.session_date');

            return $query->pluck('total', 'status')->map(fn ($value) => (int) $value)->all();
        });
    }

    private function vlDueTrend(Request $request, array $filterConfig, int $year): array
    {
        return $this->safeArray(function () use ($request, $filterConfig, $year) {
            $query = $this->buildDueForVlQuery($request, $filterConfig, false)
                ->whereYear('due.due_date', $year)
                ->get(['due_date']);

            $counts = array_fill(1, 12, 0);

            foreach ($query as $row) {
                if ($row->due_date) {
                    $month = (int) Carbon::parse($row->due_date)->format('n');
                    $counts[$month]++;
                }
            }

            return [
                'labels' => collect(range(1, 12))->map(fn ($month) => Carbon::create(null, $month, 1)->format('M'))->all(),
                'values' => array_values($counts),
            ];
        }, [
            'labels' => [],
            'values' => [],
        ]);
    }

    private function facilityDue(Request $request, array $filterConfig): array
    {
        if (! ($filterConfig['facility_id']['available'] ?? false)) {
            return ['labels' => [], 'values' => []];
        }

        return $this->safeArray(function () use ($request, $filterConfig) {
            $rows = $this->buildDueForVlQuery($request, $filterConfig)
                ->get(['facility_id']);

            $grouped = $rows
                ->groupBy(fn ($row) => $row->facility_id ?: 'Unassigned')
                ->map(fn ($items) => $items->count())
                ->sortDesc()
                ->take(6);

            return [
                'labels' => $grouped->keys()->values()->all(),
                'values' => $grouped->values()->all(),
            ];
        }, [
            'labels' => [],
            'values' => [],
        ]);
    }

    private function ageMix(Request $request, array $filterConfig): array
    {
        return $this->safeArray(function () use ($request, $filterConfig) {
            if (Schema::hasColumn('patients', 'age')) {
                $query = DB::table('patients as p')
                    ->selectRaw("COALESCE(CAST(p.age as char), 'Not captured') as age_label, COUNT(*) as total")
                    ->groupBy('age_label')
                    ->orderByDesc('total');
            } else {
                $query = DB::table('patients as p')
                    ->selectRaw("COALESCE(p.age_category, 'Not captured') as age_label, COUNT(*) as total")
                    ->groupBy('age_label')
                    ->orderByDesc('total');
            }
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyDateFilters($query, $request, 'p.art_start_date');

            $rows = $query->limit(6)->pluck('total', 'age_label');

            return [
                'labels' => $rows->keys()->values()->all(),
                'values' => $rows->map(fn ($value) => (int) $value)->values()->all(),
            ];
        }, [
            'labels' => [],
            'values' => [],
        ]);
    }

    private function periodLabel(Request $request): string
    {
        if ($request->filled('due_date')) {
            return Carbon::parse($request->input('due_date'))->format('d M Y');
        }

        $parts = [];

        if ($request->filled('quarter')) {
            $parts[] = 'Q' . (int) $request->input('quarter');
        }

        if ($request->filled('month')) {
            $parts[] = Carbon::create(null, (int) $request->input('month'), 1)->format('F');
        }

        if ($request->filled('year')) {
            $parts[] = (string) $request->input('year');
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

    private function fullNameExpression(): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "TRIM(COALESCE(p.first_name, '') || ' ' || COALESCE(p.last_name, ''))";
        }

        return "TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, '')))";
    }

    private function safeCount(callable $callback): int
    {
        try {
            return (int) $callback();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function safeArray(callable $callback, array $fallback = []): array
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
}
