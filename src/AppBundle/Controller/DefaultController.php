<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="mainpage")
     */
    public function indexAction(Request $request)
    {
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        $lots = $em
            ->getRepository('AppBundle:Lot')
            ->createQueryBuilder('l')
            ->leftJoin('l.routeId', 'r')
            ->where('l.auctionStatus = 1')
            ->getQuery()
            ->getResult();

        $_lots = [];
        $plannedLotsNum = $activeLotsNum = 0;
        /* @var $lot \AppBundle\Entity\Lot */
        foreach ($lots as $lot){
            if( $lot->getStartDate()->getTimestamp() <= time() ){
                $k = $lot->getRouteId()->getRegionFrom().' - '.$lot->getRouteId()->getRegionTo();
                if( !isset($_lots[$k]) ){
                    $_lots[ $k ] = 1;
                }
                else{
                    $_lots[ $k ]++;
                }

                $activeLotsNum++;
            }
            else{
                $plannedLotsNum++;
            }
        }
        
        $_stat = [];
        foreach($_lots as $ld=>$ls){
            $_stat[] = ['dir'=>$ld, 'num'=>$ls];
        }

        $uid = $this->getUser()->getId();
        //get routes without assigned driver 
        $sql = "SELECT r.id, r.driver_id FROM route r WHERE r.user_id = $uid";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $rn = $stmt->fetchAll();

        $rnd = 0;
        $rnc = 0;
        foreach($rn as $r){
            $rnc++;
            if( $r['driver_id'] == null ){
                $rnd++;
            }
        }

        return $this->render('base.html.twig', array(
             'stat' => $_stat
            ,'activeLotsNum'=>$activeLotsNum
            ,'plannedLotsNum'=>$plannedLotsNum
            ,'routes_no_driver'=>$rnd
            ,'routes_sum'=>$rnc
        ));
    }
}
