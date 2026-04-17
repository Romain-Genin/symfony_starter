<?php

namespace App\Command;

use Minishlink\WebPush\VAPID;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-vapid-keys',
    description: 'Génère une paire de clés VAPID pour les push notifications',
)]
class GenerateVapidKeysCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Génération des clés VAPID');

        $keys = VAPID::createVapidKeys();

        $io->success('Clés générées avec succès !');
        $io->text('Ajoute ces valeurs dans ton fichier <comment>.env.local</comment> :');
        $io->newLine();
        $io->writeln('<info>VAPID_PUBLIC_KEY=' . $keys['publicKey'] . '</info>');
        $io->writeln('<info>VAPID_PRIVATE_KEY=' . $keys['privateKey'] . '</info>');
        $io->newLine();
        $io->warning('Ne jamais committer VAPID_PRIVATE_KEY dans le dépôt Git.');

        return Command::SUCCESS;
    }
}
