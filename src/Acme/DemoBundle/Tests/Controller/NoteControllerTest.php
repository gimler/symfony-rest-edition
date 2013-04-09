<?php

namespace Acme\DemoBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NoteControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected $client;

    public function setUp()
    {
        $this->client = static::createClient();
    }

    public function tearDown()
    {
        unset($this->client);
    }

    public function testGetNotes()
    {
        // head request
        $this->client->request('HEAD', '/notes.json');
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());

        // empty list
        $this->client->request('GET', '/notes.json');
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"notes":[]}', $response->getContent());

        // list
        $this->createNote('my note for list');

        $this->client->request('GET', '/notes.json');
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"notes":[{"message":"my note for list"}]}', $response->getContent());
    }

    public function testGetNote()
    {
        $this->createNote('my note for get');

        $this->client->request('GET', '/notes/0.json');
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"message":"my note for get"}', $response->getContent());
    }

    public function testNewNote()
    {
        $this->client->request('GET', '/notes/new.json');
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"children":{"message":[]}}', $response->getContent());
    }

    public function testPostNote()
    {
        $this->createNote('my note for post');

        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
    }

    public function testEditNote()
    {
        $this->createNote('my note for post');

        $this->client->request('GET', '/notes/0/edit.json');
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"children":{"message":[]}}', $response->getContent());
    }

    public function testPutNote()
    {
        $this->createNote('my note for post');

        $csrfToken = $this->client
            ->getContainer()
            ->get('form.csrf_provider')
            ->generateCsrfToken('note');

        $this->client->request('PUT', '/notes/0.json', array(
            'note' => array(
                '_token'  => $csrfToken,
                'message' => 'my note for put'
            )
        ));
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/notes'));
    }

    public function testRemoveNote()
    {
        $this->createNote('my note for get');

        $this->client->request('GET', '/notes/0/remove.json');
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('', $response->getContent());
        // see https://github.com/symfony/symfony/pull/7610
        //$this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/notes'));
    }

    public function testDeleteNote()
    {
        $this->createNote('my note for get');

        $this->client->request('DELETE', '/notes/0.json');
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('', $response->getContent());
        // see https://github.com/symfony/symfony/pull/7610
        //$this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/notes'));
    }

    protected function createNote($message)
    {
        $csrfToken = $this->client
            ->getContainer()
            ->get('form.csrf_provider')
            ->generateCsrfToken('note');

        $this->client->request('POST', '/notes.json', array(
            'note' => array(
                '_token'  => $csrfToken,
                'message' => $message
            )
        ));
        $response = $this->client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
    }

    protected function assertJsonHeader($response) {
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            $response->headers
        );
    }
}