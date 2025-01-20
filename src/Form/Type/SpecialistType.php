<?php

namespace App\Form\Type;

use App\Entity\Account;
use App\Entity\Specialty;
use App\Form\Model\SpecialistModel;
use App\Repository\SpecialtyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class SpecialistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('name', TextType::class, [
                'label' => 'person_name',
                'required' => true,
            ])
            ->add('specialty', EntityType::class, [
                'class' => Specialty::class,
                'choice_label' => 'name',
                'query_builder' => fn (SpecialtyRepository $repo) => $repo->findByAccountOrderingByNameQueryBuilder($options['account']),
                'label' => 'specialty',
                'required' => false,
            ])
            ->add('ordering', IntegerType::class, [
                'label' => 'ordering',
                'required' => true,
                'attr' => ['min' => 0, 'max' => 999],
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'photo',
                'required' => false,
                'constraints' => [
                    new Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => SpecialistModel::class,
            ])
            ->setRequired('account')
            ->setAllowedTypes('account', [Account::class])
        ;
    }
}
