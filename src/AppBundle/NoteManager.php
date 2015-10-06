<?php

namespace AppBundle;

use Symfony\Component\Security\Core\Util\SecureRandomInterface;

class NoteManager
{
    /** @var array notes */
    protected $data = array();

    /**
     * @var \Symfony\Component\Security\Core\Util\SecureRandomInterface
     */
    protected $randomGenerator;

    /**
     * @var string
     */
    protected $cacheDir;

    public function __construct(SecureRandomInterface $randomGenerator, $cacheDir)
    {
        if (file_exists($cacheDir . '/sf_note_data')) {
            $data = file_get_contents($cacheDir . '/sf_note_data');
            $this->data = unserialize($data);
        }

        $this->randomGenerator = $randomGenerator;
        $this->cacheDir = $cacheDir;
    }

    private function flush()
    {
        file_put_contents($this->cacheDir . '/sf_note_data', serialize($this->data));
    }

    public function fetch($start = 0, $limit = 5)
    {
        return array_slice($this->data, $start, $limit, true);
    }

    public function get($id)
    {
        if (!isset($this->data[$id])) {
            return false;
        }

        return $this->data[$id];
    }

    public function set($note)
    {
        if (null === $note->id) {
            if (empty($this->data)) {
                $note->id = 0;
            } else {
                end($this->data);
                $note->id = key($this->data) + 1;
            }
        }

        if (null === $note->secret) {
            $note->secret = base64_encode($this->randomGenerator->nextBytes(64));
        }

        $this->data[$note->id] = $note;
        $this->flush();
    }

    public function remove($id)
    {
        if (!isset($this->data[$id])) {
            return false;
        }

        unset($this->data[$id]);
        $this->flush();

        return true;
    }
}
