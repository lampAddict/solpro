<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Driver;
use AppBundle\Entity\Filter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Driver controller.
 *
 * @Route("driver")
 */
class DriverController extends Controller
{
    /**
     * Composer filter condition for auction page based on user preferences
     *
     * @param array $_filters array which contains json encoded user preferences
     * @return string $where composed condition
     */
    private function makeFilterCondition( $_filters ){
        $where = 'd.user_id = '.$this->getUser()->getId();
        $filters = (array)$_filters;
        if( !empty($filters) ){

            if(    $_filters->status_active
                && $_filters->status_active == 1
            ){
                $where .= ' AND d.status = 1';
            }

            if(    $_filters->status_inactive
                && $_filters->status_inactive == 1
            ){
                if( $where != 'd.user_id = '.$this->getUser()->getId() ){
                    $where = 'd.user_id = '.$this->getUser()->getId();
                }
                else{
                    $where .= ' AND d.status = 0';
                }
            }
        }

        return $where;
    }

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

        //get user's drivers filter preferences
        $_filters = [];

        $filters = $em
            ->getRepository('AppBundle:Filter')
            ->createQueryBuilder('f')
            ->where('f.uid = '.$this->getUser()->getId())
            ->andWhere('f.type = 2')//driver filter type
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if( !empty($filters) ){
            $_filters = json_decode($filters[0]->getParams());
        }

        $where = $this->makeFilterCondition( $_filters );

        $drivers = $em
                    ->getRepository('AppBundle:Driver')
                    ->createQueryBuilder('d')
                    ->where($where)
                    ->getQuery()
                    ->getResult()
        ;

        return $this->render('driver/index.html.twig', array(
            'drivers' => $drivers,
            'filters' => $_filters
        ));
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
    
    /**
     * Creates a new driver entity.
     *
     * @Route("/add", name="driver_add")
     * @Method({"GET", "POST"})
     */
    public function addAction(Request $request)
    {
        $this->checkUserAuthentication();

        /* @var $driver \AppBundle\Entity\Driver */
        $driver = new Driver();
        $form = $this->createForm('AppBundle\Form\DriverType', $driver);
        $form->handleRequest($request);

        if( $form->isSubmitted() && $form->isValid() ){
            $em = $this->getDoctrine()->getManager();

            $driver->setUserId($this->getUser());
            $driver->setUpdatedAt( new \DateTime(date('c', time())) );

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
            $driver->setUpdatedAt( new \DateTime(date('c', time())) );
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

        if( $form->isSubmitted() && $form->isValid() ){
            $em = $this->getDoctrine()->getManager();

            $sql = 'SELECT r.name FROM route r WHERE driver_id = "'.$driver->getId().'"';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $r = $stmt->fetchAll();
            if( !empty($r) ){
                $rIds = '';
                foreach($r as $_r){
                    $rIds .= '"'.$_r['name'].'", ';
                }
                $rIds = rtrim($rIds,', ');
                return $this->render('errorPage.html.twig', array(
                    'msg' => 'Водитель привязан к рейс'.(count($r)>1?'ам':'у').' '.$rIds,
                    'redirectTo' => 'driver',
                    'redirectToCaption' => 'Вернуться к списку водителей'
                ));
            }

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
