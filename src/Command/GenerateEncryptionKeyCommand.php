<?php

declare(strict_types=1);

namespace App\Command;

use App\Security\Encryption\Encryptor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:encryption:generate-key', description: 'Génère une clé pour APP_ENCRYPTION_KEY.')]
final class GenerateEncryptionKeyCommand
{
    public function __invoke(SymfonyStyle $io): int
    {
        $io->writeln('APP_ENCRYPTION_KEY='.Encryptor::generateKey());
        $io->warning([
            'Conservez cette clé dans un gestionnaire de secrets (et dans les variables Coolify).',
            'Sans elle, les données chiffrées en base sont DÉFINITIVEMENT illisibles.',
            'Ne changez jamais la clé d\'un environnement contenant déjà des données.',
        ]);

        return Command::SUCCESS;
    }
}
