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
                         'name'
                        ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                        ,[
                             'label'=>' '
                            ,'attr'=>[
                                         'class'=>'"addVehicleWindowBtn"'
                                        ,'placeholder'=>'ТС'
                            ]
                        ])
                ->add(
                         'type'
                        ,'Symfony\Component\Form\Extension\Core\Type\ChoiceType'
                        ,[
                             'label'=>' '
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
                             'label'=>' '
                            ,'attr'=>[
                                         'class'=>'"addVehicleWindowBtn"'
                                        ,'placeholder'=>'Грузоподъёмность'
                            ]
                        ])
                ->add(
                         'regNum'
                        ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                        ,[
                             'label'=>' '
                            ,'attr'=>[
                                         'class'=>'"addVehicleWindowBtn"'
                                        ,'placeholder'=>'Гос. номер'
                            ]
                        ])
                ->add(
                         'trailerRegNum'
                        ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                        ,[
                             'label'=>' '
                            ,'required'=>false
                            ,'attr'=>[
                                         'class'=>'"addVehicleWindowBtn"'
                                        ,'placeholder'=>'Номер п/п'
                            ]
                        ])
                ->add(
                         'status'
                        ,'Symfony\Component\Form\Extension\Core\Type\ChoiceType'
                        ,[
                             'label'=>' '
                            ,'choices'=>[
                                             'Активен'=>1
                                            ,'Неактивен'=>0
                            ]
                        ,'choices_as_values'=>true
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
