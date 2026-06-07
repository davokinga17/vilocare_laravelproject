<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\ViralLoad;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index()
    {
        $totalPatients = $this->safeCount(fn () => Patient::query()->count());
        $totalViralLoads = $this->safeCount(fn () => ViralLoad::query()->count());
        $suppressed = $this->safeCount(fn () => ViralLoad::query()->where('result_cpml', '<', 1000)->count());
        $unsuppressed = $this->safeCount(fn () => ViralLoad::query()->where('result_cpml', '>=', 1000)->count());

        $latestResultDate = $this->safeValue(function () {
            if (! Schema::hasColumn('viral_load_results', 'result_date')) {
                return null;
            }

            return DB::table('viral_load_results')->max('result_date');
        });

        $analytics = [
            'patientSexMix' => $this->patientSexMix(),
            'patientAgeMix' => $this->patientAgeMix(),
            'viralLoadStatusMix' => $this->viralLoadStatusMix($suppressed, $unsuppressed),
            'monthlyViralLoads' => $this->monthlyViralLoads(),
            'testingIndications' => $this->testingIndications(),
            'facilityCoverage' => $this->facilityCoverage(),
        ];

        return view('reports.index', [
            'totalPatients' => $totalPatients,
            'totalViralLoads' => $totalViralLoads,
            'suppressed' => $suppressed,
            'unsuppressed' => $unsuppressed,
            'suppressionRate' => $this->percentage($suppressed, $suppressed + $unsuppressed),
            'latestResultDate' => $latestResultDate ? Carbon::parse($latestResultDate)->format('d M Y') : 'No data',
            'analytics' => $analytics,
        ]);
    }

    public function patientsPDF()
    {
        $patients = Patient::all();
        $pdf = Pdf::loadView('reports.patients_pdf', compact('patients'));

        return $pdf->download('patients_report.pdf');
    }

    public function patientsExcel()
    {
        return Excel::download(new \App\Exports\PatientsExport, 'patients.xlsx');
    }

    public function viralLoadPDF()
    {
        $results = ViralLoad::with('patient')->get();
        $pdf = Pdf::loadView('reports.viral_load_pdf', compact('results'));

        return $pdf->download('viral_load_report.pdf');
    }

    public function viralLoadExcel()
    {
        return Excel::download(new \App\Exports\ViralLoadExport, 'viral_load.xlsx');
    }

    private function patientSexMix(): array
    {
        if (! Schema::hasColumn('patients', 'sex')) {
            return $this->emptyDataset();
        }

        $rows = Patient::query()
            ->select('sex', DB::raw('COUNT(*) as total'))
            ->whereNotNull('sex')
            ->groupBy('sex')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('sex')->map(fn ($label) => (string) $label)->all(),
            'values' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
        ];
    }

    private function patientAgeMix(): array
    {
        if (Schema::hasColumn('patients', 'age')) {
            $rows = DB::table('patients as p')
                ->selectRaw('CAST(p.age as char) as age_label, COUNT(*) as total')
                ->whereNotNull('p.age')
                ->groupBy('age_label')
                ->orderByDesc('total')
                ->limit(6)
                ->get();

            return [
                'labels' => $rows->pluck('age_label')->map(fn ($label) => (string) $label)->all(),
                'values' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
            ];
        }

        if (! Schema::hasColumn('patients', 'age_category')) {
            return $this->emptyDataset();
        }

        $query = DB::table('patients as p')
            ->selectRaw('COALESCE(ac.description, CAST(p.age_category as char)) as age_label, COUNT(*) as total')
            ->leftJoin('age_categories as ac', 'ac.category_id', '=', 'p.age_category')
            ->whereNotNull('p.age_category')
            ->groupBy('age_label')
            ->orderByDesc('total')
            ->limit(6);

        $rows = $query->get();

        return [
            'labels' => $rows->pluck('age_label')->map(fn ($label) => (string) $label)->all(),
            'values' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
        ];
    }

    private function viralLoadStatusMix(int $suppressed, int $unsuppressed): array
    {
        return [
            'labels' => ['Suppressed', 'Unsuppressed'],
            'values' => [$suppressed, $unsuppressed],
        ];
    }

    private function monthlyViralLoads(): array
    {
        $labels = collect(range(1, 6))
            ->map(fn ($offset) => Carbon::now()->subMonths(6 - $offset)->format('M Y'))
            ->all();

        if (! Schema::hasColumn('viral_load_results', 'sample_date')) {
            return ['labels' => $labels, 'values' => array_fill(0, count($labels), 0)];
        }

        $startDate = Carbon::now()->startOfMonth()->subMonths(5)->toDateString();

        $rows = DB::table('viral_load_results')
            ->selectRaw('YEAR(sample_date) as yr, MONTH(sample_date) as mn, COUNT(*) as total')
            ->whereNotNull('sample_date')
            ->whereDate('sample_date', '>=', $startDate)
            ->groupBy('yr', 'mn')
            ->orderBy('yr')
            ->orderBy('mn')
            ->get()
            ->keyBy(fn ($row) => sprintf('%04d-%02d', $row->yr, $row->mn));

        $values = collect(range(0, 5))
            ->map(function ($offset) use ($rows) {
                $month = Carbon::now()->startOfMonth()->subMonths(5 - $offset);
                $key = $month->format('Y-m');

                return (int) ($rows[$key]->total ?? 0);
            })
            ->all();

        return ['labels' => $labels, 'values' => $values];
    }

    private function testingIndications(): array
    {
        if (! Schema::hasColumn('viral_load_results', 'vl_testing_indication')) {
            return $this->emptyDataset();
        }

        $rows = DB::table('viral_load_results')
            ->selectRaw('vl_testing_indication as label, COUNT(*) as total')
            ->whereNotNull('vl_testing_indication')
            ->where('vl_testing_indication', '!=', '')
            ->groupBy('vl_testing_indication')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'labels' => $rows->pluck('label')->map(fn ($label) => (string) $label)->all(),
            'values' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
        ];
    }

    private function facilityCoverage(): array
    {
        if (! Schema::hasColumn('patients', 'facility_id')) {
            return $this->emptyDataset();
        }

        $rows = DB::table('patients as p')
            ->selectRaw('COALESCE(f.facility_name, CAST(p.facility_id as char)) as facility_label, COUNT(*) as total')
            ->leftJoin('facilities as f', 'f.facility_id', '=', 'p.facility_id')
            ->whereNotNull('p.facility_id')
            ->groupBy('facility_label')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'labels' => $rows->pluck('facility_label')->map(fn ($label) => (string) $label)->all(),
            'values' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
        ];
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
}
