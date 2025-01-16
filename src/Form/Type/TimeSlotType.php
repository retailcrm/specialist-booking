<?php

namespace App\Form\Type;

use App\Form\Model\TimeSlotModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimeSlotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('length', NumberType::class, [
                'label' => 'time_slot_length_label',
                'scale' => 0,
                'html5' => true,
                'attr' => [
                    'step' => 15,
                    'min' => 15,
                    'max' => 360,
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TimeSlotModel::class,
        ]);
    }
}
