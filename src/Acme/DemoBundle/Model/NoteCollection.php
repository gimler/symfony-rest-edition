<?php

namespace Acme\DemoBundle\Model;

class NoteCollection
{
    /**
     * @var Note[]
     */
    public $notes;

    /**
     * @var integer
     */
    public $offset;

    /**
     * @var integer
     */
    public $limit;

    /**
     * @param Note[]  $notes
     * @param integer $offset
     * @param integer $limit
     */
    public function __construct($notes = array(), $offset = null, $limit = null)
    {
        $this->notes = $notes;
        $this->offset = $offset;
        $this->limit = $limit;
    }
}
