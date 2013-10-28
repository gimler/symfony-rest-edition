<?php

namespace Acme\DemoBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Client;

class NoteControllerTest extends WebTestCase
{
    private function getClient($authenticated = false)
    {
        $params = array();
        if ($authenticated) {
            $params = array_merge($params, array(
                'PHP_AUTH_USER' => 'restapi',
                'PHP_AUTH_PW'   => 'secretpw',
            ));
        }

        return static::createClient(array(), $params);
    }
    public function testGetNotes()
    {
        $client = $this->getClient(true);

        // head request
        $client->request('HEAD', '/notes.json');
        $response = $client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());

        // empty list
        $client->request('GET', '/notes.json');
        $response = $client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"notes":[],"limit":5}', $response->getContent());

        // list
        $this->createNote($client, 'my note for list');

        $client->request('GET', '/notes.json');
        $response = $client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"notes":[{"message":"my note for list","links":{"self":{"href":"http:\/\/localhost\/notes\/0"}}}],"limit":5}', $response->getContent());
    }

    public function testGetNote()
    {
        $client = $this->getClient(true);

        $client->request('GET', '/notes/0.json');
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"code":404,"message":"Note does not exist."}', $response->getContent());

        $this->createNote($client, 'my note for get');

        $client->request('GET', '/notes/0.json');
        $response = $client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"message":"my note for get","links":{"self":{"href":"http:\/\/localhost\/notes\/0"}}}', $response->getContent());
    }

    public function testNewNote()
    {
        $client = $this->getClient(true);

        $client->request('GET', '/notes/new.json');
        $response = $client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"children":{"message":[]}}', $response->getContent());
    }

    public function testPostNote()
    {
        $client = $this->getClient(true);

        $this->createNote($client, 'my note for post');

        $response = $client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($response->headers->contains('location', 'http://localhost/notes'));
    }

    public function testEditNote()
    {
        $client = $this->getClient(true);

        $client->request('GET', '/notes/0/edit.json');
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"code":404,"message":"Note does not exist."}', $response->getContent());

        $this->createNote($client, 'my note for post');

        $client->request('GET', '/notes/0/edit.json');
        $response = $client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"children":{"message":[]}}', $response->getContent());
    }

    public function testPutNote()
    {
        $client = $this->getClient(true);

        $client->request('PUT', '/notes/0.json', array(
            'note' => array(
                'message' => ''
            )
        ));
        $response = $client->getResponse();

        $this->assertEquals(400, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"code":400,"message":"Validation Failed","errors":{"children":{"message":{"errors":["This value should not be blank."]}}}}', $response->getContent());

        $this->createNote($client, 'my note for post');

        $client->request('PUT', '/notes/0.json', array(
            'note' => array(
                'message' => 'my note for put'
            )
        ));
        $response = $client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($response->headers->contains('location', 'http://localhost/notes'));
    }

    public function testRemoveNote()
    {
        $client = $this->getClient(true);

        $client->request('GET', '/notes/0/remove.json');
        $response = $client->getResponse();

        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('', $response->getContent());

        $this->createNote($client, 'my note for get');

        $client->request('GET', '/notes/0/remove.json');
        $response = $client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($response->headers->contains('location', 'http://localhost/notes'));
    }

    public function testDeleteNote()
    {
        $client = $this->getClient(true);

        $client->request('DELETE', '/notes/0.json');
        $response = $client->getResponse();

        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('', $response->getContent());

        $this->createNote($client, 'my note for get');

        $client->request('DELETE', '/notes/0.json');
        $response = $client->getResponse();

        $this->assertJsonHeader($response);
        $this->assertEquals(204, $response->getStatusCode(), $response->getContent());
        $this->assertTrue($response->headers->contains('location', 'http://localhost/notes'));
    }

    protected function createNote(Client $client, $message)
    {
        $client->request('POST', '/notes.json', array(
            'note' => array(
                'message' => $message
            )
        ));
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode(), $response->getContent());
    }

    protected function assertJsonHeader($response)
    {
        $this->assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            $response->headers
        );
    }
}