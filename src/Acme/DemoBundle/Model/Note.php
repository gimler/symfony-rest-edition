<?php

namespace Acme\DemoBundle\Model;

class Note
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $secret;

    /**
     * @var string The note message
     */
    public $message;

    /**
     * String representation for a note
     *
     * @return string
     */
    public function __toString()
    {
        return $this->message;
    }

    public function getAssociatedEventsRel()
    {
        return 'associated_events';
    }

    public function getAssociatedEvents()
    {
        return array(
            new Event('SymfonyCon',    new \DateTime('December 12, 2013')),
            new Event('Christmas Day', new \DateTime('December 25, 2013')),
        );
    }
}
