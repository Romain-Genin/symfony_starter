<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'app:user:create', description: 'Crée un utilisateur (admin avec --admin). Email déjà vérifié.')]
final readonly class CreateUserCommand
{
    public function __construct(
        private UserRepository $users,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Adresse email')] ?string $email = null,
        #[Option('Donne le rôle ROLE_ADMIN')] bool $admin = false,
    ): int {
        $email ??= $io->ask('Adresse email');
        if (null !== $this->users->findOneByEmail((string) $email)) {
            $io->error('Un utilisateur existe déjà avec cet email.');

            return Command::FAILURE;
        }

        $password = $io->askHidden('Mot de passe (12 caractères minimum)', static function (?string $value): string {
            if (null === $value || mb_strlen($value) < 12) {
                throw new \RuntimeException('Le mot de passe doit contenir au moins 12 caractères.');
            }

            return $value;
        });

        $user = new User()
            ->setEmail($email)
            ->setIsVerified(true)
            ->setRoles($admin ? [User::ROLE_ADMIN] : []);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $violations = $this->validator->validate($user);
        if (\count($violations) > 0) {
            foreach ($violations as $violation) {
                $io->error(\sprintf('%s : %s', $violation->getPropertyPath(), $violation->getMessage()));
            }

            return Command::FAILURE;
        }

        $this->em->persist($user);
        $this->em->flush();

        $io->success(\sprintf('%s %s créé.', $admin ? 'Administrateur' : 'Utilisateur', $user->getEmail()));

        return Command::SUCCESS;
    }
}
