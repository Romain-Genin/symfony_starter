<?php

declare(strict_types=1);

namespace App\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Génère un PDF (Dompdf) à partir d'un template Twig.
 * Les templates PDF étendent templates/pdf/base.html.twig (CSS simple, pas de Tailwind).
 */
final readonly class PdfGenerator
{
    public function __construct(
        private Environment $twig,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = [], string $paper = 'A4', string $orientation = 'portrait'): string
    {
        $options = new Options()
            ->setDefaultFont('DejaVu Sans')
            ->setIsRemoteEnabled(false)
            ->setChroot([$this->projectDir.'/public', $this->projectDir.'/assets']);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->twig->render($template, $context));
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();

        return (string) $dompdf->output();
    }

    /**
     * @param array<string, mixed> $context
     */
    public function response(string $template, array $context, string $filename, bool $download = false): Response
    {
        return new Response($this->render($template, $context), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                $download ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
                $filename,
            ),
        ]);
    }
}
