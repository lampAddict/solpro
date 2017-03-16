<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

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


    /**
     * @Route("/timezone", name="timezone")
     */
    public function timezoneAction(Request $request){
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        
        $offset = intval($request->request->get('offset'));
        $tz = ['3'=>'Europe/Moscow', '4'=>'Europe/Samara'];
        $tzValue = isset($tz[$offset]) ? $tz[$offset] : 'UTC';
        if( $tzValue != $user->getTimezone() ){
            $user->setTimezone( $tzValue );

            $em = $this->getDoctrine()->getManager();

            $em->persist($user);
            $em->flush();

            return new JsonResponse(['result'=>true]);
        }

        return new JsonResponse(['result'=>false]);
    }
}
