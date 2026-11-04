<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Saisie d'un nouveau mot de passe (double saisie + politique de robustesse).
 * Utilisé à l'inscription, à la réinitialisation et au changement de mot de passe.
 */
final class NewPasswordType extends AbstractType
{
    public const int MIN_LENGTH = 12;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'type' => PasswordType::class,
            'label' => false,
            'invalid_message' => 'password.mismatch',
            'first_options' => [
                'label' => 'form.password',
                'help' => 'form.password_help',
                'help_translation_parameters' => ['%min%' => self::MIN_LENGTH],
                'attr' => ['autocomplete' => 'new-password', 'minlength' => self::MIN_LENGTH],
            ],
            'second_options' => [
                'label' => 'form.password_confirm',
                'attr' => ['autocomplete' => 'new-password'],
            ],
            'constraints' => [
                new Assert\NotBlank(message: 'password.required'),
                new Assert\Length(min: self::MIN_LENGTH, max: 4096, minMessage: 'password.too_short'),
                new Assert\PasswordStrength(minScore: Assert\PasswordStrength::STRENGTH_MEDIUM, message: 'password.too_weak'),
                new Assert\NotCompromisedPassword(message: 'password.compromised', skipOnError: true),
            ],
        ]);
    }

    public function getParent(): string
    {
        return RepeatedType::class;
    }
}
