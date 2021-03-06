<?php

namespace AppBundle\Services;

use AppBundle\Controller;
use AppBundle\Entity\RefLotStatus;

class closeAuctionService
{
    protected $em;
    protected $um;
    protected $redis;
    protected $rlss;

    public function __construct($entityManager, $userManager, $redisManager, $refLotStatusService)
    {
        $this->em    = $entityManager;
        $this->um    = $userManager;
        $this->redis = $redisManager;
        $this->rlss  = $refLotStatusService;
    }

    public function closeAuctionService(){

        //check if lots current prices are stored in redis
        $lids = $this->redis->get('lcp');

        if( !$lids ){
            $lids = $this->checkRunningAuctions();
        }

        if( $lids !== false ){
            //get lots end time from redis
            $lids = explode(',', $lids);
            foreach( $lids as $lid ){
                //check if auction end time is reached
                if( time() >= $this->redis->get('laet_'.$lid) ){

                    /* @var $lot \AppBundle\Entity\Lot */
                    $lot = $this->em->getRepository('AppBundle:Lot')->findOneBy(['id'=>$lid, 'auctionStatus'=>1]);
                    if( !$lot )return false;

                    if( time() >= ($lot->getStartDate()->getTimestamp() + $lot->getDuration()*60) ){
                        //do close auction routine
                        if( $this->closeAuction($lot) ){
                            //delete redis lot auction end time key
                            $this->redis->del('laet_'.$lid);
                        }
                    }
                }
            }
        }
        else{
            return false;
        }

        return true;
    }

    /**
     * Reinitiates redis value for lcp key
     *
     * @return bool|string
     */
    protected function checkRunningAuctions(){
        $lots = $this->em->getRepository('AppBundle:Lot')->findBy(['auctionStatus'=>1]);
        /* @var $lot \AppBundle\Entity\Lot */
        $ids = '';
        foreach ($lots as $lot){
            if( !is_null($this->redis->get('lcp_'.$lot->getId())) ){
                $ids .= $lot->getId().',';
            }
        }

        $ids = rtrim($ids,',');
        if( $ids != '' ){
            $this->redis->set('lcp', $ids);
            $this->redis->expire('lcp', 600);

            return $ids;
        }

        return false;
    }

    /**
     * Close auction routine
     *
     * @param $lot \AppBundle\Entity\Lot
     * @return boolean true if all ran smoothly
     */
    public function closeAuction($lot){
        //delete lot price from redis

        /* @var $lot \AppBundle\Entity\Lot */
        $lid = $lot->getId();

        //lcp - lot current price
        if( $this->redis->exists('lcp_'.$lid) ){
            $this->redis->del('lcp_'.$lid);

            //delete lot id from redis
            $l_ids = $this->redis->get('lcp');
            if( $l_ids ){
                $l_ids = explode(',', $l_ids);
                foreach( $l_ids as $indx => $l_id ){
                    if( $l_id == $lid ){
                        unset( $l_ids[$indx] );
                        break;
                    }
                }
                $this->redis->set('lcp', join(',',$l_ids));
                $this->redis->expire('lcp', 600);
            }
        }

        //get lot off the auction
        $lot->setAuctionStatus(0);

        //get lot statuses
        $routeStatusByPid = $this->rlss->getLotStatuses();

        //lot has been traded unsuccessfully
        $lot->setStatusId1c($routeStatusByPid[ RefLotStatus::AUCTION_FAILED ]);

        /* @var $route \AppBundle\Entity\Route */
        $route = $this->em->getRepository('AppBundle:Route')->findBy(['id'=>$lot->getRouteId()]);
        $route = $route[0];

        //get bets history and current lot owner
        $sql = "SELECT b.value AS bet, b.user_id AS uid FROM bet b WHERE b.lot_id = $lid ORDER BY b.value ASC LIMIT 1";
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
        $bet = $stmt->fetchAll();

        //assign route to winner
        if( !empty($bet) ){
            /* @var $user \AppBundle\Entity\User */
            $user = $this->em->getRepository('AppBundle:User')->find($bet[0]['uid']);
            $route->setUserId($user);
            $route->setCarrier($user->getCarrierId1C());//1C carrier id

            $route->setUpdatedAt( new \DateTime(date('c', time())) );
            $this->em->persist($route);

            //lot has been traded successfully
            $lot->setStatusId1c($routeStatusByPid[ RefLotStatus::AUCTION_SUCCEED ]);
        }
        else{
            //$this->em()->remove($route);
        }

        $lot->setUpdatedAt( new \DateTime(date('c', time())) );

        $this->em->flush();

        $this->redis->set('lae_'.$lid, 1);
        $this->redis->expire('lae_'.$lid, 120);

        return true;
    }
}