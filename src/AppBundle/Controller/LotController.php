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
            $sql = "SELECT l.id, l.price AS price, b.bet, b.uid FROM lot l LEFT JOIN (SELECT b1.lot_id AS lot_id, min(b1.value) AS bet, b1.user_id AS uid FROM bet b1 GROUP BY b1.user_id, b1.lot_id)b ON l.id = b.lot_id LEFT JOIN route r ON r.id = l.route_id WHERE l.auction_status = 1 AND r.carrier = ''";//AND l.start_date <= NOW()
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

        $redis = $this->container->get('snc_redis.default');
        $lid = intval($request->request->get('lot'));
        //lae - lot auction end
        if( $redis->exists('lae_'.$lid) ){
            return new JsonResponse(['result'=>true]);
        }

        $em = $this->getDoctrine()->getManager();

        /* @var $lot \AppBundle\Entity\Lot */
        $lot = $em->getRepository('AppBundle:Lot')->findOneBy(['id'=>$lid, 'auctionStatus'=>1]);
        
        //if there is no lot found then auction ended up successfully (probably)
        if( !$lot ){
            $redis->set('lae_'.$lid, 1);
            $redis->expire('lae_'.$lid, 120);
            return new JsonResponse(['result'=>true]);
        }

        //check auction end time
        if( time() >= ($lot->getStartDate()->getTimestamp() + $lot->getDuration()*60) ){

            $closeAuctionService = $this->container->get('app.closeauction');
            if( $closeAuctionService->closeAuctionService() )
                return new JsonResponse(['result'=>true]);
        }

        return new JsonResponse(['result'=>false]);
    }

    /**
     * @Route("/lotsTimers", name="lotsTimers")
     */
    public function lotsTimersAction(Request $request)
    {
        $lotsTimers = [];

        $redis = $this->container->get('snc_redis.default');
        if(
               $redis
            && $redis->exists('lcp')
            && $redis->get('lcp') != ""
        ){

            $em = $this->getDoctrine()->getManager();
            $sql = "SELECT l.id, l.duration FROM lot l WHERE l.id IN( ".$redis->get('lcp')." )";
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $lots = $stmt->fetchAll();

            foreach( $lots as $lot ){
                $lotsTimers[ $lot['id'] ] = $lot['duration'] * 60;
            }
        }

        return new JsonResponse(['lotsTimers'=>$lotsTimers]);
    }
}