<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire « une adresse email » : mot de passe oublié, renvoi du lien de confirmation.
 */
final class EmailRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'form.email',
            'attr' => ['autocomplete' => 'email'],
            'constraints' => [
                new Assert\NotBlank(message: 'email.required'),
                new Assert\Email(message: 'email.invalid'),
            ],
        ]);
    }
}
