<?php

namespace Acme\DemoBundle\Controller;

use Acme\DemoBundle\Form\NoteType;
use Acme\DemoBundle\Model\Note;
use Acme\DemoBundle\Model\NoteCollection;

use Codag\RestFabricationBundle\Exception\InvalidFormException;
use FOS\RestBundle\Util\Codes;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\RouteRedirectView;

use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormTypeInterface;
use Codag\RestFabricationBundle\Exception\ResourceNotFoundException;

/**
 * Rest controller for notes
 *
 * @package Acme\DemoBundle\Controller
 * @author Gordon Franke <info@nevalon.de>
 */
class NoteController extends FOSRestController
{
    /**
     * return \Acme\DemoBundle\NoteManager
     */
    public function getNoteManager()
    {
        return $this->get('acme.demo.note_manager');
    }

    /**
     * return \Codag\RestFabricationBundle\Form\Handler\CreateFormHandler
     */
    public function getFormHandler()
    {
        return $this->get('acme.demo.form_handler.note');
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
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return array
     */
    public function getNotesAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $offset = $paramFetcher->get('offset');
        $start = null == $offset ? 0 : $offset + 1;
        $limit = $paramFetcher->get('limit');

        $notes = $this->getNoteManager()->fetch($start, $limit);

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
     * @throws ResourceNotFoundException when note not exist
     */
    public function getNoteAction(Request $request, $id)
    {
        $note = $this->getNoteManager()->get($id);
        if (false === $note) {
            throw new ResourceNotFoundException("Note", $id);
        }

        $view = new View($note);
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
     *
     * @throws InvalidFormException when FormHandler receives invalid form
     */
    public function postNotesAction(Request $request)
    {
        try {
            $form = $this->createForm(new NoteType(), new Note());
            $new = $this->getFormHandler()->handle($form, $request);
            return $this->routeRedirectView('get_note', array('id' => $new->id));
        }catch (InvalidFormException $exception) {
            return array(
                'form' => $exception->getForm()
            );
        }
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
     * @throws ResourceNotFoundException when note not exist
     */
    public function editNotesAction(Request $request, $id)
    {
        $note = $this->getNoteManager()->get($id);
        if (false === $note) {
            throw new ResourceNotFoundException("Note", $id);
        }

        $form = $this->createForm(new NoteType(), $note);

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
     * @throws ResourceNotFoundException when note not exist
     */
    public function putNotesAction(Request $request, $id)
    {
        try {
            if (!($object = $this->getNoteManager()->get($id))) {
                $statusCode = Codes::HTTP_CREATED;
                $form = $this->createForm(new NoteType(), new Note(), array('method' => 'POST'));
            } else {
                $statusCode = Codes::HTTP_NO_CONTENT;
                $form = $this->createForm(new NoteType(), $object, array('method' => 'PUT'));
            }
            $object = $this->getFormHandler()->handle($form, $request);

            return $this->routeRedirectView('get_note', array('id' => $object->id), $statusCode);

        } catch (InvalidFormException $exception) {
            return $exception->getForm();
        }
    }

    /**
     * Removes a note.
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes={
     *     204="Returned when successful"
     *     404 = "Returned when the note is not found"
     *   }
     * )
     *
     * @param Request $request the request object
     * @param int     $id      the note id
     *
     * @return RouteRedirectView
     */
    public function deleteNotesAction(Request $request, $id)
    {
        if(!$this->getNoteManager()->remove($id)){
            throw new ResourceNotFoundException("Note", $id);
        }

        // There is a debate if this should be a 404 or a 204
        // see http://leedavis81.github.io/is-a-http-delete-requests-idempotent/
        return $this->routeRedirectView('get_notes', array(), Codes::HTTP_NO_CONTENT);
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
