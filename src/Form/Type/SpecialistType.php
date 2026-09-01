<?php

namespace App\Form\Type;

use App\Entity\Account;
use App\Entity\Specialty;
use App\Form\Model\SpecialistModel;
use App\Repository\SpecialtyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class SpecialistType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $account = $options['account'];
        assert($account instanceof Account);

        $builder
            ->add('id', HiddenType::class)
            ->add('name', TextType::class, [
                'label' => 'person_name',
                'required' => true,
            ])
            ->add('specialty', EntityType::class, [
                'class' => Specialty::class,
                'choice_label' => 'name',
                'query_builder' => fn (SpecialtyRepository $repo) => $repo->findByAccountOrderingByNameQueryBuilder($account),
                'label' => 'specialty',
                'required' => false,
            ])
        ;

        if ($account->getSettings()->chooseStore()) {
            $builder->add('storeCode', ChoiceType::class, [
                'choices' => array_flip($options['stores']),
                'label' => 'branch',
                'required' => false,
            ]);
        }

        $builder
            ->add('ordering', IntegerType::class, [
                'label' => 'ordering',
                'required' => true,
                'attr' => ['min' => 0, 'max' => 999],
            ])
            ->add('workTimesText', TextareaType::class, [
                'label' => 'personal_work_times',
                'help' => 'personal_work_times_help',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => "1: 10:00-13:00, 14:00-18:00\n6: 10:00-15:00"],
            ])
            ->add('nonWorkingDaysText', TextType::class, [
                'label' => 'personal_non_working_days',
                'help' => 'personal_non_working_days_help',
                'required' => false,
                'attr' => ['placeholder' => '09.04-09.18, 12.31'],
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
            ->setRequired(['account', 'stores'])
            ->setAllowedTypes('account', [Account::class])
            ->setAllowedTypes('stores', ['array', 'null'])
        ;
    }
}
