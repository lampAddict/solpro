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
        $sql = 'SELECT l.*, u.username as user_name FROM lot l LEFT JOIN bet b ON b.lot_id = l.id LEFT JOIN fos_user u ON b.user_id = u.id WHERE l.auction_status = 1 AND l.price >= IFNULL(b.value, 0) GROUP BY l.id ORDER BY l.start_date DESC';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $_lots = $stmt->fetchAll();

        //get routes ids
        $routesIds = [];
        if( !empty($_lots) ){
            foreach( $_lots as $indx=>$lot ){
                $_lots[ $indx ]['start_date']  = new \DateTime( $lot['start_date'] );
                $routesIds[] = $lot['id'];
            }
        }
        else{
            $_lots = [];
        }

        $_routes = [];

        //get routes data
        if( !empty($routesIds) ){
            $sql = 'SELECT * FROM route WHERE lot_id IN ('.join(',', $routesIds).')';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $routesData = $stmt->fetchAll();
            foreach( $routesData as $routeData ){
                $routeData['load_date'] = new \DateTime( $routeData['load_date'] );
                $_routes[ $routeData['id'] ] = $routeData;
            }
        }

        return $this->render('adminPage.html.twig', array(
             'lots' => $_lots
            ,'routes' => $_routes
            ,'tz' => ($this->getUser()->getTimezone() != '' ? $this->getUser()->getTimezone() : 'UTC')
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

        return $this->render('adminLotPage.html.twig', array(
             'bids'=>$_bids
            ,'tz' => ($this->getUser()->getTimezone() != '' ? $this->getUser()->getTimezone() : 'UTC')
        ));
    }
}