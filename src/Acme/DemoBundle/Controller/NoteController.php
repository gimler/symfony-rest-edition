<?php

namespace Acme\DemoBundle\Controller;

use Acme\DemoBundle\Form\NoteType;
use Acme\DemoBundle\Model\Note;
use Acme\DemoBundle\Model\NoteCollection;

use FOS\Rest\Util\Codes;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;

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
     *   description = "List all notes",
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   }
     * )
     *
     * @Annotations\QueryParam(name="lastId", requirements="\d+", nullable=true, description="Last id from previous call.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="5", description="How many records.")
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

        $start = 0;
        if (null !== $lastId = $paramFetcher->get('lastId')) {
            $start = $lastId + 1;
        }
        $limit = $paramFetcher->get('limit');

        $notes = $session->get(self::SESSION_CONTEXT_NOTE, array());
        $notes = array_slice($notes, $start, $limit, true);

        return new NoteCollection($notes, $start, $limit);
    }

    /**
     * Get single note,
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Gets single note for a given id",
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

        return $notes[$id];
    }

    /**
     * Presents the form to use to create a new note.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Presents the form to use to create a new Quiz.",
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   }
     * )
     *
     * @Annotations\View()
     *
     * @return FormTypeInterface
     */
    public function newNotesAction()
    {
        return $this->createForm(new NoteType());
    }

    /**
     * Creates a new note from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new note from the submitted data.",
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
        $session = $this->getRequest()->getSession();
        $notes   = $session->get(self::SESSION_CONTEXT_NOTE);

        $note = new Note();
        $note->id = count($notes);
        $form = $this->createForm(new NoteType(), $note);

        if ($form->bind($request)->isValid()) {
            $note->secret = base64_encode($this->get('security.secure_random')->nextBytes(64));
            $notes[] = $note;
            $session->set(self::SESSION_CONTEXT_NOTE, $notes);

            return $this->routeRedirectView('get_notes');
        }

        return array(
            'form' => $form
        );
    }

    /**
     * Presents the form to use to update existing note.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Presents the form to use to update existing note.",
     *   statusCodes={
     *     200="Returned when successful",
     *     404={
     *       "Returned when the note is not found",
     *     }
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
     * Update existing note from the submitted data or create a new note at a specific location
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Update existing note from the submitted data.",
     *   input = "Acme\DemoBundle\Form\NoteType",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *   }
     * )
     *
     * @Annotations\View(
     *   template="AcmeDemoBundle:Note:editNote.html.twig"
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
        $session = $this->getRequest()->getSession();

        $notes   = $session->get(self::SESSION_CONTEXT_NOTE);
        if (!isset($notes[$id])) {
            $note = new Note();
            $note->id = count($notes);
            $statusCode = Codes::HTTP_CREATED;
        } else {
            $note = $notes[$id];
            $statusCode = Codes::HTTP_OK;
        }

        $form = $this->createForm(new NoteType(), $note);

        if ($form->bind($request)->isValid()) {
            if (!isset($note->secret)) {
                $note->secret = base64_encode($this->get('security.secure_random')->nextBytes(64));
            }
            $notes[$id] = $note;
            $session->set(self::SESSION_CONTEXT_NOTE, $notes);

            return $this->routeRedirectView('get_notes', array(), $statusCode);
        }

        return array(
            'form' => $form
        );
    }


    /**
     * Removes a note
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new note from the submitted data.",
     *   statusCodes={
     *     204="Returned when successful",
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
        $session = $this->getRequest()->getSession();

        $notes   = $session->get(self::SESSION_CONTEXT_NOTE);
        if (isset($notes[$id])) {
            unset($notes[$id]);
            $session->set(self::SESSION_CONTEXT_NOTE, $notes);
        }

        return $this->routeRedirectView('get_notes', array(), Codes::HTTP_NO_CONTENT);
    }

    /**
     * Removes a note
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Creates a new note from the submitted data.",
     *   statusCodes={
     *     204="Returned when successful",
     *     404={
     *       "Returned when the note is not found",
     *     }
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int     $id      the note id
     *
     * @return RouteRedirectView
     */
    public function removeNotesAction(Request $request, $id)
    {
        return $this->deleteNotesAction($request, $id);
    }
}
