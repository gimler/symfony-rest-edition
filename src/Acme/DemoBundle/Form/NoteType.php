<?php

namespace Acme\DemoBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class NoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('message', 'textarea');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'         => 'Acme\DemoBundle\Model\Note',
            'intention'          => 'note',
            'translation_domain' => 'AcmeDemoBundle'
        ));
    }

    public function getName()
    {
        return 'note';
    }
}
