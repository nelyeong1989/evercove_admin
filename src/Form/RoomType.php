<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Room;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Room Name / Number',
                'attr' => ['class' => 'form-control mb-3', 'placeholder' => 'e.g., Suite 101']
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Category Classification',
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('pricePerNight', MoneyType::class, [
                'currency' => 'PHP',
                'label' => 'Price per Night (₱)',
                'attr' => ['class' => 'form-control mb-3', 'placeholder' => '2500.00']
            ])
            ->add('capacity', NumberType::class, [
                'label' => 'Guest Capacity',
                'attr' => ['class' => 'form-control mb-3', 'placeholder' => '2']
            ])
            ->add('imagePath', TextType::class, [
                'required' => false,
                'label' => 'Image Relative Path',
                'attr' => ['class' => 'form-control mb-3', 'placeholder' => 'images/room-1.jpg']
            ])
            ->add('isAvailable', CheckboxType::class, [
                'required' => false,
                'label' => 'Available for Guest Bookings',
                'attr' => ['class' => 'form-check-input ms-2 mb-3']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Room::class,
        ]);
    }
}