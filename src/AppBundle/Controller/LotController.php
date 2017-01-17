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
        //$this->get('memcache.default')->flush();

        $_lots = [];
        //check if lots current prices are stored in memcache
        $l_ids = $this->get('memcache.default')->get('lcp');
        if( !$l_ids ){
            //read lot prices from db
            $em = $this->getDoctrine()->getManager();
            $lots = $em
                ->getRepository('AppBundle:Lot')
                ->createQueryBuilder('l')
                ->where('l.startDate <= CURRENT_DATE() AND l.auctionStatus = 1')
                ->getQuery()
                ->getResult();

            if( !empty($lots) ){
                foreach( $lots as $lot ){
                    /* @var $lot \AppBundle\Entity\Lot */
                    $_lots[ $lot->getId() ] = $lot->getPrice();
                    $this->get('memcache.default')->set('lcp_'.$lot->getId(), $lot->getPrice(), 0, 1*60*60);
                }
                $this->get('memcache.default')->set('lcp', join(',',array_keys($_lots)), 0, 1*60*60);
            }
            else{
                $_lots = false;
            }
        }
        else{
            //get lot prices from memcache
            $l_ids = explode(',',$l_ids);
            foreach( $l_ids as $l_id ){
                $_lots[ $l_id ] = $this->get('memcache.default')->get('lcp_'.$l_id);
            }
        }

        return new JsonResponse(['lots'=>$_lots]);
    }

    /**
     * @Route("/lotAuctionEnd", name="lotAuctionEnd")
     */
    public function lotAuctionEndAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        /* @var $lot \AppBundle\Entity\Lot */
        $lot = $em->getRepository('AppBundle:Lot')->findOneBy(['id'=>intval($request->request->get('lot')), 'auctionStatus'=>1]);

        if(     $lot
            &&  time() >= $lot->getStartDate()->getTimestamp() + $lot->getDuration()*60
        ){
            //get lot off the auction
            $lot->setAuctionStatus(0);

            //assign route to winner
            $bet = $em
                ->getRepository('AppBundle:Bet')
                ->createQueryBuilder('b')
                ->where('b.lot_id = '.intval($request->request->get('lot')))
                ->orderBy('b.id', 'DESC')
                ->setMaxResults( 1 )
                ->getQuery()
                ->getResult();

            if( !empty($bet) ){
                /* @var $route \AppBundle\Entity\Route */
                $route = $lot->getRouteId();
                $route->setUserId($bet[0]->getUserId());
                $em->persist($route);
            }

            $em->persist($lot);

            $em->flush();

            return new JsonResponse(['result'=>true]);
        }

        return new JsonResponse(['result'=>false]);
    }
}