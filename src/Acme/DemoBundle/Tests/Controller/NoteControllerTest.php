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
        $this->assertEquals('{"notes":[],"limit":5}', $response->getContent());

        // list
        $this->createNote('my note for list');

        $this->client->request('GET', '/notes.json');
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"notes":[{"message":"my note for list","links":{"self":{"href":"http:\/\/localhost\/notes\/0"}}}],"limit":5}', $response->getContent());
    }

    public function testGetNote()
    {
        $this->client->request('GET', '/notes/0.json');
        $response = $this->client->getResponse();

        $this->assertEquals(404, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"code":404,"message":"Note does not exist."}', $response->getContent());

        $this->createNote('my note for get');

        $this->client->request('GET', '/notes/0.json');
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"message":"my note for get","links":{"self":{"href":"http:\/\/localhost\/notes\/0"}}}', $response->getContent());
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
        $this->assertTrue($response->headers->contains('location', 'http://localhost/notes'));
    }

    public function testEditNote()
    {
        $this->client->request('GET', '/notes/0/edit.json');
        $response = $this->client->getResponse();

        $this->assertEquals(404, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"code":404,"message":"Note does not exist."}', $response->getContent());

        $this->createNote('my note for post');

        $this->client->request('GET', '/notes/0/edit.json');
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"children":{"message":[]}}', $response->getContent());
    }

    public function testPutNote()
    {
        $this->client->request('PUT', '/notes/0.json', array(
            'note' => array(
                'message' => ''
            )
        ));
        $response = $this->client->getResponse();

        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"code":400,"message":"Validation Failed","errors":{"children":{"message":{"errors":["This value should not be blank."]}}}}', $response->getContent());

        $this->createNote('my note for post');

        $this->client->request('PUT', '/notes/0.json', array(
            'note' => array(
                'message' => 'my note for put'
            )
        ));
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($response->headers->contains('location', 'http://localhost/notes'));
    }

    public function testRemoveNote()
    {
        $this->client->request('GET', '/notes/0/remove.json');
        $response = $this->client->getResponse();

        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('', $response->getContent());

        $this->createNote('my note for get');

        $this->client->request('GET', '/notes/0/remove.json');
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($response->headers->contains('location', 'http://localhost/notes'));
        // see https://github.com/symfony/symfony/pull/7610
        //$this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/notes'));
    }

    public function testDeleteNote()
    {
        $this->client->request('DELETE', '/notes/0.json');
        $response = $this->client->getResponse();

        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('', $response->getContent());

        $this->createNote('my note for get');

        $this->client->request('DELETE', '/notes/0.json');
        $response = $this->client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($response->headers->contains('location', 'http://localhost/notes'));
        // see https://github.com/symfony/symfony/pull/7610
        //$this->assertTrue($this->client->getResponse()->isRedirect('http://localhost/notes'));
    }

    protected function createNote($message)
    {
        $this->client->request('POST', '/notes.json', array(
            'note' => array(
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