<?php

namespace AppBundle\Controller;

use AppBundle\Form\NoteType;
use AppBundle\Model\Note;
use AppBundle\Model\NoteCollection;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Rest controller for notes
 *
 * @package AppBundle\Controller
 * @author Gordon Franke <info@nevalon.de>
 */
class NoteController extends FOSRestController
{
    /**
     * return \AppBundle\NoteManager
     */
    public function getNoteManager()
    {
        return $this->get('app.note_manager');
    }

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
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return array
     */
    public function getNotesAction(ParamFetcherInterface $paramFetcher)
    {
        $offset = $paramFetcher->get('offset');
        $start = null == $offset ? 0 : $offset + 1;
        $limit = $paramFetcher->get('limit');

        $notes = $this->getNoteManager()->fetch($start, $limit);

        return new NoteCollection($notes, $offset, $limit);
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
     * Get a single note.
     *
     * @ApiDoc(
     *   output = "AppBundle\Model\Note",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the note is not found"
     *   }
     * )
     *
     * @Annotations\View(templateVar="note")
     *
     * @param int $id the note id
     *
     * @return array
     *
     * @throws NotFoundHttpException when note not exist
     */
    public function getNoteAction($id)
    {
        $note = $this->getNoteManager()->get($id);
        if (false === $note) {
            throw $this->createNotFoundException("Note does not exist.");
        }

        $view = new View($note);
        $group = $this->container->get('security.context')->isGranted('ROLE_API') ? 'restapi' : 'standard';
        $view->getContext()->addGroup('Default');
        $view->getContext()->addGroup($group);

        return $view;
    }

    /**
     * Creates a new note from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Form\NoteType",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned when the form has errors"
     *   }
     * )
     *
     * @Annotations\View(
     *   template = "AppBundle:Note:newNote.html.twig",
     *   statusCode = Response::HTTP_BAD_REQUEST
     * )
     *
     * @param Request $request the request object
     *
     * @return FormTypeInterface[]|View
     */
    public function postNotesAction(Request $request)
    {
        $note = new Note();
        $form = $this->createForm(new NoteType(), $note);

        $form->submit($request);
        if ($form->isValid()) {
            $this->getNoteManager()->set($note);

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
     * @param int $id the note id
     *
     * @return FormTypeInterface
     *
     * @throws NotFoundHttpException when note not exist
     */
    public function editNotesAction($id)
    {
        $note = $this->getNoteManager()->get($id);
        if (false === $note) {
            throw $this->createNotFoundException("Note does not exist.");
        }

        return $this->createForm(new NoteType(), $note);
    }

    /**
     * Update existing note from the submitted data or create a new note at a specific location.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "AppBundle\Form\NoteType",
     *   statusCodes = {
     *     201 = "Returned when a new resource is created",
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors"
     *   }
     * )
     *
     * @Annotations\View(
     *   template="AppBundle:Note:editNote.html.twig",
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
        $note = $this->getNoteManager()->get($id);
        if (false === $note) {
            $note = new Note();
            $note->id = $id;
            $statusCode = Response::HTTP_CREATED;
        } else {
            $statusCode = Response::HTTP_NO_CONTENT;
        }

        $form = $this->createForm(new NoteType(), $note);

        $form->submit($request);
        if ($form->isValid()) {
            $this->getNoteManager()->set($note);

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
     *     204="Returned when successful"
     *   }
     * )
     *
     * @param int $id the note id
     *
     * @return View
     */
    public function deleteNotesAction($id)
    {
        $this->getNoteManager()->remove($id);

        // There is a debate if this should be a 404 or a 204
        // see http://leedavis81.github.io/is-a-http-delete-requests-idempotent/
        return $this->routeRedirectView('get_notes', array(), Response::HTTP_NO_CONTENT);
    }

    /**
     * Removes a note.
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes={
     *     204="Returned when successful"
     *   }
     * )
     *
     * @param int $id the note id
     *
     * @return View
     */
    public function removeNotesAction($id)
    {
        return $this->deleteNotesAction($id);
    }
}
