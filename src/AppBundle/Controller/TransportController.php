<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Transport;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Transport controller.
 *
 * @Route("transport")
 */
class TransportController extends Controller
{
    /**
     * Lists all transport entities.
     *
     * @Route("/", name="transport")
     * @Method("GET")
     */
    public function indexAction()
    {
        $this->checkUserAuthentication();

        $em = $this->getDoctrine()->getManager();

        $transports = $em->getRepository('AppBundle:Transport')->findBy(['user_id'=>$this->getUser()->getId()],['id'=>'DESC']);//findAll();
        
        return $this->render('transport/index.html.twig', array(
            'transports' => $transports,
        ));
    }

    /**
     * Creates a new transport entity.
     *
     * @Route("/add", name="transport_add")
     * @Method({"GET", "POST"})
     */
    public function addAction(Request $request)
    {
        $this->checkUserAuthentication();

        $transport = new Transport();
        $form = $this->createForm('AppBundle\Form\TransportType', $transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $transport->setUserId($this->getUser());
            $em->persist($transport);
            $em->flush($transport);

            return $this->redirectToRoute('transport');
            //return $this->redirectToRoute('transport_show', array('id' => $transport->getId()));
        }

        return $this->render('transport/add.html.twig', array(
            'transport' => $transport,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a transport entity.
     *
     * @Route("/{id}", name="transport_show")
     * @Method("GET")
     */
    public function showAction(Transport $transport)
    {
        $this->checkUserAuthentication();
        $this->checkUserOwner($transport);

        $deleteForm = $this->createDeleteForm($transport);

        return $this->render('transport/show.html.twig', array(
            'transport' => $transport,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing transport entity.
     *
     * @Route("/{id}/edit", name="transport_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Transport $transport)
    {
        $this->checkUserAuthentication();
        $this->checkUserOwner($transport);

        $deleteForm = $this->createDeleteForm($transport);
        $editForm = $this->createForm('AppBundle\Form\TransportType', $transport);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            //return $this->redirectToRoute('transport_edit', array('id' => $transport->getId()));
            return $this->redirectToRoute('transport');
        }

        return $this->render('transport/edit.html.twig', array(
            'transport' => $transport,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Show delete confirmation dialog
     *
     * @Route("/{id}/confirmDelete", name="transport_confirmDelete")
     * @Method("GET")
     */
    public function confirmDeleteAction(Request $request, Transport $transport)
    {
        $this->checkUserAuthentication();
        $this->checkUserOwner($transport);

        $deleteForm = $this->createDeleteForm($transport);

        return $this->render('transport/confirmDelete.html.twig', array(
            'transport' => $transport,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a transport entity.
     *
     * @Route("/{id}/delete", name="transport_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Transport $transport)
    {
        $this->checkUserAuthentication();
        $this->checkUserOwner($transport);

        $form = $this->createDeleteForm($transport);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($transport);
            $em->flush($transport);
        }

        return $this->redirectToRoute('transport');
    }

    /**
     * Check if user logged in
     *
     * @throws AccessDeniedException
     */
    private function checkUserAuthentication(){
        if( !$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY') ){
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * Creates a form to delete a transport entity.
     *
     * @param Transport $transport The transport entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Transport $transport)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('transport_delete', array('id' => $transport->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    private function checkUserOwner(Transport $transport){
        if( $transport->getUserId()->getId() != $this->getUser()->getId() ){
            throw $this->createAccessDeniedException();
        }
    }
}
