<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Admin controller.
 *
 * @Route("admin")
 */
class AdminController extends Controller
{
    /**
     * Lists all active auctions
     *
     * @Route("/", name="admin")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        //get lots data
        $sql = 'SELECT l.*, u.username as user_name, r.code as code, r.region_from, r.region_to FROM lot l LEFT JOIN bet b ON b.lot_id = l.id LEFT JOIN fos_user u ON b.user_id = u.id LEFT JOIN route r ON r.lot_id = l.id WHERE l.price >= IFNULL(b.value, 0) GROUP BY l.id ORDER BY l.start_date DESC, l.auction_status DESC';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $_lots = $stmt->fetchAll();

        //get routes ids
        $routesIds = [];
        if( !empty($_lots) ){
            foreach( $_lots as $indx=>$lot ){
                $_lots[ $indx ]['start_date']  = new \DateTime( $lot['start_date'] );
            }
        }
        else{
            $_lots = [];
        }

        return $this->render('admin/adminPage.html.twig', array(
             'lots' => $_lots
            ,'tz' => ($this->getUser()->getTimezone() != '' ? $this->getUser()->getTimezone() : 'UTC')
        ));
    }

    /**
     * Lists all site users
     *
     * @Route("/users", name="users")
     * @Method("GET")
     */
    public function showUsersAction(){

        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        $users = $em
            ->getRepository('AppBundle:User')
            ->createQueryBuilder('u')
            ->getQuery()
            ->getResult();

        $_users = [];
        foreach ($users as $user){
            /* @var $user \AppBundle\Entity\User */
            $_users[] = [
                             'id'=>$user->getId()
                            ,'login'=>$user->getEmail()
                            ,'name'=>$user->getUsername()
                            ,'roles'=>$user->getRoles()
                    ];
        }


        return $this->render('admin/adminUsersPage.html.twig', array(
            'users' => $_users
        ));
    }

    /**
     * Finds and displays all lot bids.
     *
     * @Route("/lot/{id}", name="lot_bids_show")
     * @Method("GET")
     */
    public function showLotBidsAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $_bids = [];
        //get lots data
        $sql = 'SELECT b.value, b.created_at, u.username as user_name FROM bet b LEFT JOIN fos_user u ON b.user_id = u.id WHERE b.lot_id="'.intval($id).'" ORDER BY b.created_at DESC';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $_bids = $stmt->fetchAll();

        if( !empty($_bids) ){
            foreach( $_bids as $indx=>$bid ){
                $_bids[ $indx ]['created_at']  = new \DateTime( $bid['created_at'] );
            }
        }

        return $this->render('admin/adminLotPage.html.twig', array(
             'bids'=>$_bids
            ,'tz' => ($this->getUser()->getTimezone() != '' ? $this->getUser()->getTimezone() : 'UTC')
        ));
    }
}