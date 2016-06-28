<?php

namespace AppBundle\Model;

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
     * @var string This version will be used since 1.1
     */
    public $new_version = 1.1;

    /**
     * The order is important here, if no version is set, then this property
     * will override the previous one due to the mapping of ``serialized_name``
     * to ``version`` for the ``new_version`` property. However since we
     * define a default version in the ``fos_rest`` config it doesn't matter.
     *
     * @var string The original version
     */
    public $version = 1;

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
