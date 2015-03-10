<?php

namespace Pumukit\NewAdminBundle\Form\Type\Other;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class Html5dateType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        'compound' => false,
    ));
    }

    public function getParent()
    {
        return 'form';
    }

    public function getName()
    {
        return 'html5date';
    }
}