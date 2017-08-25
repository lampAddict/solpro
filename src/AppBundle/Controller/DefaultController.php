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

        $sql = "SELECT l.*, r.region_from, r.region_to FROM lot l LEFT JOIN route r ON l.id = r.lot_id WHERE l.auction_status = 1";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $lots = $stmt->fetchAll();

        $_lots = [];
        $plannedLotsNum = $activeLotsNum = 0;
        foreach ($lots as $lot){
            if( strtotime($lot['start_date']) <= time() ){
                $k = $lot['region_from'].' - '.$lot['region_to'];
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
        $tz = ['3'=>'Europe/Moscow', '4'=>'Europe/Samara', '5'=>'Asia/Yekaterinburg', '7'=>'Asia/Novosibirsk'];
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
