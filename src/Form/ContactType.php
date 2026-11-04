<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\ContactMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

final class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom et prénom',
                'attr' => ['autocomplete' => 'name', 'maxlength' => 120],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => ['autocomplete' => 'email', 'maxlength' => 180],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['autocomplete' => 'tel', 'maxlength' => 30],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Objet',
                'attr' => ['maxlength' => 150],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => ['rows' => 6, 'maxlength' => 5000],
            ])
            ->add('consent', CheckboxType::class, [
                'mapped' => false,
                'label' => 'J\'accepte que mes données soient utilisées pour traiter ma demande, conformément à la politique de confidentialité.',
                'constraints' => [new IsTrue(message: 'Votre consentement est nécessaire pour traiter votre demande.')],
            ])
            ->add('antispam', AntiSpamType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactMessage::class,
        ]);
    }
}
