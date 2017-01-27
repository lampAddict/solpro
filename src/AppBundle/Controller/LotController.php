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

        $redis = $this->container->get('snc_redis.default');

        $_lots = [];
        //check if lots current prices are stored in redis
        $l_ids = $redis->get('lcp');
        if( $l_ids ){
            //get lot prices from redis
            $l_ids = explode(',',$l_ids);
            foreach( $l_ids as $l_id ){
                $_lots[ $l_id ] = json_decode($redis->get('lcp_'.$l_id));
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

                    $redis->set('lcp_'.$lot['id'], json_encode($_lots[ $lot['id'] ]));
                }
                //store lots ids in redis
                $redis->set('lcp', join(',',array_keys($_lots)));
                //set time expiration for `lcp` key equals to 10 mins
                $redis->expire('lcp', 600);
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
            //delete lot price from redis
            $redis = $this->container->get('snc_redis.default');
            if( $redis->exists('lcp_'.$lot->getId()) ){
                $redis->del('lcp_'.$lot->getId());
            }

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