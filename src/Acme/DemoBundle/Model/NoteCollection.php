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
    public $start;

    /**
     * @var integer
     */
    public $limit;

    /**
     * @param Note[] $notes
     * @param integer $start
     * @param integer $limit
     */
    public function __construct($notes = array(), $start = null, $limit = null)
    {
        $this->notes = $notes;
        $this->start = $start;
        $this->limit = $limit;
    }
}