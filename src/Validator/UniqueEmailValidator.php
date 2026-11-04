<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEmail) {
            throw new UnexpectedTypeException($constraint, UniqueEmail::class);
        }

        if (!\is_string($value) || '' === $value) {
            return;
        }

        $existing = $this->users->findOneByEmail($value);
        $current = $this->context->getObject();

        if (null === $existing || ($current instanceof User && null !== $current->getId() && $existing->getId() === $current->getId())) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
