<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Transport;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Transport controller.
 *
 * @Route("transport")
 */
class TransportController extends Controller
{
    /**
     * Composer filter condition for auction page based on user preferences
     *
     * @param array $_filters array which contains json encoded user preferences
     * @return string $where composed condition
     */
    private function makeFilterCondition( $_filters ){
        $where = 't.user_id = '.$this->getUser()->getId();
        $filters = (array)$_filters;
        if( !empty($filters) ){

            if(    $_filters->status_active
                && $_filters->status_active == 1
            ){
                $where .= ' AND t.status = 1';
            }

            if(    $_filters->status_inactive
                && $_filters->status_inactive == 1
            ){
                if( $where != 't.user_id = '.$this->getUser()->getId() ){
                    $where = 't.user_id = '.$this->getUser()->getId();
                }
                else{
                    $where .= ' AND t.status = 0';
                }
            }

            if(    $_filters->vehicle_types
                && is_array($_filters->vehicle_types)
            ){
                $where .= ' AND t.type IN (\''.join('\',\'', $_filters->vehicle_types).'\')';
            }
        }

        return $where;
    }

    /**
     * Get stored vehicle types
     *
     * @return array
     */
    private function getPossibleVehicleTypes(){
        $em = $this->getDoctrine()->getManager();
        return $em->getRepository('AppBundle:RefVehicleType')->findAll();
    }

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
        //get user's vehicle filter preferences
        $_filters = [];

        $filters = $em
            ->getRepository('AppBundle:Filter')
            ->createQueryBuilder('f')
            ->where('f.uid = '.$this->getUser()->getId())
            ->andWhere('f.type = 3')//vehicle filter type
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if( !empty($filters) ){
            $_filters = json_decode($filters[0]->getParams());
        }

        $where = $this->makeFilterCondition( $_filters );

        $sql = 'SELECT t.*, rvt.name, rvct.name as pname FROM transport t LEFT JOIN refvehicletype rvt ON rvt.id = t.type LEFT JOIN refvehiclecarryingtype rvct ON rvct.id = t.payload WHERE '.$where.'ORDER BY t.id DESC';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $transports = $stmt->fetchAll();
        
        return $this->render('transport/index.html.twig', array(
             'transports' => $transports
            ,'filters' => $_filters
            ,'vtypes' => $this->getPossibleVehicleTypes()
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
            $transport->setUpdatedAt(  new \DateTime(date('c', time()))  );
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
        $this->doChecks($transport);

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
        $this->doChecks($transport);

        $editForm = $this->createForm('AppBundle\Form\TransportType', $transport);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            
            $transport->setUpdatedAt( new \DateTime(date('c', time())) );
            
            $this->getDoctrine()->getManager()->flush();

            //return $this->redirectToRoute('transport_edit', array('id' => $transport->getId()));
            return $this->redirectToRoute('transport');
        }

        return $this->render('transport/edit.html.twig', array(
            'transport' => $transport,
            'edit_form' => $editForm->createView()
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
        $this->doChecks($transport);

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
        $this->doChecks($transport);

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
     * @param Transport $transport The transport entity
     *
     * @throws AccessDeniedException
     */
    private function checkUserOwner(Transport $transport){
        if( $transport->getUserId()->getId() != $this->getUser()->getId() ){
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * Do various check routines before actual action
     * - User authentication
     * - Transport entity owner
     *
     * @param Transport $transport The transport entity
     */
    private function doChecks($transport){
        $this->checkUserAuthentication();
        $this->checkUserOwner($transport);
    }

    /**
     * Set users drivers filter
     *
     * @Route("/setFilter", name="set_filter")
     * @Method("POST")
     */
    public function setFilterAction(Request $request){
        return $this->forward('AppBundle:Auction:setFilter');
    }

    /**
     * Set users drivers filter
     *
     * @Route("/unsetFilter", name="unset_filter")
     * @Method("POST")
     */
    public function unsetFilterAction(Request $request){
        return $this->forward('AppBundle:Auction:unsetFilter');
    }
}
