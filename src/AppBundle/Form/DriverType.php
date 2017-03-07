<?php

namespace AppBundle\Form;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DriverType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                     'status'
                    ,'Symfony\Component\Form\Extension\Core\Type\ChoiceType'
                    ,[
                         'label'=>'Статус'
                        ,'choices'=>[
                             'Активен'=>1
                            ,'Неактивен'=>0
                        ]
                        ,'choices_as_values'=>true
                    ])
            ->add(
                     'fio'
                    ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                    ,[
                         'label'=>'Фамилия имя отчество'
                        ,'attr'=>[
                                     'class'=>''
                                    ,'placeholder'=>'Иванов Иван Иванович'
                        ]
                    ])
            ->add(
                     'phone'
                    ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                    ,[
                         'label'=>'Телефон'
                        ,'attr'=>[
                                     'class'=>''
                        ]
                    ])
            ->add(
                    'passport_type'
                    ,EntityType::class
                    ,[
                        'class' => 'AppBundle:RefPassport'
                        ,'query_builder' => function (EntityRepository $er){

                            return $er->createQueryBuilder('rfp')
                                      ->orderBy('rfp.id', 'ASC')
                            ;
                        }
                        ,'choice_label' => function($p){return $p->getName();}
                        ,'label' => 'Выберите тип документа'
                    ])
            ->add(
                    'passport_series'
                    ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                    ,[
                        'label'=>'Паспортные данные, серия'
                        ,'attr'=>[
                                     'class'=>'passportSeries'
                                    ,'placeholder'=>''
                        ]
                    ])
            ->add(
                    'passport_number'
                    ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                    ,[
                        'label'=>'номер'
                        ,'attr'=>[
                                     'class'=>'passportNumber'
                                    ,'placeholder'=>''
                        ]
                    ])
            ->add(
                    'passport_date_issue'
                    ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                    ,[
                        'label'=>'Когда выдан'
                        ,'attr'=>[
                                     'class'=>'passportDateIssue'
                                    ,'placeholder'=>''
                        ]
                    ])
            ->add(
                     'passport_issued_by'
                    ,'Symfony\Component\Form\Extension\Core\Type\TextareaType'
                    ,[
                         'label'=>'Кем выдан'
                        ,'attr'=>[
                                     'class'=>'passportIssuedBy'
                                    ,'placeholder'=>''
                        ]
                    ])
            ->add(
                     'driverLicense'
                    ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                    ,[
                         'label'=>'Водительское удостоверение'
                        ,'attr'=>[
                                     'class'=>'driverLicense'
                                    ,'placeholder'=>''
                        ]
                    ])
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Driver'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_driver';
    }
    
    public function setDefaultOptions(\Symfony\Component\OptionsResolver\OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
             'user' => null
            ,'transport' => null
            ,'driver'=> null
        ]);
    }
}
