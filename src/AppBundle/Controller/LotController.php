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
            $l_ids = explode(',', $l_ids);
            foreach( $l_ids as $l_id ){
                $_lots[ $l_id ] = json_decode($redis->get('lcp_'.$l_id));
            }
        }
        else{
            //read lot prices from db
            $em = $this->getDoctrine()->getManager();
            //TODO simplify query
            $sql = 'SELECT l.id, l.price AS price, b.bet, b.uid FROM lot l LEFT JOIN (SELECT b1.lot_id AS lot_id, min(b1.value) AS bet, b1.user_id AS uid FROM bet b1 GROUP BY b1.user_id, b1.lot_id)b ON l.id = b.lot_id WHERE l.auction_status = 1';//AND l.start_date <= NOW()
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $lots = $stmt->fetchAll();
            
            if( !empty($lots) ){
                foreach( $lots as $lot ){

                    if( !isset($_lots[ $lot['id'] ]) ){
                        $_lots[ $lot['id'] ] = [
                             'price'=>$lot['price']
                            ,'owner'=>$lot['uid']
                            ,'history'=>[]
                            ,'bet'=>$lot['bet']
                        ];
                    }
                    else{
                        if( $_lots[ $lot['id'] ]['bet'] > $lot['bet'] ){
                            $_lots[ $lot['id'] ]['owner'] = $lot['uid'];
                        }
                    }

                    if(    !is_null($lot['uid'])
                        && !in_array($lot['uid'], $_lots[ $lot['id'] ]['history'])
                    ){
                        array_push($_lots[ $lot['id'] ]['history'], $lot['uid']);
                    }
                }

                foreach( $_lots as $lotId=>$lData ){
                    unset($lData['bet']);
                    $redis->set('lcp_'.$lotId, json_encode($lData));
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
        
        //if there is no lot found then auction ended up successfully (probably)
        if( !$lot )return new JsonResponse(['result'=>true]);

        //check auction end time
        if( time() >= ($lot->getStartDate()->getTimestamp() + $lot->getDuration()*60) ){
            //delete lot price from redis
            $redis = $this->container->get('snc_redis.default');

            if( $redis->exists('lcp_'.$lot->getId()) ){
                $redis->del('lcp_'.$lot->getId());

                //delete lot id from redis
                $l_ids = $redis->get('lcp');
                if( $l_ids ){
                    $l_ids = explode(',', $l_ids);
                    foreach( $l_ids as $indx=>$l_id ){
                        if( $l_id == $lot->getId() ){
                            unset($l_ids[$indx]);
                            break;
                        }
                    }
                    $redis->set('lcp', join(',',$l_ids));
                    $redis->expire('lcp', 600);
                }
            }

            //get lot off the auction
            $lot->setAuctionStatus(0);

            //get bets history and current lot owner
            $sql = 'SELECT b.value AS bet, b.user_id AS uid FROM bet b WHERE b.lot_id = '.intval($request->request->get('lot')).' ORDER BY b.value ASC LIMIT 1';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $bet = $stmt->fetchAll();

            //assign route to winner
            if( !empty($bet) ){
                /* @var $route \AppBundle\Entity\Route */
                $route = $lot->getRouteId();
                $route->setUserId($em->getRepository('AppBundle:User')->find($bet[0]['uid']));
                $em->persist($route);
            }

            $em->persist($lot);

            $em->flush();

            return new JsonResponse(['result'=>true]);
        }

        return new JsonResponse(['result'=>false]);
    }
}