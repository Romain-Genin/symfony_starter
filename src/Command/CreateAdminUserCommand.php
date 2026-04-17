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
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un compte administrateur',
)]
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
        $validator = Validation::createValidator();

        $io->title('Création d\'un administrateur');

        $email = $io->ask('Email', null, function (?string $value): string {
            if (!$value || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Email invalide.');
            }
            return $value;
        });

        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $io->error("Un utilisateur avec l'email « {$email} » existe déjà.");
            return Command::FAILURE;
        }

        $name = $io->ask('Nom complet', null, function (?string $value): string {
            if (empty(trim($value ?? ''))) {
                throw new \RuntimeException('Le nom ne peut pas être vide.');
            }
            return trim($value);
        });

        $password = $io->askHidden('Mot de passe (masqué)', function (?string $value): string {
            if (strlen($value ?? '') < 8) {
                throw new \RuntimeException('Le mot de passe doit contenir au moins 8 caractères.');
            }
            return $value;
        });

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success("Administrateur créé avec succès !");
        $io->table(['Champ', 'Valeur'], [
            ['Email', $user->getEmail()],
            ['Nom',   $user->getName()],
            ['Rôle',  'ROLE_ADMIN'],
        ]);

        return Command::SUCCESS;
    }
}
