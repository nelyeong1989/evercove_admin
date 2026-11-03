<?php

namespace App\Form;

use App\Entity\Reservation;
use App\Entity\Room;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Guest Account',
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('room', EntityType::class, [
                'class' => Room::class,
                'choice_label' => 'name',
                'label' => 'Selected Room',
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('checkIn', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Check-In Date',
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('checkOut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Check-Out Date',
                'attr' => ['class' => 'form-control mb-3']
            ])
            ->add('totalPrice', MoneyType::class, [
                'currency' => 'PHP',
                'label' => 'Total Calculated Price (₱)',
                'attr' => ['class' => 'form-control mb-3', 'placeholder' => '5000.00']
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'Pending',
                    'Confirmed' => 'Confirmed',
                    'Cancelled' => 'Cancelled',
                    'Completed' => 'Completed',
                ],
                'label' => 'Reservation Status',
                'attr' => ['class' => 'form-select mb-3']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}