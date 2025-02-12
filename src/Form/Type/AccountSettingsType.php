<?php

namespace App\Form\Type;

use App\Form\Model\AccountSettingsModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountSettingsType extends AbstractType
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
            ->add('chooseStore', CheckboxType::class, [
                'label' => 'choose_store_label',
                'required' => false,
                'help' => 'choose_store_help',
                'help_html' => true,
                'help_translation_parameters' => ['%public_url%' => $options['public_url']],
                'translation_domain' => null,
            ])
            ->add('chooseCity', CheckboxType::class, [
                'label' => 'choose_city_label',
                'required' => false,
                'help' => 'choose_city_help',
                'help_html' => true,
                'help_translation_parameters' => ['%public_url%' => $options['public_url']],
                'translation_domain' => null,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => AccountSettingsModel::class,
            ])
            ->setRequired('public_url')
            ->setAllowedTypes('public_url', 'string')
        ;
    }
}
