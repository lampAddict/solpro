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
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $_session = $request->getSession();
        //$_session->clear();

        $_lots = [];
        //check if lots current prices are stored in memcache
        $l_ids = $_session->get('lcp');
        if( $l_ids ){
            //get lot prices from session stored in `memcached`
            $l_ids = explode(',',$l_ids);
            foreach( $l_ids as $l_id ){
                $_lots[ $l_id ] = json_decode($_session->get('lcp_'.$l_id));
            }
        }
        else{
            //read lot prices from db
            $em = $this->getDoctrine()->getManager();

            $sql = 'SELECT l.id, l.price AS price, b.bet, b.uid FROM lot l left join (select b1.lot_id as lot_id, min(b1.value) as bet, u.id as uid from bet b1 left join fos_user u on b1.user_id = u.id group by b1.lot_id)b on l.id = b.lot_id WHERE l.auction_status = 1';//AND l.start_date <= NOW()
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $lots = $stmt->fetchAll();
            
            if( !empty($lots) ){
                foreach( $lots as $lot ){
                    $_own = '';
                    if( $lot['uid'] == $this->getUser()->getId() )
                        $_own = $lot['uid'];

                    $_lots[ $lot['id'] ] = ['price'=>$lot['price'], 'owner'=>$_own];

                    //$this->get('memcache.default')->set('lcp_'.$lot->getId(), $lot->getPrice(), 0, 1*60*60);
                    $_session->set('lcp_'.$lot['id'], json_encode($_lots[ $lot['id'] ]));
                }
                //$this->get('memcache.default')->set('lcp', join(',',array_keys($_lots)), 0, 1*60*60);
                $_session->set('lcp', join(',',array_keys($_lots)));
            }
            else{
                $_lots = false;
            }
        }

        return new JsonResponse(['lots'=>$_lots]);
    }

    /**
     * @Route("/lotAuctionEnd", name="lotAuctionEnd")
     */
    public function lotAuctionEndAction(Request $request)
    {
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }
        
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