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
            ->where('l.startDate <= CURRENT_DATE() AND l.auctionStatus = 1')
            ->getQuery()
            ->getResult();

        $_lots = [];
        /* @var $lot \AppBundle\Entity\Lot */
        foreach ($lots as $lot){
            $k = $lot->getRouteId()->getRegionFrom().' - '.$lot->getRouteId()->getRegionTo();
            if( !isset($_lots[$k]) ){
                $_lots[ $k ] = 1;
            }
            else{
                $_lots[ $k ]++;
            }
        }
        
        $_stat = [];
        foreach($_lots as $ld=>$ls){
            $_stat[] = ['dir'=>$ld, 'num'=>$ls];
        }
        
        // replace this example code with whatever you need
        return $this->render('base.html.twig', array(
             'lots' => $lots
            ,'stat' => $_stat
        ));
    }
}
