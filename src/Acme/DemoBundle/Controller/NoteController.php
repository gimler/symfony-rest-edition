<?php

namespace Acme\DemoBundle\Controller;

use Acme\DemoBundle\Form\NoteType;
use Acme\DemoBundle\Model\Note;

use FOS\Rest\Util\Codes;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\RouteRedirectView;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Rest controller for notes
 *
 * @package Acme\DemoBundle\Controller
 * @author Gordon Franke <info@nevalon.de>
 */
class NoteController extends Controller
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
     * @param ParamFetcherInterface $paramFetcher
     *
     * @return array
     */
    public function getNotesAction(ParamFetcherInterface $paramFetcher)
    {
        $session = $this->getRequest()->getSession();

        $start = 0;
        if (null !== $lastId = $paramFetcher->get('lastId')) {
            $start = $lastId;
        }
        $limit = $paramFetcher->get('limit');

        $notes = $session->get(self::SESSION_CONTEXT_NOTE, array());
        $notes = array_slice($notes, $start, $limit, true);

        return array('notes' => $notes);
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
     * @param int $id note id
     *
     * @return array
     *
     * @throws ResourceNotFoundException when note not exist
     */
    public function getNoteAction($id)
    {
        $session = $this->getRequest()->getSession();
        $notes   = $session->get(self::SESSION_CONTEXT_NOTE);
        if (!isset($notes[$id])) {
            $this->createNotFoundException("Note does not exist.");
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
        $form = $this->createForm(new NoteType());

        return $form;
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
     * @return FormTypeInterface|RouteRedirectView
     */
    public function postNotesAction(Request $request)
    {
        $note = new Note();
        $form = $this->createForm(new NoteType(), $note);

        if ($form->bind($request)->isValid()) {
            $session = $this->getRequest()->getSession();

            $notes   = $session->get(self::SESSION_CONTEXT_NOTE);
            $note->secret = base64_encode($this->get('security.secure_random')->nextBytes(64));
            $notes[] = $note;
            $session->set(self::SESSION_CONTEXT_NOTE, $notes);

            return RouteRedirectView::create('get_notes');
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
     * @param int $id the note id
     *
     * @return FormTypeInterface
     *
     * @throws ResourceNotFoundException when note not exist
     */
    public function editNotesAction($id)
    {
        $session = $this->getRequest()->getSession();

        $notes = $session->get(self::SESSION_CONTEXT_NOTE);
        if (!isset($notes[$id])) {
            $this->createNotFoundException("Note does not exist.");
        }

        $form = $this->createForm(new NoteType(), $notes[$id]);

        return $form;
    }

    /**
     * Update existing note from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   description = "Update existing note from the submitted data.",
     *   input = "Acme\DemoBundle\Form\NoteType",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned when the form has errors",
     *     404 = "Returned when the note is not found"
     *   }
     * )
     *
     * @Annotations\View(
     *   template="AcmeDemoBundle:Note:editNote.html.twig",
     *   statusCode=Codes::HTTP_BAD_REQUEST
     * )
     *
     * @param int $id the note id
     *
     * @return FormTypeInterface|RouteRedirectView
     *
     * @throws ResourceNotFoundException when note not exist
     */
    public function putNotesAction(Request $request, $id)
    {
        $session = $this->getRequest()->getSession();

        $notes   = $session->get(self::SESSION_CONTEXT_NOTE);
        if (!isset($notes[$id])) {
            $this->createNotFoundException("Note does not exist.");
        }
        $note = $notes[$id];

        $form = $this->createForm(new NoteType(), $note);

        if ($form->bind($request)->isValid()) {
            $notes[$id] = $note;
            $session->set(self::SESSION_CONTEXT_NOTE, $notes);

            return RouteRedirectView::create('get_notes', array(), Codes::HTTP_OK);
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
     *     404={
     *       "Returned when the note is not found",
     *     }
     *   }
     * )
     *
     * @param int $id the note id
     *
     * @return RouteRedirectView
     *
     * @throws ResourceNotFoundException when note not exist
     */
    public function deleteNotesAction($id)
    {
        $session = $this->getRequest()->getSession();

        $notes   = $session->get(self::SESSION_CONTEXT_NOTE);
        if (!isset($notes[$id])) {
            $this->createNotFoundException("Note does not exist.");
        }
        unset($notes[$id]);
        $session->set(self::SESSION_CONTEXT_NOTE, $notes);

        return RouteRedirectView::create('get_notes', array(), Codes::HTTP_NO_CONTENT);
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
     * @param int $id the note id
     *
     * @return RouteRedirectView
     */
    public function removeNotesAction($id)
    {
        return $this->deleteNotesAction($id);
    }
}
