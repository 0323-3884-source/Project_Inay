<?php

namespace Database\Seeders;

use App\Http\Controllers\AuthController;
use App\Models\Mother;
use App\Models\MaternalMonitoringRecord;
use App\Models\InayKaalamanProgress;
use App\Models\InayKaalamanCheckup;
use App\Models\InayKaalamanUpload;
use App\Support\MaternalVitalScreening;
use Dompdf\Dompdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SyntheticGuideHistorySeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Synthetic history is only available locally or in tests.');
        }
        $emails = array_map(fn ($i) => sprintf('guide.mother.%03d@example.test', $i), range(1, 27));
        $mothers = Mother::whereIn('email', $emails)->orderBy('email')->get();
        if ($mothers->count() !== 27) {
            throw new \RuntimeException('Run SyntheticGuideSeeder first to create all 27 guide profiles.');
        }
        // Use the actual learning catalog so completion matches the portal.
        $catalog = (new \ReflectionMethod(AuthController::class, 'inayKaalamanVideoMonths'))->invoke(app(AuthController::class));
        $disk = Storage::disk('public');
        DB::transaction(function () use ($mothers, $catalog, $disk): void {
            foreach ($mothers as $index => $mother) {
                foreach (range(1, 3) as $month) {
                    $at = Carbon::create(2026, 7 + $month - 1, 8 + $index % 15, 9, 0);
                    $week = $mother->pregnancy_status === 'pregnant' ? 4 + $month * 4 + $index % 4 : null;
                    $values = [
                        'recorded_at' => $at, 'pregnancy_week' => $week,
                        'bp_systolic' => 108 + $index % 14 + $month,
                        'bp_diastolic' => 66 + $index % 10 + $month,
                        'blood_sugar_test_type' => 'fasting_plasma_glucose',
                        'blood_sugar' => 78 + $index % 15 + $month,
                        'weight' => 50 + $index % 17 + $month * ($week ? 0.8 : 0.2),
                        'height_cm' => 150 + $index % 16,
                        'hemoglobin' => 11.8 + ($index % 5) * 0.2,
                        'temperature' => 36.5 + ($index % 3) * 0.1,
                        'heart_rate' => 74 + $index % 12 + $month,
                    ];
                    $screen = MaternalVitalScreening::screen($values, $mother);
                    $attributes = $values + [
                        'pregnancy_month' => $week ? (int) ceil($week / 4) : null,
                        'screening_summary_status' => $screen['summary_status'],
                        'risk_level' => $screen['summary_status'],
                        'measurement_units' => $screen['units'],
                        'screening_explanations' => $screen['explanations'],
                        'screening_guidelines' => $screen['guidelines'],
                        'weight_change_from_previous' => $screen['weight_change_from_previous'],
                        'notes' => 'Fictional guide visit '.$month.'. Sample measurements for demonstrating trends and history.',
                    ];
                    foreach (['bp'=>'blood_pressure', 'blood_sugar'=>'blood_sugar', 'hemoglobin'=>'hemoglobin', 'weight'=>'weight', 'temperature'=>'temperature', 'heart_rate'=>'heart_rate'] as $field => $key) {
                        $attributes[$field.'_status'] = $screen['statuses'][$key] ?? null;
                    }
                    MaternalMonitoringRecord::firstOrCreate(['mother_id'=>$mother->id, 'recorded_at'=>$at], $attributes);
                    InayKaalamanCheckup::firstOrCreate(['mother_id'=>$mother->id, 'month'=>$month], [
                        'checkup_date'=>$at->toDateString(), 'recorded_at'=>$at,
                        'healthcare_worker_name'=>'Sample Midwife',
                        'facility_name'=>'Sample '.$mother->barangay.' Health Center',
                        'notes'=>'Synthetic guide checkup; not a verified clinical visit.',
                    ]);
                    $complete = $month <= 1 + $index % 3;
                    $items = [
                        ['reading', "month-{$month}-reading", $catalog[$month]['title'], 'read'],
                        ['infographic', "month-{$month}-infographic", $catalog[$month]['title'].' Infographic', 'reviewed'],
                    ];
                    foreach ($catalog[$month]['videos'] as $video) {
                        $items[] = ['video', $video['key'], $video['title'], 'watched'];
                    }
                    foreach ($items as [$type, $key, $title, $status]) {
                        InayKaalamanProgress::firstOrCreate(['mother_id'=>$mother->id, 'month'=>$month, 'activity_type'=>$type, 'item_key'=>$key], [
                            'item_title'=>$title, 'status'=>$complete ? $status : 'in_progress',
                            'started_at'=>$at->copy()->subDays(3), 'completed_at'=>$complete ? $at : null,
                        ]);
                    }
                    $name = "SAMPLE-Month-{$month}-Prenatal-Record.pdf";
                    $path = "inay-kaalaman-records/synthetic-guide/{$mother->id}/{$name}";
                    if (! $disk->exists($path)) {
                        $pdf = new Dompdf(['isRemoteEnabled'=>false]);
                        $person = htmlspecialchars($mother->full_name, ENT_QUOTES, 'UTF-8');
                        $pdf->loadHtml('<html><head><style>body{font-family:DejaVu Sans;font-size:11pt;color:#14233d}h1{font-size:20pt;color:#c90b67}td{padding:9pt;border:1px solid #ccc}table{width:100%;border-collapse:collapse}.banner{padding:12pt;background:#fff0f7;font-weight:bold}p{line-height:1.5}</style></head><body>'
                            .'<div class="banner">FICTIONAL SAMPLE - FOR SYSTEM DEMONSTRATION</div><h1>Project INAY</h1><h2>Sample care visit - Learning month '.$month.'</h2><table>'
                            .'<tr><td>Mother</td><td>'.$person.'</td></tr><tr><td>Visit date</td><td>'.$at->format('d M Y').'</td></tr>'
                            .'<tr><td>Blood pressure</td><td>'.$values['bp_systolic'].' / '.$values['bp_diastolic'].' mmHg</td></tr>'
                            .'<tr><td>Weight</td><td>'.$values['weight'].' kg</td></tr><tr><td>Temperature</td><td>'.$values['temperature'].' C</td></tr>'
                            .'</table><h2>Learning and document history</h2><p>Sample reading, video, and infographic activity accompanies this month. This document demonstrates the document library and preview.</p>'
                            .'<p>All identities, visits, and measurements are fictional. This is not a clinical certificate or proof of attendance.</p></body></html>');
                        $pdf->setPaper('A4');
                        $pdf->render();
                        if (! $disk->put($path, $pdf->output())) throw new \RuntimeException('Could not save sample document.');
                    }
                    InayKaalamanUpload::firstOrCreate(['mother_id'=>$mother->id, 'path'=>$path], [
                        'month'=>$month, 'record_type'=>'Prenatal Records and Receipts',
                        'original_name'=>$name, 'mime_type'=>'application/pdf', 'size'=>$disk->size($path),
                        'created_at'=>$at, 'updated_at'=>$at,
                    ]);
                }
            }
        });
        $this->command?->info('27 guide profiles now have three visits, learning history, checkups, and sample documents each.');
    }
}
