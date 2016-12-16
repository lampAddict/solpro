<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransportType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
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
                         'name'
                        ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                        ,[
                             'label'=>'Марка транспортного средства'
                            ,'attr'=>[
                                         'class'=>'"addVehicleWindowBtn"'
                                        ,'placeholder'=>''
                            ]
                        ])
                ->add(
                         'type'
                        ,'Symfony\Component\Form\Extension\Core\Type\ChoiceType'
                        ,[
                             'label'=>'Тип кузова'
                            ,'choices'=>[
                                             'Реф'
                                            ,'Тент'
                                            ,'Изотерм'
                                            ,'Цистерна'
                            ]
                            ,'attr'=>[
                                    'class'=>'"addVehicleWindowBtn"'
                            ]
                        ])
                ->add(
                         'payload'
                        ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                        ,[
                             'label'=>'Грузоподъёмность, т'
                            ,'attr'=>[
                                         'class'=>'"addVehicleWindowBtn"'
                                        ,'placeholder'=>''
                            ]
                        ])
                ->add(
                         'regNum'
                        ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                        ,[
                             'label'=>'Автомобильный номер'
                            ,'attr'=>[
                                         'class'=>'"addVehicleWindowBtn"'
                                        ,'placeholder'=>'е097кх 97'
                            ]
                        ])
                ->add(
                         'trailerRegNum'
                        ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                        ,[
                             'label'=>'Номер полуприцепа (необязательное поле)'
                            ,'required'=>false
                            ,'attr'=>[
                                         'class'=>'"addVehicleWindowBtn"'
                                        ,'placeholder'=>'аа9797 97'
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
            'data_class' => 'AppBundle\Entity\Transport'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_transport';
    }
}
