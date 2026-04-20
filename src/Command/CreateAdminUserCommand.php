<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Créer un compte administrateur')]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Création d'un administrateur");

        $email = $io->ask('Email', null, function (?string $v): string {
            if (!$v || !filter_var($v, FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Email invalide.');
            return $v;
        });

        if ($this->em->getRepository(User::class)->findOneBy(['email' => $email])) {
            $io->error("L'email « {$email} » est déjà utilisé.");
            return Command::FAILURE;
        }

        $name = $io->ask('Nom complet', null, function (?string $v): string {
            if (empty(trim($v ?? ''))) throw new \RuntimeException('Le nom ne peut pas être vide.');
            return trim($v);
        });

        $password = $io->askHidden('Mot de passe (masqué, min. 8 caractères)', function (?string $v): string {
            if (strlen($v ?? '') < 8) throw new \RuntimeException('8 caractères minimum.');
            return $v;
        });

        $user = (new User())->setEmail($email)->setName($name)->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success("Administrateur créé : {$email}");
        return Command::SUCCESS;
    }
}
