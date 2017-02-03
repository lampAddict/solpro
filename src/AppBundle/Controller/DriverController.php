<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Driver;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Driver controller.
 *
 * @Route("driver")
 */
class DriverController extends Controller
{
    /**
     * Lists all driver entities.
     *
     * @Route("/", name="driver")
     * @Method("GET")
     */
    public function indexAction()
    {
        $this->checkUserAuthentication();

        $em = $this->getDoctrine()->getManager();

        $drivers = $em
                    ->getRepository('AppBundle:Driver')
                    ->createQueryBuilder('d')
                    ->where('d.user_id = '.$this->getUser()->getId())
                    ->getQuery()
                    ->getResult()
        ;

        return $this->render('driver/index.html.twig', array(
            'drivers' => $drivers,
        ));
    }

    /**
     * Creates a new driver entity.
     *
     * @Route("/add", name="driver_add")
     * @Method({"GET", "POST"})
     */
    public function addAction(Request $request)
    {
        $this->checkUserAuthentication();

        $driver = new Driver();
        $form = $this->createForm('AppBundle\Form\DriverType', $driver);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $driver->setUserId($this->getUser());

            $em->persist($driver);

            $em->flush();

            return $this->redirectToRoute('driver', array('id' => $driver->getId()));
        }

        return $this->render('driver/add.html.twig', array(
            'driver' => $driver,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a driver entity.
     *
     * @Route("/{id}", name="driver_show")
     * @Method("GET")
     */
    public function showAction(Driver $driver)
    {
        $this->doChecks($driver);

        $deleteForm = $this->createDeleteForm($driver);

        return $this->render('driver/show.html.twig', array(
            'driver' => $driver,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing driver entity.
     *
     * @Route("/{id}/edit", name="driver_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Driver $driver)
    {
        $this->doChecks($driver);

        $editForm = $this->createForm('AppBundle\Form\DriverType', $driver);

        $editForm->handleRequest($request);
        if( $editForm->isSubmitted() && $editForm->isValid() ){
            $this->getDoctrine()->getManager()->flush();
            return $this->redirectToRoute('driver');
        }

        return $this->render('driver/edit.html.twig', array(
            'driver' => $driver,
            'edit_form' => $editForm->createView()
        ));
    }

    /**
     * Deletes a driver entity.
     *
     * @Route("/{id}", name="driver_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Driver $driver)
    {
        $this->doChecks($driver);

        $form = $this->createDeleteForm($driver);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($driver);
            $em->flush($driver);
        }

        return $this->redirectToRoute('driver');
    }

    /**
     * Show delete confirmation dialog
     *
     * @Route("/{id}/confirmDelete", name="driver_confirmDelete")
     * @Method("GET")
     */
    public function confirmDeleteAction(Request $request, Driver $driver)
    {
        $this->doChecks($driver);

        $deleteForm = $this->createDeleteForm($driver);

        return $this->render('driver/confirmDelete.html.twig', array(
            'driver' => $driver,
            'delete_form' => $deleteForm->createView(),
        ));
    }
    
    /**
     * Creates a form to delete a driver entity.
     *
     * @param Driver $driver The driver entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Driver $driver)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('driver_delete', array('id' => $driver->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Do various check routines before actual action
     * - User authentication
     * - Transport entity owner
     *
     * @param Driver $driver The driver entity
     */
    private function doChecks($driver){
        $this->checkUserAuthentication();
        $this->checkUserOwner($driver);
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
     * Checks if entity belongs to current user
     *
     * @param Driver $driver The driver entity
     *
     * @throws AccessDeniedException
     */
    private function checkUserOwner(Driver $driver){
        if( $driver->getUserId()->getId() != $this->getUser()->getId() ){
            throw $this->createAccessDeniedException();
        }
    }
}
