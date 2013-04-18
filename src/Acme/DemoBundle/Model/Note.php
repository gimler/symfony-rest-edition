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
    public function __toString() {
        return $this->message;
    }
}