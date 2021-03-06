<?php

namespace AppBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
                        ])
                ->add(
                         'type'
                        ,EntityType::class
                        ,[
                             'class' => 'AppBundle:RefVehicleType'
                            ,'query_builder' => function (EntityRepository $er){
                                return $er->createQueryBuilder('vt')
                                    ->orderBy('vt.id', 'DESC')
                                    ;
                            }
                            ,'choice_label' => function($type){return $type->getName();}
                            ,'choice_value' => 'id'
                            ,'label'=>'Тип кузова'
                        ])
                ->add(
                         'payload'
                        ,EntityType::class
                        ,[
                             'class' => 'AppBundle:RefVehicleCarryingType'
                            ,'query_builder' => function (EntityRepository $er){
                                return $er->createQueryBuilder('vct')
                                    ->orderBy('vct.id', 'DESC')
                                    ;
                            }
                            ,'choice_label' => function($vct){return $vct->getName();}
                            ,'choice_value' => 'id'
                            ,'label'=>'Грузоподъёмность, т'
                        ])
                ->add(
                         'regNum'
                        ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                        ,[
                             'label'=>'Автомобильный номер'
                            ,'attr'=>[
                                         'class'=>''
                                        ,'placeholder'=>'х000хх 000'
                                        ,'maxlength'=>10
                            ]
                        ])
                ->add(
                         'trailerRegNum'
                        ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                        ,[
                             'label'=>'Номер полуприцепа (необязательное поле)'
                            ,'required'=>false
                            ,'attr'=>[
                                         'class'=>''
                                        ,'placeholder'=>'хх0000 00'
                                        ,'maxlength'=>9
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
