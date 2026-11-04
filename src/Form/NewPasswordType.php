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
            'invalid_message' => 'Les deux mots de passe ne correspondent pas.',
            'first_options' => [
                'label' => 'Mot de passe',
                'help' => \sprintf('Au moins %d caractères. Une phrase de passe est idéale.', self::MIN_LENGTH),
                'attr' => ['autocomplete' => 'new-password', 'minlength' => self::MIN_LENGTH],
            ],
            'second_options' => [
                'label' => 'Confirmer le mot de passe',
                'attr' => ['autocomplete' => 'new-password'],
            ],
            'constraints' => [
                new Assert\NotBlank(message: 'Merci de saisir un mot de passe.'),
                new Assert\Length(min: self::MIN_LENGTH, max: 4096, minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères.'),
                new Assert\PasswordStrength(minScore: Assert\PasswordStrength::STRENGTH_MEDIUM, message: 'Ce mot de passe est trop faible. Allongez-le ou mélangez davantage les caractères.'),
                new Assert\NotCompromisedPassword(message: 'Ce mot de passe figure dans une fuite de données connue. Choisissez-en un autre.', skipOnError: true),
            ],
        ]);
    }

    public function getParent(): string
    {
        return RepeatedType::class;
    }
}
