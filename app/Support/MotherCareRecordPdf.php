<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;

class MotherCareRecordPdf
{
    public function render(string $html): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('chroot', public_path('assets/images/mother-record'));
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('defaultMediaType', 'print');
        $options->set('isFontSubsettingEnabled', true);

        $pdf = new Dompdf($options);
        $pdf->setPaper('A4', 'portrait');
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->render();
        $canvas = $pdf->getCanvas();
        $canvas->page_text(475, 815, 'Page {PAGE_NUM} of {PAGE_COUNT}', $pdf->getFontMetrics()->getFont('DejaVu Sans'), 7);

        return $pdf->output();
    }
}
