<?php

namespace Acme\DemoBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class DisableCSRFExtension extends AbstractTypeExtension
{
    private $securityContext;

    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        if (!$this->securityContext->getToken()) {
            return;
        }

        if (!$this->securityContext->isGranted('ROLE_NO_CSRF_REQUIRED')) {
            return;
        }

        $resolver->setDefaults(array(
            'csrf_protection' => false,
        ));
    }

    public function getExtendedType()
    {
        return 'form';
    }
}
