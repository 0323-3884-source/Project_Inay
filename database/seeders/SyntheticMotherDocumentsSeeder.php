<?php

namespace Database\Seeders;

use App\Models\InayKaalamanUpload;
use App\Models\Mother;
use Dompdf\Dompdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class SyntheticMotherDocumentsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('Sample documents can only be generated locally.');
        }

        $emails = array_map(fn ($i) => sprintf('synthetic.mother.%03d@example.test', $i), range(1, 20));
        $mothers = Mother::whereIn('email', $emails)->orderBy('id')->get();
        if ($mothers->count() !== 20 || $mothers->contains(fn ($m) => ! preg_match('/^Synthetic\d{3}$/', $m->last_name))) {
            throw new \RuntimeException('Expected the 20 previously generated synthetic mothers.');
        }

        $disk = Storage::disk('public');
        $created = 0;
        foreach ($mothers as $mother) {
            foreach (range(1, 9) as $month) {
                $name = "SAMPLE-Month-{$month}-Learning-Document.pdf";
                $path = "inay-kaalaman-records/synthetic-testing/{$mother->id}/{$name}";
                if (! $disk->exists($path)) {
                    $pdf = new Dompdf(['isRemoteEnabled' => false]);
                    $person = htmlspecialchars($mother->full_name, ENT_QUOTES, 'UTF-8');
                    $pdf->loadHtml('<html><head><style>body{font-family:DejaVu Sans,sans-serif;color:#14233d;margin:30px}h1{color:#d51270;font-size:25px}.banner{padding:18px;background:#fff0f7;border:2px solid #d51270;font-weight:bold}td{padding:12px;border-bottom:1px solid #ddd}table{width:100%;border-collapse:collapse}p{line-height:1.7}</style></head><body>'
                        .'<div class="banner">SYNTHETIC SAMPLE — FOR SOFTWARE TESTING ONLY</div><h1>Project INAY · INAY Kaalaman</h1>'
                        .'<h2>Month '.$month.' — Sample supporting document</h2><table><tr><td>Fictional mother</td><td>'.$person.'</td></tr>'
                        .'<tr><td>Test account ID</td><td>INAY-'.$mother->id.'</td></tr><tr><td>Month</td><td>'.$month.'</td></tr>'
                        .'<tr><td>Document reference</td><td>TEST-'.$mother->id.'-M'.$month.'</td></tr></table>'
                        .'<h2>Preview checklist</h2><p>This sample tests document lists, month selection, PDF previews, and beneficiary document access.</p>'
                        .'<p>Confirm that the mother name and month above match the selected profile. The text should be readable when zoomed or viewed on a mobile screen.</p>'
                        .'<p>No medical findings, treatment, signatures, or verified attendance are represented. This is not a clinical record, official certificate, or proof of 4Ps compliance.</p>'
                        .'</body></html>', 'UTF-8');
                    $pdf->setPaper('A4');
                    $pdf->render();
                    if (! $disk->put($path, $pdf->output())) {
                        throw new \RuntimeException('Could not save sample PDF.');
                    }
                }
                $created += (int) InayKaalamanUpload::firstOrCreate([
                    'mother_id' => $mother->id, 'path' => $path,
                ], [
                    'month' => $month, 'record_type' => 'Other Documents', 'original_name' => $name,
                    'mime_type' => 'application/pdf', 'size' => $disk->size($path),
                ])->wasRecentlyCreated;
            }
        }

        $documents = InayKaalamanUpload::whereIn('mother_id', $mothers->modelKeys())
            ->where('path', 'like', 'inay-kaalaman-records/synthetic-testing/%')->get();
        foreach ($documents as $document) {
            if (! $disk->exists($document->path) || $disk->mimeType($document->path) !== 'application/pdf') {
                throw new \RuntimeException('Sample document verification failed.');
            }
        }
        $this->command?->info("Created {$created} sample PDFs. Verified {$documents->count()} documents across {$mothers->count()} synthetic mothers.");
    }
}
