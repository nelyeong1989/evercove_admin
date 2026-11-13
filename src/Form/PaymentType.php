<?php

namespace App\Form;

use App\Entity\Payment;
use App\Entity\Reservation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reservation', EntityType::class, [
                'class' => Reservation::class,
                'label' => 'Reservation Booking',
                'choice_label' => static function (Reservation $reservation): string {
                    $guest = $reservation->getUser()?->getUsername() ?? 'Guest';
                    $room = $reservation->getRoom()?->getName() ?? 'Room';
                    $total = number_format((float) $reservation->getTotalPrice(), 2);

                    return sprintf('#%d — %s (%s) — ₱%s', $reservation->getId(), $guest, $room, $total);
                },
                'choice_attr' => static fn (Reservation $reservation): array => [
                    'data-amount' => $reservation->getTotalPrice() ?? '0.00',
                    'data-status' => $reservation->getStatus() ?? 'Pending',
                ],
                'attr' => ['class' => 'form-select mb-3'],
            ])
            ->add('amount', MoneyType::class, [
                'currency' => 'PHP',
                'label' => 'Payment Amount',
                'attr' => ['class' => 'form-control mb-3', 'placeholder' => '0.00'],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'choices' => [
                    'Cash' => 'Cash',
                    'GCash' => 'GCash',
                    'Credit / Debit Card' => 'Card',
                    'Bank Transfer' => 'Bank Transfer',
                ],
                'label' => 'Payment Method',
                'attr' => ['class' => 'form-select mb-3'],
            ])
            ->add('paymentStatus', ChoiceType::class, [
                'choices' => [
                    'Paid' => 'Paid',
                    'Pending' => 'Pending',
                    'Refunded' => 'Refunded',
                    'Failed' => 'Failed',
                ],
                'label' => 'Payment Status',
                'attr' => ['class' => 'form-select mb-3'],
            ])
            ->add('paidAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Payment Date & Time',
                'attr' => ['class' => 'form-control mb-3'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}