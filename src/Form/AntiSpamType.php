<?php

declare(strict_types=1);

namespace App\Form;

use App\Spam\SpamChecker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Champs anti-spam à ajouter à un formulaire public : $builder->add('antispam', AntiSpamType::class).
 * Vérification : SpamChecker::isSpam($form).
 */
final class AntiSpamType extends AbstractType
{
    public function __construct(private readonly SpamChecker $spamChecker)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Pot de miel : masqué visuellement ET pour les lecteurs d'écran, ignoré au clavier.
            ->add('website', TextType::class, [
                'label' => 'form.honeypot',
                'required' => false,
                'attr' => ['autocomplete' => 'off', 'tabindex' => '-1'],
            ])
            ->add('token', HiddenType::class, [
                'data' => $this->spamChecker->createToken(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'label' => false,
            'required' => false,
            'error_bubbling' => false,
            'row_attr' => ['class' => 'hp-field', 'aria-hidden' => 'true'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'antispam';
    }
}
