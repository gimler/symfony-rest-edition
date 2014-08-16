<?php

namespace Acme\DemoBundle;

class NoteManager
{
    /** @var array notes */
    protected $data = array();

    public function __construct() {
        if (file_exists(sys_get_temp_dir() . '/sf_note_data')) {
            $data = file_get_contents(sys_get_temp_dir() . '/sf_note_data');
            $this->data = unserialize($data);
        }
    }

    public function __destruct() {
        file_put_contents(sys_get_temp_dir() . '/sf_note_data', serialize($this->data));
    }

    public function fetch($start = 0, $limit = 5) {
        return array_slice($this->data, $start, $limit, true);
    }

    public function get($id) {
        if (!isset($this->data[$id])) {
            return false;
        }

        return $this->data[$id];
    }

    public function set($note) {
        if (null === $note->id) {
            //TODO: find biggest id
            $note->id = count($this->data);
        }
        $this->data[$note->id] = $note;
    }

    public function remove($id) {
        if (!isset($this->data[$id])) {
            return false;
        }

        unset($this->data[$id]);

        return true;
    }
}