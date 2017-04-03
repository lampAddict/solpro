<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BetType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $lot    = $options['lot'];
        $route  = $options['route'];

        $nextMinAcceptedLotPrice = (is_array($lot) ? $lot['price'] : $lot->getPrice()) - $route['trade_step'];
        
        $builder
            ->add(
                     'value'
                    ,'Symfony\Component\Form\Extension\Core\Type\TextType'
                    ,[
                         'label'=>' '
                        ,'attr'=>[
                                     'class'=>'bid'
                                    ,'placeholder'=>$nextMinAcceptedLotPrice
                                    ,'value'=>$nextMinAcceptedLotPrice
                        ]
                    ])

            ->add(
                     'lot_id'
                    ,HiddenType::class
                    ,[
                        'data'=>(is_array($lot) ? $lot['id'] : $lot)
                    ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
             'data_class' => 'AppBundle\Entity\Bet'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_bet';
    }

    public function setDefaultOptions(\Symfony\Component\OptionsResolver\OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
             'lot' => null
            ,'route' => null
        ]);
    }
}