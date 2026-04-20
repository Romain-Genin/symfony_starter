<?php

namespace App\Service\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfService
{
    public function __construct(private readonly Environment $twig) {}

    public function generateFromTemplate(string $template, array $context = [], array $options = []): string
    {
        return $this->generateFromHtml($this->twig->render($template, $context), $options);
    }

    public function generateFromHtml(string $html, array $options = []): string
    {
        $opts = new Options();
        $opts->set('defaultFont', $options['font'] ?? 'DejaVu Sans');
        $opts->set('isRemoteEnabled', $options['remote'] ?? false);
        $opts->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($opts);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($options['paper'] ?? 'A4', $options['orientation'] ?? 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
