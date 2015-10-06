<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class NoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('message', 'textarea', array(
            'description' => 'A brief record of points or ideas written down as an aid to memory',
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'         => 'AppBundle\Model\Note',
            'intention'          => 'note',
            'translation_domain' => 'AppBundle'
        ));
    }

    public function getName()
    {
        return 'note';
    }
}
