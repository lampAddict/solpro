<?php

namespace AppBundle\Services;

use AppBundle\Controller;

class closeAuctionService
{
    protected $em;
    protected $um;
    protected $redis;

    public function __construct($entityManager, $userManager, $redisManager)
    {
        $this->em    = $entityManager;
        $this->um    = $userManager;
        $this->redis = $redisManager;
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

        //lot has been traded unsuccessfully
        $lot->setStatusId1c('a9649dc5-266e-4084-8498-e89c351533ea');

        //get bets history and current lot owner
        $sql = "SELECT b.value AS bet, b.user_id AS uid FROM bet b WHERE b.lot_id = $lid ORDER BY b.value ASC LIMIT 1";
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
        $bet = $stmt->fetchAll();

        //assign route to winner
        if( !empty($bet) ){
            /* @var $route \AppBundle\Entity\Route */
            $route = $this->em->getRepository('AppBundle:Route')->findBy(['lot_id'=>$lid]);
            $route = $route[0];
            $route->setUserId($this->em->getRepository('AppBundle:User')->find($bet[0]['uid']));
            $route->setUpdatedAt( new \DateTime(date('c', time())) );
            $this->em->persist($route);

            //lot has been traded successfully
            $lot->setStatusId1c('c2399918-8f2f-4a4f-bb0b-170a4079472a');
        }

        $lot->setUpdatedAt( new \DateTime(date('c', time())) );

        $this->em->flush();

        $this->redis->set('lae_'.$lid, 1);
        $this->redis->expire('lae_'.$lid, 120);

        return true;
    }
}