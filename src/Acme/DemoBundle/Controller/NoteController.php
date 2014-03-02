<?php

namespace Acme\DemoBundle\Controller;

use Acme\DemoBundle\Form\NoteType;
use Acme\DemoBundle\Model\Note;
use Acme\DemoBundle\Model\NoteCollection;

use FOS\RestBundle\Util\Codes;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\RouteRedirectView;

use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Rest controller for notes
 *
 * @package Acme\DemoBundle\Controller
 * @author Gordon Franke <info@nevalon.de>
 */
class NoteController extends FOSRestController
{
    const SESSION_CONTEXT_NOTE = 'notes';

    /**
     * List all notes.
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   }
     * )
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing notes.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="5", description="How many notes to return.")
     *
     * @Annotations\View()
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return array
     */
    public function getNotesAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $session = $request->getSession();

        $offset = $paramFetcher->get('offset');
        $start = null == $offset ? 0 : $offset + 1;
        $limit = $paramFetcher->get('limit');

        $notes = $session->get(self::SESSION_CONTEXT_NOTE, array());
        $notes = array_slice($notes, $start, $limit, true);

        return new NoteCollection($notes, $offset, $limit);
    }

    /**
     * Get a single note.
     *
     * @ApiDoc(
     *   output = "Acme\DemoBundle\Model\Note",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the note is not found"
     *   }
     * )
     *
     * @Annotations\View(templateVar="note")
     *
     * @param Request $request the request object
     * @param int     $id      the note id
     *
     * @return array
     *
     * @throws NotFoundHttpException when note not exist
     */
    public function getNoteAction(Request $request, $id)
    {
        $session = $request->getSession();
        $notes   = $session->get(self::SESSION_CONTEXT_NOTE);
        if (!isset($notes[$id])) {
            throw $this->createNotFoundException("Note does not exist.");
        }

        $view = new View($notes[$id]);
        $group = $this->container->get('security.context')->isGranted('ROLE_API') ? 'restapi' : 'standard';
        $view->getSerializationContext()->setGroups(array('Default', $group));

        return $view;
    }

    /**
     * Presents the form to use to create a new note.
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   }
     * )
     *
     * @Annotations\View()
     *
     * @return FormTypeInterface
     */
    public function newNoteAction()
    {
        return $this->createForm(new NoteType());
    }

    /**
     * Creates a new note from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "Acme\DemoBundle\Form\NoteType",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned when the form has errors"
     *   }
     * )
     *
     * @Annotations\View(
     *   template = "AcmeDemoBundle:Note:newNote.html.twig",
     *   statusCode = Codes::HTTP_BAD_REQUEST
     * )
     *
     * @param Request $request the request object
     *
     * @return FormTypeInterface|RouteRedirectView
     */
    public function postNotesAction(Request $request)
    {
        $session = $request->getSession();
        $notes   = $session->get(self::SESSION_CONTEXT_NOTE);

        $note = new Note();
        $note->id = $this->getValidIndex($notes);
        $form = $this->createForm(new NoteType(), $note);

        $form->submit($request);
        if ($form->isValid()) {
            $note->secret = base64_encode($this->get('security.secure_random')->nextBytes(64));
            $notes[$note->id] = $note;
            $session->set(self::SESSION_CONTEXT_NOTE, $notes);

            return $this->routeRedirectView('get_note', array('id' => $note->id));
        }

        return array(
            'form' => $form
        );
    }

    /**
     * Presents the form to use to update an existing note.
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes={
     *     200 = "Returned when successful",
     *     404 = "Returned when the note is not found"
     *   }
     * )
     *
     * @Annotations\View()
     *
     * @param Request $request the request object
     * @param int     $id      the note id
     *
     * @return FormTypeInterface
     *
     * @throws NotFoundHttpException when note not exist
     */
    public function editNotesAction(Request $request, $id)
    {
        $session = $request->getSession();

        $notes = $session->get(self::SESSION_CONTEXT_NOTE);
        if (!isset($notes[$id])) {
            throw $this->createNotFoundException("Note does not exist.");
        }

        $form = $this->createForm(new NoteType(), $notes[$id]);

        return $form;
    }

    /**
     * Update existing note from the submitted data or create a new note at a specific location.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "Acme\DemoBundle\Form\NoteType",
     *   statusCodes = {
     *     201 = "Returned when a new resource is created",
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors"
     *   }
     * )
     *
     * @Annotations\View(
     *   template="AcmeDemoBundle:Note:editNote.html.twig",
     *   templateVar="form"
     * )
     *
     * @param Request $request the request object
     * @param int     $id      the note id
     *
     * @return FormTypeInterface|RouteRedirectView
     *
     * @throws NotFoundHttpException when note not exist
     */
    public function putNotesAction(Request $request, $id)
    {
        $session = $request->getSession();

        $notes   = $session->get(self::SESSION_CONTEXT_NOTE);
        if (!isset($notes[$id])) {
            $note = new Note();
            $note->id = $id;
            $statusCode = Codes::HTTP_CREATED;
        } else {
            $note = $notes[$id];
            $statusCode = Codes::HTTP_NO_CONTENT;
        }

        $form = $this->createForm(new NoteType(), $note);

        $form->submit($request);
        if ($form->isValid()) {
            if (!isset($note->secret)) {
                $note->secret = base64_encode($this->get('security.secure_random')->nextBytes(64));
            }
            $notes[$id] = $note;
            $session->set(self::SESSION_CONTEXT_NOTE, $notes);

            return $this->routeRedirectView('get_note', array('id' => $note->id), $statusCode);
        }

        return $form;
    }

    /**
     * Removes a note.
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes={
     *     204="Returned when successful",
     *     404="Returned when the note is not found"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int     $id      the note id
     *
     * @return RouteRedirectView
     *
     * @throws NotFoundHttpException when note not exist
     */
    public function deleteNotesAction(Request $request, $id)
    {
        $session = $request->getSession();
        $notes   = $session->get(self::SESSION_CONTEXT_NOTE);
        if (!isset($notes[$id])) {
            throw $this->createNotFoundException("Note does not exist.");
        }

        unset($notes[$id]);
        $session->set(self::SESSION_CONTEXT_NOTE, $notes);

        return $this->routeRedirectView('get_notes', array(), Codes::HTTP_NO_CONTENT);
    }

    /**
     * Removes a note.
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes={
     *     204="Returned when successful",
     *     404="Returned when the note is not found"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int     $id      the note id
     *
     * @return RouteRedirectView
     *
     * @throws NotFoundHttpException when note not exist
     */
    public function removeNotesAction(Request $request, $id)
    {
        return $this->deleteNotesAction($request, $id);
    }

    /**
     * Get a valid index key.
     *
     * @param array $notes
     *
     * @return int $id
     */
    private function getValidIndex($notes)
    {
        $id = count($notes);
        while (isset($notes[$id])) {
            $id++;
        }

        return $id;
    }

}
