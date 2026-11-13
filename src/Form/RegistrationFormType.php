<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'attr' => [
                    'class' => 'form-control mb-3',
                    'placeholder' => 'e.g., Maria Santos',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Please enter the staff member\'s full name'
                    ),
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => [
                    'class' => 'form-control mb-3',
                    'placeholder' => 'e.g., frontdesk_maria',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => [
                    'class' => 'form-control mb-3',
                    'placeholder' => 'staff@evercove.com',
                ],
            ])
            ->add('role', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Account Role',
                'choices' => [
                    'Front Desk Staff (ROLE_STAFF)' => 'ROLE_STAFF',
                    'Administrator (ROLE_ADMIN)' => 'ROLE_ADMIN',
                ],
                'data' => 'ROLE_STAFF',
                'attr' => [
                    'class' => 'form-select mb-3',
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Initial Password',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control mb-3',
                    'placeholder' => '••••••••',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Please enter a password'
                    ),
                    new Length(
                        min: 6,
                        minMessage: 'Your password should be at least {{ limit }} characters',
                        max: 4096
                    ),
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'label' => 'Account Active (Allow Login)',
                'data' => true,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}