<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$options['method_patch']) {
            $passwordConstraints = [
                new NotBlank([], 'user.password.not_blank'),
            ];
        }

        $builder
            ->add('email')
            ->add('plainPassword', PasswordType::class, [
                'constraints' => $passwordConstraints ?? []
            ])
        ;
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
            'method_patch' => false,
        ]);
    }
}
