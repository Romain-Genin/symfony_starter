<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ContactMessageRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Durée de conservation RGPD (app.contact_retention_months). À planifier (cron Coolify) :
 *   php bin/console app:contact:purge
 */
#[AsCommand(name: 'app:contact:purge', description: 'Supprime les messages de contact dépassant la durée de conservation.')]
final readonly class PurgeContactMessagesCommand
{
    public function __construct(
        private ContactMessageRepository $messages,
        #[Autowire('%app.contact_retention_months%')]
        private int $retentionMonths,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $limit = new \DateTimeImmutable(\sprintf('-%d months', $this->retentionMonths));
        $deleted = $this->messages->deleteOlderThan($limit);

        $io->success(\sprintf('%d message(s) antérieur(s) au %s supprimé(s).', $deleted, $limit->format('d/m/Y')));

        return Command::SUCCESS;
    }
}
