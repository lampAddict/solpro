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
                     'passport'
                    ,'Symfony\Component\Form\Extension\Core\Type\TextareaType'
                    ,[
                         'label'=>'Паспортные данные'
                        ,'attr'=>[
                                     'class'=>'"addDriverWindowBtn"'
                                    ,'placeholder'=>'Серия, номер, кем и когда выдан'
                        ]
                    ])
            ->add(
                     'driverLicense'
                    ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                    ,[
                         'label'=>'Водительское удостоверение'
                        ,'attr'=>[
                                     'class'=>'"addDriverWindowBtn"'
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
