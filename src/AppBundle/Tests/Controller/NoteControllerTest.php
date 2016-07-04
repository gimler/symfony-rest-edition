<?php

namespace AppBundle\Tests\Controller;

use Bazinga\Bundle\RestExtraBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\HttpFoundation\Response;

class NoteControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $cacheDir = $this->getClient()->getContainer()->getParameter('kernel.cache_dir');
        if (file_exists($cacheDir . '/sf_note_data')) {
            unlink($cacheDir . '/sf_note_data');
        }
    }

    public function testGetNotes()
    {
        $client = $this->getClient(true);

        // head request
        $client->request('HEAD', '/notes.json');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode(), $response->getContent());

        // empty list
        $client->request('GET', '/notes.json');
        $response = $client->getResponse();

        $this->assertJsonResponse($response);
        $this->assertEquals('{"notes":[],"limit":5,"_links":{"self":{"href":"http:\/\/localhost\/notes"},"note":{"href":"http:\/\/localhost\/notes\/{id}","templated":true}}}', $response->getContent());

        // list
        $this->createNote($client, 'my note for list');

        $client->request('GET', '/notes.json');
        $response = $client->getResponse();

        $this->assertJsonResponse($response);
        $contentWithoutSecret = preg_replace('/"secret":"[^"]*"/', '"secret":"XXX"', $response->getContent());
        $this->assertEquals('{"notes":[{"secret":"XXX","message":"my note for list","version":"1","_links":{"self":{"href":"http:\/\/localhost\/notes\/0"}}}],"limit":5,"_links":{"self":{"href":"http:\/\/localhost\/notes"},"note":{"href":"http:\/\/localhost\/notes\/{id}","templated":true}}}', $contentWithoutSecret);
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

        $this->assertJsonResponse($response);
        $contentWithoutSecret = preg_replace('/"secret":"[^"]*"/', '"secret":"XXX"', $response->getContent());
        $this->assertEquals('{"secret":"XXX","message":"my note for get","version":"1","_links":{"self":{"href":"http:\/\/localhost\/notes\/0"}}}', $contentWithoutSecret);

        $client->request('GET', '/notes/0', array(), array(), array('HTTP_ACCEPT' => 'application/json'));
        $response = $client->getResponse();

        $this->assertJsonResponse($response);
        $contentWithoutSecret = preg_replace('/"secret":"[^"]*"/', '"secret":"XXX"', $response->getContent());
        $this->assertEquals('{"secret":"XXX","message":"my note for get","version":"1","_links":{"self":{"href":"http:\/\/localhost\/notes\/0"}}}', $contentWithoutSecret);
    }

    public function testGetNoteVersioned()
    {
        $client = $this->getClient(true);

        $client->request('GET', '/notes/0.json');
        $response = $client->getResponse();

        $this->assertEquals(404, $response->getStatusCode(), $response->getContent());
        $this->assertEquals('{"code":404,"message":"Note does not exist."}', $response->getContent());

        $this->createNote($client, 'my note for get');

        $client->request('GET', '/notes/0', array(), array(), array('HTTP_ACCEPT' => 'application/json;version=1.0'));
        $response = $client->getResponse();

        $this->assertJsonResponse($response);
        $contentWithoutSecret = preg_replace('/"secret":"[^"]*"/', '"secret":"XXX"', $response->getContent());
        $this->assertEquals('{"secret":"XXX","message":"my note for get","version":"1","_links":{"self":{"href":"http:\/\/localhost\/notes\/0"}}}', $contentWithoutSecret);

        $client->request('GET', '/notes/0', array(), array(), array('HTTP_ACCEPT' => 'application/json;version=1.2'));
        $response = $client->getResponse();

        $this->assertJsonResponse($response);
        $contentWithoutSecret = preg_replace('/"secret":"[^"]*"/', '"secret":"XXX"', $response->getContent());
        $this->assertEquals('{"secret":"XXX","message":"my note for get","version":"1.1","_links":{"self":{"href":"http:\/\/localhost\/notes\/0"}}}', $contentWithoutSecret);
    }

    public function testNewNote()
    {
        $client = $this->getClient(true);

        $client->request('GET', '/notes/new.json');
        $response = $client->getResponse();

        $this->assertJsonResponse($response);
        $this->assertEquals('{"children":{"message":{}}}', $response->getContent());
    }

    public function testPostNote()
    {
        $client = $this->getClient(true);

        $this->createNote($client, 'my note for post');

        $response = $client->getResponse();

        $this->assertJsonResponse($response, Response::HTTP_CREATED);
        $this->assertEquals($response->headers->get('location'), 'http://localhost/notes/0');
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

        $this->assertJsonResponse($response);
        $this->assertEquals('{"children":{"message":{}}}', $response->getContent());
    }

    public function testPutShouldModifyANote()
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

        $this->assertEquals(
            Response::HTTP_NO_CONTENT, $response->getStatusCode(),
            $response->getContent()
        );
        $this->assertEquals($response->headers->get('location'), 'http://localhost/notes/0');
    }

    public function testPutShouldCreateANote()
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

        $client->request('PUT', '/notes/0.json', array(
            'note' => array(
                'message' => 'my note for put'
            )
        ));
        $response = $client->getResponse();

        $this->assertJsonResponse($response, Response::HTTP_CREATED);
        $this->assertEquals($response->headers->get('location'), 'http://localhost/notes/0');
    }

    public function testRemoveNote()
    {
        $client = $this->getClient(true);

        $client->request('GET', '/notes/0/remove.json');
        $response = $client->getResponse();

        $this->assertEquals(
            Response::HTTP_NO_CONTENT, $response->getStatusCode(),
            $response->getContent()
        );

        $this->createNote($client, 'my note for get');

        $client->request('GET', '/notes/0/remove.json');
        $response = $client->getResponse();

        $this->assertEquals(
            Response::HTTP_NO_CONTENT, $response->getStatusCode(),
            $response->getContent()
        );

        $this->assertTrue($response->headers->contains('location', 'http://localhost/notes'));
    }

    public function testDeleteNote()
    {
        $client = $this->getClient(true);

        $client->request('DELETE', '/notes/0.json');
        $response = $client->getResponse();

        $this->assertEquals(
            Response::HTTP_NO_CONTENT, $response->getStatusCode(),
            $response->getContent()
        );

        $this->createNote($client, 'my note for get');

        $client->request('DELETE', '/notes/0.json');
        $response = $client->getResponse();

        $this->assertEquals(
            Response::HTTP_NO_CONTENT, $response->getStatusCode(),
            $response->getContent()
        );
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
        $this->assertJsonResponse($response, Response::HTTP_CREATED);
    }

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
}
