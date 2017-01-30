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
        $user       = $options['user'];
        $transport  = $options['transport'];
        $driver     = $options['driver'];

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
                                    ,'placeholder'=>'01 23 456789'
                        ]
                    ])
            ->add(
                     'transport_id'
                    ,EntityType::class
                    ,[
                         'class' => 'AppBundle:Transport'
                        ,'query_builder' => function (EntityRepository $er) use ($user, $driver) {

                                if( is_null($driver) ){
                                    return $er->createQueryBuilder('t')
                                        ->where('t.status = 1 AND t.user_id = '.$user->getId())
                                        //->andWhere('t.driver_id IS NULL') //show only free to bind vehicles
                                        ->orderBy('t.id', 'DESC')
                                        ;
                                }
                                else{
                                    return $er->createQueryBuilder('t')
                                        ->where('t.status = 1 AND t.user_id = '.$user->getId())
                                        //->andWhere('t.driver_id IS NULL') //show only free to bind vehicles
                                        ->orWhere('t.driver_id = '.$driver->getId())
                                        ->orderBy('t.id', 'DESC')
                                        ;
                                }
                        }
                        ,'choice_label' => function($transport){return $transport->getName() . ' ' . $transport->getRegNum();}
                        ,'label' => $transport
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
