<?php

namespace App\Http\Controllers;

use App\Exports\PatientsExport;
use App\Exports\ReportSummaryExport;
use App\Exports\ViralLoadExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filterConfig = $this->getFilterConfig();
        $filterOptions = $this->getFilterOptions($filterConfig);
        $activeFilters = $this->activeFilters($request);
        $filterQuery = array_filter($activeFilters, fn ($value) => $value !== null && $value !== '');
        $summary = $this->summaryMetrics($request, $filterConfig);
        $analytics = [
            'patientSexMix' => $this->patientSexMix($request, $filterConfig),
            'patientAgeMix' => $this->patientAgeMix($request, $filterConfig),
            'viralLoadStatusMix' => $this->viralLoadStatusMix($summary['suppressed_raw'], $summary['unsuppressed_raw']),
            'monthlyViralLoads' => $this->monthlyViralLoads($request, $filterConfig, $activeFilters),
            'testingIndications' => $this->testingIndications($request, $filterConfig),
            'facilityCoverage' => $this->facilityCoverage($request, $filterConfig),
        ];
        $summaryReport = $this->createReportManifest(
            'Summary Report',
            $activeFilters,
            $filterOptions,
            $summary,
            $summary['totalPatientsRaw'] + $summary['totalViralLoadsRaw']
        );

        return view('reports.index', [
            'summary' => $summary,
            'analytics' => $analytics,
            'filterConfig' => $filterConfig,
            'filterOptions' => $filterOptions,
            'activeFilters' => $activeFilters,
            'filterQuery' => $filterQuery,
            'summaryReport' => $summaryReport,
        ]);
    }

    public function summaryPDF(Request $request)
    {
        $filterConfig = $this->getFilterConfig();
        $filterOptions = $this->getFilterOptions($filterConfig);
        $activeFilters = $this->activeFilters($request);
        $summary = $this->summaryMetrics($request, $filterConfig);
        $analytics = [
            'patientSexMix' => $this->patientSexMix($request, $filterConfig),
            'patientAgeMix' => $this->patientAgeMix($request, $filterConfig),
            'viralLoadStatusMix' => $this->viralLoadStatusMix($summary['suppressed_raw'], $summary['unsuppressed_raw']),
            'monthlyViralLoads' => $this->monthlyViralLoads($request, $filterConfig, $activeFilters),
            'testingIndications' => $this->testingIndications($request, $filterConfig),
            'facilityCoverage' => $this->facilityCoverage($request, $filterConfig),
        ];
        $report = $this->createReportManifest(
            'Summary Report',
            $activeFilters,
            $filterOptions,
            $summary,
            $summary['totalPatientsRaw'] + $summary['totalViralLoadsRaw']
        );

        return Pdf::loadView('reports.summary_pdf', compact('summary', 'analytics', 'report'))
            ->setPaper('a4', 'portrait')
            ->download('vilocare_summary_report_' . Str::lower($report['reference']) . '.pdf');
    }

    public function summaryExcel(Request $request)
    {
        $filterConfig = $this->getFilterConfig();
        $filterOptions = $this->getFilterOptions($filterConfig);
        $activeFilters = $this->activeFilters($request);
        $summary = $this->summaryMetrics($request, $filterConfig);
        $report = $this->createReportManifest(
            'Summary Report',
            $activeFilters,
            $filterOptions,
            $summary,
            $summary['totalPatientsRaw'] + $summary['totalViralLoadsRaw']
        );

        return Excel::download(
            new ReportSummaryExport($summary, $report),
            'vilocare_summary_report_' . Str::lower($report['reference']) . '.xlsx'
        );
    }

    public function patientsPDF(Request $request)
    {
        $filterConfig = $this->getFilterConfig();
        $filterOptions = $this->getFilterOptions($filterConfig);
        $activeFilters = $this->activeFilters($request);
        $summary = $this->summaryMetrics($request, $filterConfig);
        $patients = $this->patientRows($request, $filterConfig);
        $report = $this->createReportManifest(
            'Patients Report',
            $activeFilters,
            $filterOptions,
            $summary,
            $patients->count()
        );

        return Pdf::loadView('reports.patients_pdf', compact('patients', 'report'))
            ->setPaper('a4', 'landscape')
            ->download('vilocare_patients_report_' . Str::lower($report['reference']) . '.pdf');
    }

    public function patientsExcel(Request $request)
    {
        $filterConfig = $this->getFilterConfig();
        $filterOptions = $this->getFilterOptions($filterConfig);
        $activeFilters = $this->activeFilters($request);
        $summary = $this->summaryMetrics($request, $filterConfig);
        $patients = $this->patientRows($request, $filterConfig);
        $report = $this->createReportManifest(
            'Patients Report',
            $activeFilters,
            $filterOptions,
            $summary,
            $patients->count()
        );

        return Excel::download(
            new PatientsExport($patients, $report),
            'vilocare_patients_report_' . Str::lower($report['reference']) . '.xlsx'
        );
    }

    public function viralLoadPDF(Request $request)
    {
        $filterConfig = $this->getFilterConfig();
        $filterOptions = $this->getFilterOptions($filterConfig);
        $activeFilters = $this->activeFilters($request);
        $summary = $this->summaryMetrics($request, $filterConfig);
        $results = $this->viralLoadRows($request, $filterConfig);
        $report = $this->createReportManifest(
            'Viral Load Report',
            $activeFilters,
            $filterOptions,
            $summary,
            $results->count()
        );

        return Pdf::loadView('reports.viral_load_pdf', compact('results', 'report'))
            ->setPaper('a4', 'landscape')
            ->download('vilocare_viral_load_report_' . Str::lower($report['reference']) . '.pdf');
    }

    public function viralLoadExcel(Request $request)
    {
        $filterConfig = $this->getFilterConfig();
        $filterOptions = $this->getFilterOptions($filterConfig);
        $activeFilters = $this->activeFilters($request);
        $summary = $this->summaryMetrics($request, $filterConfig);
        $results = $this->viralLoadRows($request, $filterConfig);
        $report = $this->createReportManifest(
            'Viral Load Report',
            $activeFilters,
            $filterOptions,
            $summary,
            $results->count()
        );

        return Excel::download(
            new ViralLoadExport($results, $report),
            'vilocare_viral_load_report_' . Str::lower($report['reference']) . '.xlsx'
        );
    }

    public function verify(Request $request, ?string $reference = null)
    {
        $reference = strtoupper(trim((string) ($reference ?: $request->query('reference', ''))));
        $report = $reference !== '' ? Cache::get($this->reportCacheKey($reference)) : null;

        if (is_array($report)) {
            $report['barcode_svg'] = $this->barcodeSvg($report['reference']);
        }

        return view('reports.verify', [
            'reference' => $reference,
            'report' => $report,
        ]);
    }

    private function summaryMetrics(Request $request, array $filterConfig): array
    {
        $totalPatients = $this->safeCount(function () use ($request, $filterConfig) {
            $query = DB::table('patients as p');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyPeriodFilters($query, $request, 'p.art_start_date');

            return $query->count();
        });

        $totalViralLoads = $this->safeCount(function () use ($request, $filterConfig) {
            $query = DB::table('viral_load_results as vl')
                ->join('patients as p', 'p.patient_id', '=', 'vl.patient_id');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyPeriodFilters($query, $request, 'vl.sample_date');

            return $query->count();
        });

        $suppressed = $this->safeCount(function () use ($request, $filterConfig) {
            $query = DB::table('viral_load_results as vl')
                ->join('patients as p', 'p.patient_id', '=', 'vl.patient_id')
                ->where('vl.result_cpml', '<', 1000);
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyPeriodFilters($query, $request, 'vl.sample_date');

            return $query->count();
        });

        $unsuppressed = $this->safeCount(function () use ($request, $filterConfig) {
            $query = DB::table('viral_load_results as vl')
                ->join('patients as p', 'p.patient_id', '=', 'vl.patient_id')
                ->where('vl.result_cpml', '>=', 1000);
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyPeriodFilters($query, $request, 'vl.sample_date');

            return $query->count();
        });

        $latestResultDate = $this->safeValue(function () use ($request, $filterConfig) {
            $query = DB::table('viral_load_results as vl')
                ->join('patients as p', 'p.patient_id', '=', 'vl.patient_id');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyPeriodFilters($query, $request, 'vl.sample_date');

            return $query->max('vl.result_date');
        });

        $coveredFacilities = $this->safeCount(function () use ($request, $filterConfig) {
            if (! ($filterConfig['facility_id']['available'] ?? false)) {
                return 0;
            }

            $query = DB::table('patients as p')
                ->whereNotNull('p.facility_id')
                ->distinct('p.facility_id');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyPeriodFilters($query, $request, 'p.art_start_date');

            return $query->count('p.facility_id');
        });

        $coveredCounties = $this->safeCount(function () use ($request, $filterConfig) {
            if (! ($filterConfig['county_id']['available'] ?? false)) {
                return 0;
            }

            $query = DB::table('patients as p')
                ->whereNotNull('p.county_id')
                ->distinct('p.county_id');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyPeriodFilters($query, $request, 'p.art_start_date');

            return $query->count('p.county_id');
        });

        return [
            'totalPatients' => number_format($totalPatients),
            'totalPatientsRaw' => $totalPatients,
            'totalViralLoads' => number_format($totalViralLoads),
            'totalViralLoadsRaw' => $totalViralLoads,
            'suppressed' => number_format($suppressed),
            'suppressed_raw' => $suppressed,
            'unsuppressed' => number_format($unsuppressed),
            'unsuppressed_raw' => $unsuppressed,
            'suppressionRate' => $this->percentage($suppressed, $suppressed + $unsuppressed),
            'latestResultDate' => $latestResultDate ? Carbon::parse($latestResultDate)->format('d M Y') : 'No data',
            'coveredFacilities' => number_format($coveredFacilities),
            'coveredFacilitiesRaw' => $coveredFacilities,
            'coveredCounties' => number_format($coveredCounties),
            'coveredCountiesRaw' => $coveredCounties,
            'periodLabel' => $this->periodLabel($request),
        ];
    }

    private function patientRows(Request $request, array $filterConfig)
    {
        $query = DB::table('patients as p')
            ->select([
                'p.art_number',
                'p.first_name',
                'p.last_name',
                'p.sex',
                'p.phone',
                'p.art_start_date',
            ])
            ->selectRaw($this->fullNameExpression('p') . ' as full_name');

        $this->joinLocationLookups($query, 'p');
        $this->applyLocationFilters($query, $request, $filterConfig, 'p');
        $this->applyPeriodFilters($query, $request, 'p.art_start_date');

        return $query->orderBy('p.first_name')->orderBy('p.last_name')->get();
    }

    private function viralLoadRows(Request $request, array $filterConfig)
    {
        $query = DB::table('viral_load_results as vl')
            ->join('patients as p', 'p.patient_id', '=', 'vl.patient_id')
            ->select([
                'p.art_number',
                'p.first_name',
                'p.last_name',
                'vl.sample_date',
                'vl.result_date',
                'vl.result_cpml',
                'vl.result_log',
                'vl.vl_testing_indication',
            ])
            ->selectRaw($this->fullNameExpression('p') . ' as full_name')
            ->selectRaw("CASE WHEN vl.result_cpml >= 1000 THEN 'Unsuppressed' ELSE 'Suppressed' END as viral_load_status");

        $this->joinLocationLookups($query, 'p');
        $this->applyLocationFilters($query, $request, $filterConfig, 'p');
        $this->applyPeriodFilters($query, $request, 'vl.sample_date');

        return $query->orderByDesc('vl.sample_date')->orderBy('p.first_name')->get();
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

            if (! $config['available']) {
                $options[$key] = [];
                continue;
            }

            $options[$key] = DB::table('patients')
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

    private function activeFilters(Request $request): array
    {
        $period = (string) $request->input('period', 'all');
        $allowedPeriods = ['all', 'day', 'month', 'quarter', 'year'];

        return [
            'state_id' => $request->input('state_id'),
            'county_id' => $request->input('county_id'),
            'facility_id' => $request->input('facility_id'),
            'period' => in_array($period, $allowedPeriods, true) ? $period : 'all',
            'period_date' => $request->input('period_date'),
            'month' => $request->input('month'),
            'quarter' => $request->input('quarter'),
            'year' => $request->input('year'),
        ];
    }

    private function applyLocationFilters(Builder $query, Request $request, array $filterConfig, string $patientAlias = 'p'): void
    {
        foreach ($filterConfig as $key => $config) {
            if ($config['available'] && $request->filled($key)) {
                $query->where("{$patientAlias}.{$config['column']}", $request->input($key));
            }
        }
    }

    private function applyPeriodFilters(Builder $query, Request $request, string $dateColumn): void
    {
        $period = (string) $request->input('period', 'all');

        if ($period === 'day' && $request->filled('period_date')) {
            $query->whereDate($dateColumn, $request->input('period_date'));
            return;
        }

        if ($period === 'month' && $request->filled('month')) {
            $year = (int) ($request->input('year') ?: Carbon::today()->year);
            $query->whereYear($dateColumn, $year)->whereMonth($dateColumn, (int) $request->input('month'));
            return;
        }

        if ($period === 'quarter' && $request->filled('quarter')) {
            $year = (int) ($request->input('year') ?: Carbon::today()->year);
            $quarter = max(1, min(4, (int) $request->input('quarter')));
            $query->whereYear($dateColumn, $year)
                ->where(function (Builder $quarterQuery) use ($dateColumn, $quarter) {
                    foreach ($this->quarterMonths($quarter) as $index => $month) {
                        $method = $index === 0 ? 'whereMonth' : 'orWhereMonth';
                        $quarterQuery->{$method}($dateColumn, $month);
                    }
                });
            return;
        }

        if ($period === 'year' && $request->filled('year')) {
            $query->whereYear($dateColumn, (int) $request->input('year'));
        }
    }

    private function periodLabel(Request $request): string
    {
        return match ((string) $request->input('period', 'all')) {
            'day' => $request->filled('period_date')
                ? Carbon::parse($request->input('period_date'))->format('d M Y')
                : 'Selected day',
            'month' => $request->filled('month')
                ? Carbon::create(null, (int) $request->input('month'), 1)->format('F') . ' ' . ($request->input('year') ?: Carbon::today()->year)
                : 'Selected month',
            'quarter' => $request->filled('quarter')
                ? 'Q' . (int) $request->input('quarter') . ' ' . ($request->input('year') ?: Carbon::today()->year)
                : 'Selected quarter',
            'year' => $request->filled('year')
                ? (string) $request->input('year')
                : (string) Carbon::today()->year,
            default => 'All time',
        };
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

    private function patientSexMix(Request $request, array $filterConfig): array
    {
        return $this->safeArray(function () use ($request, $filterConfig) {
            $query = DB::table('patients as p')
                ->selectRaw('COALESCE(p.sex, "Not captured") as label, COUNT(*) as total')
                ->groupBy('label');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyPeriodFilters($query, $request, 'p.art_start_date');

            $rows = $query->orderByDesc('total')->get();

            return [
                'labels' => $rows->pluck('label')->map(fn ($label) => (string) $label)->all(),
                'values' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
            ];
        }, $this->emptyDataset());
    }

    private function patientAgeMix(Request $request, array $filterConfig): array
    {
        return $this->safeArray(function () use ($request, $filterConfig) {
            $query = DB::table('patients as p')
                ->selectRaw('COALESCE(CAST(p.age as char), "Not captured") as label, COUNT(*) as total')
                ->groupBy('label');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyPeriodFilters($query, $request, 'p.art_start_date');

            $rows = $query->orderByDesc('total')->limit(6)->get();

            return [
                'labels' => $rows->pluck('label')->map(fn ($label) => (string) $label)->all(),
                'values' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
            ];
        }, $this->emptyDataset());
    }

    private function viralLoadStatusMix(int $suppressed, int $unsuppressed): array
    {
        return [
            'labels' => ['Suppressed', 'Unsuppressed'],
            'values' => [$suppressed, $unsuppressed],
        ];
    }

    private function monthlyViralLoads(Request $request, array $filterConfig, array $activeFilters): array
    {
        $year = (int) ($activeFilters['year'] ?: Carbon::today()->year);
        $labels = collect(range(1, 12))
            ->map(fn ($month) => Carbon::create($year, $month, 1)->format('M'))
            ->all();

        return $this->safeArray(function () use ($request, $filterConfig, $year, $labels) {
            $query = DB::table('viral_load_results as vl')
                ->join('patients as p', 'p.patient_id', '=', 'vl.patient_id')
                ->selectRaw('MONTH(vl.sample_date) as month_number, COUNT(*) as total')
                ->whereNotNull('vl.sample_date')
                ->whereYear('vl.sample_date', $year)
                ->groupBy('month_number');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');

            $counts = array_fill(1, 12, 0);
            foreach ($query->get() as $row) {
                $counts[(int) $row->month_number] = (int) $row->total;
            }

            return [
                'labels' => $labels,
                'values' => array_values($counts),
            ];
        }, [
            'labels' => $labels,
            'values' => array_fill(0, 12, 0),
        ]);
    }

    private function testingIndications(Request $request, array $filterConfig): array
    {
        return $this->safeArray(function () use ($request, $filterConfig) {
            $query = DB::table('viral_load_results as vl')
                ->join('patients as p', 'p.patient_id', '=', 'vl.patient_id')
                ->selectRaw('COALESCE(NULLIF(vl.vl_testing_indication, ""), "Not captured") as label, COUNT(*) as total')
                ->groupBy('label');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyPeriodFilters($query, $request, 'vl.sample_date');

            $rows = $query->orderByDesc('total')->limit(6)->get();

            return [
                'labels' => $rows->pluck('label')->map(fn ($label) => (string) $label)->all(),
                'values' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
            ];
        }, $this->emptyDataset());
    }

    private function facilityCoverage(Request $request, array $filterConfig): array
    {
        if (! ($filterConfig['facility_id']['available'] ?? false)) {
            return $this->emptyDataset();
        }

        return $this->safeArray(function () use ($request, $filterConfig) {
            $labelExpression = 'CAST(p.facility_id as char)';
            $query = DB::table('patients as p');

            if (Schema::hasTable('facilities')) {
                $facilityIdColumn = $this->lookupColumn('facilities', ['facility_id', 'id']);
                $facilityLabelColumn = $this->lookupColumn('facilities', ['facility_name', 'name']);

                if ($facilityIdColumn && $facilityLabelColumn) {
                    $query->leftJoin('facilities as f', "f.{$facilityIdColumn}", '=', 'p.facility_id');
                    $labelExpression = "f.{$facilityLabelColumn}";
                }
            }

            $query->selectRaw("COALESCE({$labelExpression}, CAST(p.facility_id as char), 'Unassigned') as label, COUNT(*) as total");
            $query->groupBy('label');
            $this->applyLocationFilters($query, $request, $filterConfig, 'p');
            $this->applyPeriodFilters($query, $request, 'p.art_start_date');

            $rows = $query->orderByDesc('total')->limit(6)->get();

            return [
                'labels' => $rows->pluck('label')->map(fn ($label) => (string) $label)->all(),
                'values' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
            ];
        }, $this->emptyDataset());
    }

    private function createReportManifest(
        string $title,
        array $activeFilters,
        array $filterOptions,
        array $summary,
        int $recordCount
    ): array {
        $timestamp = now();
        $reference = 'VC-' . strtoupper(Str::slug($title, '-')) . '-' . $timestamp->format('YmdHis') . '-' . Str::upper(Str::random(4));
        $filters = $this->formattedFilters($activeFilters, $filterOptions);
        $report = [
            'title' => $title,
            'reference' => $reference,
            'generated_at' => $timestamp->toIso8601String(),
            'generated_at_human' => $timestamp->format('d M Y H:i:s'),
            'website' => rtrim((string) config('app.url'), '/'),
            'filters' => $filters,
            'record_count' => $recordCount,
            'summary_cards' => [
                ['label' => 'Patients', 'value' => $summary['totalPatients']],
                ['label' => 'Viral Loads', 'value' => $summary['totalViralLoads']],
                ['label' => 'Suppression Rate', 'value' => $summary['suppressionRate']],
                ['label' => 'Facilities', 'value' => $summary['coveredFacilities']],
            ],
        ];

        $report['verification_url'] = route('reports.verify.reference', ['reference' => $reference]);
        $report['barcode_svg'] = $this->barcodeSvg($reference);
        $report['logo_image'] = extension_loaded('gd') ? $this->logoImageDataUri() : null;
        $report['logo_path'] = public_path('images/vilocarelogo.png');
        $report['logo_svg'] = $this->logoSvg();
        $report['footer_text'] = 'Report produced by ViLoCare on ' . $report['generated_at_human'];

        Cache::put($this->reportCacheKey($reference), $report, now()->addDays(30));

        return $report;
    }

    private function formattedFilters(array $activeFilters, array $filterOptions): array
    {
        $filters = [
            'State' => $this->resolveFilterLabel('state_id', $activeFilters['state_id'] ?? null, $filterOptions),
            'County' => $this->resolveFilterLabel('county_id', $activeFilters['county_id'] ?? null, $filterOptions),
            'Facility' => $this->resolveFilterLabel('facility_id', $activeFilters['facility_id'] ?? null, $filterOptions),
            'Period' => $this->formattedPeriod($activeFilters),
        ];

        return array_filter($filters, fn ($value) => $value !== null && $value !== '');
    }

    private function resolveFilterLabel(string $key, mixed $value, array $filterOptions): string
    {
        if ($value === null || $value === '') {
            return 'All';
        }

        foreach ($filterOptions[$key] ?? [] as $option) {
            if ((string) $option['value'] === (string) $value) {
                return (string) $option['label'];
            }
        }

        return (string) $value;
    }

    private function formattedPeriod(array $activeFilters): string
    {
        return match ($activeFilters['period'] ?? 'all') {
            'day' => ! empty($activeFilters['period_date'])
                ? Carbon::parse($activeFilters['period_date'])->format('d M Y')
                : 'Selected day',
            'month' => ! empty($activeFilters['month'])
                ? Carbon::create(null, (int) $activeFilters['month'], 1)->format('F') . ' ' . ($activeFilters['year'] ?: Carbon::today()->year)
                : 'Selected month',
            'quarter' => ! empty($activeFilters['quarter'])
                ? 'Quarter ' . (int) $activeFilters['quarter'] . ' ' . ($activeFilters['year'] ?: Carbon::today()->year)
                : 'Selected quarter',
            'year' => ! empty($activeFilters['year']) ? (string) $activeFilters['year'] : (string) Carbon::today()->year,
            default => 'All time',
        };
    }

    private function joinLocationLookups(Builder $query, string $patientAlias): void
    {
        if (Schema::hasTable('states')) {
            $stateIdColumn = $this->lookupColumn('states', ['state_id', 'id']);
            $stateLabelColumn = $this->lookupColumn('states', ['state_name', 'name']);

            if ($stateIdColumn && $stateLabelColumn) {
                $query->leftJoin('states as s', "s.{$stateIdColumn}", '=', "{$patientAlias}.state_id");
                $query->addSelect(DB::raw("COALESCE(s.{$stateLabelColumn}, CAST({$patientAlias}.state_id as char), 'Unassigned') as state_name"));
            }
        }

        if (Schema::hasTable('counties')) {
            $countyIdColumn = $this->lookupColumn('counties', ['county_id', 'id']);
            $countyLabelColumn = $this->lookupColumn('counties', ['county_name', 'name']);

            if ($countyIdColumn && $countyLabelColumn) {
                $query->leftJoin('counties as c', "c.{$countyIdColumn}", '=', "{$patientAlias}.county_id");
                $query->addSelect(DB::raw("COALESCE(c.{$countyLabelColumn}, CAST({$patientAlias}.county_id as char), 'Unassigned') as county_name"));
            }
        }

        if (Schema::hasTable('facilities')) {
            $facilityIdColumn = $this->lookupColumn('facilities', ['facility_id', 'id']);
            $facilityLabelColumn = $this->lookupColumn('facilities', ['facility_name', 'name']);

            if ($facilityIdColumn && $facilityLabelColumn) {
                $query->leftJoin('facilities as f', "f.{$facilityIdColumn}", '=', "{$patientAlias}.facility_id");
                $query->addSelect(DB::raw("COALESCE(f.{$facilityLabelColumn}, CAST({$patientAlias}.facility_id as char), 'Unassigned') as facility_name"));
            }
        }
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

    private function fullNameExpression(string $alias): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "TRIM(COALESCE({$alias}.first_name, '') || ' ' || COALESCE({$alias}.last_name, ''))";
        }

        return "TRIM(CONCAT(COALESCE({$alias}.first_name, ''), ' ', COALESCE({$alias}.last_name, '')))";
    }

    private function percentage(int $value, int $total): string
    {
        if ($total <= 0) {
            return '0%';
        }

        return number_format(($value / $total) * 100, 1) . '%';
    }

    private function emptyDataset(): array
    {
        return [
            'labels' => [],
            'values' => [],
        ];
    }

    private function reportCacheKey(string $reference): string
    {
        return 'report_manifest:' . $reference;
    }

    private function logoSvg(): string
    {
        return <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 64" role="img" aria-label="ViLoCare Logo">
  <defs>
    <linearGradient id="vilocareLogoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0f9f8f" />
      <stop offset="100%" stop-color="#2d73b9" />
    </linearGradient>
  </defs>
  <rect x="2" y="2" rx="16" ry="16" width="60" height="60" fill="url(#vilocareLogoGradient)" />
  <path d="M18 20h11l8 21 8-21h11L42 48h-10L18 20z" fill="#ffffff" />
  <text x="78" y="28" font-family="DejaVu Sans, Arial, sans-serif" font-size="20" font-weight="700" fill="#163046">ViLoCare</text>
  <text x="78" y="46" font-family="DejaVu Sans, Arial, sans-serif" font-size="10" letter-spacing="1.6" fill="#5f6f84">STANDARD REPORT</text>
</svg>
SVG;
    }

    private function logoImageDataUri(): ?string
    {
        $path = public_path('images/vilocarelogo.png');

        if (! is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $mime = @mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function barcodeSvg(string $value): string
    {
        $patterns = [
            '0' => 'nnnwwnwnn',
            '1' => 'wnnwnnnnw',
            '2' => 'nnwwnnnnw',
            '3' => 'wnwwnnnnn',
            '4' => 'nnnwwnnnw',
            '5' => 'wnnwwnnnn',
            '6' => 'nnwwwnnnn',
            '7' => 'nnnwnnwnw',
            '8' => 'wnnwnnwnn',
            '9' => 'nnwwnnwnn',
            'A' => 'wnnnnwnnw',
            'B' => 'nnwnnwnnw',
            'C' => 'wnwnnwnnn',
            'D' => 'nnnnwwnnw',
            'E' => 'wnnnwwnnn',
            'F' => 'nnwnwwnnn',
            'G' => 'nnnnnwwnw',
            'H' => 'wnnnnwwnn',
            'I' => 'nnwnnwwnn',
            'J' => 'nnnnwwwnn',
            'K' => 'wnnnnnnww',
            'L' => 'nnwnnnnww',
            'M' => 'wnwnnnnwn',
            'N' => 'nnnnwnnww',
            'O' => 'wnnnwnnwn',
            'P' => 'nnwnwnnwn',
            'Q' => 'nnnnnnwww',
            'R' => 'wnnnnnwwn',
            'S' => 'nnwnnnwwn',
            'T' => 'nnnnwnwwn',
            'U' => 'wwnnnnnnw',
            'V' => 'nwwnnnnnw',
            'W' => 'wwwnnnnnn',
            'X' => 'nwnnwnnnw',
            'Y' => 'wwnnwnnnn',
            'Z' => 'nwwnwnnnn',
            '-' => 'nwnnnnwnw',
            '.' => 'wwnnnnwnn',
            ' ' => 'nwwnnnwnn',
            '$' => 'nwnwnwnnn',
            '/' => 'nwnwnnnwn',
            '+' => 'nwnnnwnwn',
            '%' => 'nnnwnwnwn',
            '*' => 'nwnnwnwnn',
        ];

        $encoded = '*' . Str::upper($value) . '*';
        $narrow = 2;
        $wide = 5;
        $height = 56;
        $gap = 2;
        $quietZone = 12;
        $x = $quietZone;
        $rectangles = [];

        foreach (str_split($encoded) as $char) {
            $pattern = $patterns[$char] ?? $patterns['-'];

            foreach (str_split($pattern) as $index => $unit) {
                $width = $unit === 'w' ? $wide : $narrow;

                if ($index % 2 === 0) {
                    $rectangles[] = '<rect x="' . $x . '" y="0" width="' . $width . '" height="' . $height . '" fill="#111827" />';
                }

                $x += $width;
            }

            $x += $gap;
        }

        $width = $x + $quietZone;
        $textY = $height + 18;

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' ' . ($height + 24) . '" role="img" aria-label="Barcode for ' . e($value) . '">' .
            implode('', $rectangles) .
            '<text x="' . ($width / 2) . '" y="' . $textY . '" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" letter-spacing="1.2" fill="#0f172a">' . e($value) . '</text>' .
            '</svg>';
    }

    private function safeCount(callable $callback): int
    {
        try {
            return (int) $callback();
        } catch (\Throwable $exception) {
            return 0;
        }
    }

    private function safeValue(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function safeArray(callable $callback, array $fallback): array
    {
        try {
            return $callback();
        } catch (\Throwable $exception) {
            return $fallback;
        }
    }
}
