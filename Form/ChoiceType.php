<?php

namespace UJM\ExoBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'ordre', 'text'
            )
            ->add(
                'rightResponse', 'checkbox', array(
                    'required' => false, 'label' => ' '
                )
            )
            ->add(
                'label', 'textarea', array(
                    'label' => ' ',
                    'attr' => array('style' => 'height:34px; ',
                    'class'=>'form-control',
                    'placeholder' => 'expected answer'               
                    )
                )
            )
            ->add(
                'weight', 'text', array(
                    'required' => false, 
                    'label' => ' ', 
                    'attr' => array('class' => 'col-md-1', 'placeholder' => 'points')
                )
            )
            ->add(
                   'feedback', 'textarea', array(
                   'required' => false, 'label' => ' ',
                   'attr' => array('class'=>'form-control',
                                   'data-new-tab' => 'yes',
                                   'placeholder' => 'Choice.feedback',
                                   'style' => 'height:34px;'
                       )
                  )
            )
            ->add(
                'positionForce', 'checkbox', array(
                    'required' => false, 'label' => ' '
                )
            );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'UJM\ExoBundle\Entity\Choice',
            )
        );
    }

    public function getName()
    {
        return 'ujm_exobundle_choicetype';
    }

}
