<?php

namespace App\Service\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfService
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function generateFromTemplate(string $template, array $context = [], array $options = []): string
    {
        $html = $this->twig->render($template, $context);

        return $this->generateFromHtml($html, $options);
    }

    public function generateFromHtml(string $html, array $options = []): string
    {
        $dompdfOptions = new Options();
        $dompdfOptions->set('defaultFont', $options['font'] ?? 'DejaVu Sans');
        $dompdfOptions->set('isRemoteEnabled', $options['remote'] ?? false);
        $dompdfOptions->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($dompdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($options['paper'] ?? 'A4', $options['orientation'] ?? 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function streamResponse(string $html, string $filename = 'document.pdf', bool $inline = true, array $options = []): never
    {
        $pdf = $this->generateFromHtml($html, $options);

        $disposition = $inline ? 'inline' : 'attachment';

        header('Content-Type: application/pdf');
        header(sprintf('Content-Disposition: %s; filename="%s"', $disposition, $filename));
        header('Content-Length: ' . strlen($pdf));

        echo $pdf;
        exit;
    }
}
