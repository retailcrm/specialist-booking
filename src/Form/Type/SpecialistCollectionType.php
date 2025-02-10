<?php

namespace App\Form\Type;

use App\Entity\Account;
use App\Form\Model\SpecialistCollectionModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpecialistCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('specialists', CollectionType::class, [
            'entry_type' => SpecialistType::class,
            'entry_options' => [
                'account' => $options['account'],
                'stores' => $options['stores'],
            ],
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'prototype' => true,
            'label' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => SpecialistCollectionModel::class,
            ])
            ->setRequired(['account', 'stores'])
            ->setAllowedTypes('account', [Account::class])
            ->setAllowedTypes('stores', ['array', 'null'])
        ;
    }
}
