<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LotController extends Controller
{
    /**
     * @Route("/lotsPrices", name="lotsPrices")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $this->get('memcache.default')->set('someKey', 'someValue', 0, 1*60*60);
        var_dump($this->get('memcache.default')->get('someKey'));die;
        
        $lots = $em
            ->getRepository('AppBundle:Lot')
            ->createQueryBuilder('l')
            ->where('l.startDate <= CURRENT_DATE() AND DATE_DIFF(l.startDate, CURRENT_DATE()) < l.duration/1440 ')
            ->getQuery()
            ->getResult();

        $_lots = [];
        foreach ($lots as $lot){
            $_lots[ $lot->getId() ] = $lot->getPrice();
        }

        return new JsonResponse(['lots'=>$_lots]);
    }
}