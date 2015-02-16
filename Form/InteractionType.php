<?php

namespace UJM\ExoBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Claroline\CoreBundle\Entity\User;

class InteractionType extends AbstractType
{

    private $user;
    private $catID;

    public function __construct(User $user, $catID = -1)
    {
        $this->user = $user;
        $this->catID = $catID;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'question', new QuestionType(
                    $this->user, $this->catID
                )
            )
            ->add('invite', 'tinymce', array(
                    'label' => 'Interaction.invite',
                    'required' => true
                )
            )
            ->add(
                'ordre', 'hidden', array(
                    'required' => false
                )
            )
            ->add('feedBack', 'tinymce', array(
                    //for automatically open documents in a new tab for all tinymce field
                    'attr' => array('data-new-tab' => 'yes'),
                    'label' => 'Interaction.feedback', 'required' => false
                )
            )
            //->add('locked_expertise', 'checkbox', array('required' => false))
            //->add('documents')
            ->add(
                'hints', 'collection', array(
                    'type' => new HintType,
                    'prototype' => true,
                    'allow_add' => true,
                    'allow_delete' => true
                )
            );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'UJM\ExoBundle\Entity\Interaction',
                'cascade_validation' => true
            )
        );
    }

    public function getName()
    {
        return 'ujm_exobundle_interactiontype';
    }

}