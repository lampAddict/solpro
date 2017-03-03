<?php

namespace AppBundle\Services;

use Symfony\Component\Validator\Constraints\DateTime;

class export1CDataService
{
    protected $em;
    protected $um;

    public function __construct($entityManager, $userManager)
    {
        $this->em = $entityManager;
        $this->um = $userManager;
    }

    public function exportData($sendNum, $recNum){

        $q = $this->em->getConnection()->prepare('SELECT id, date_exchange FROM exchange WHERE rec_num = '.$sendNum);
        $q->execute();
        $r = $q->fetchAll();
        //if found couple or more records some data may not has been exported properly
        if( count($r) > 1 ){

        }
        //single record - ok
        elseif( count($r) == 1 ){
            $exchange_date   = $r[0]['date_exchange'];
            $exchange_rec_id = $r[0]['id'];
        }
        else{
            //return false;
        }

        echo "Exchange table checked\n";

        $auction_end_lots = $this->em->getRepository('AppBundle:Lot')->findBy(['auctionStatus'=>0]);
        if( !empty($auction_end_lots) ){

            echo "Auction lots processing started\n";

            $current_date = new \DateTime(date('c', time()));

            $routesIds = [];
            $routesPrices = [];
            foreach( $auction_end_lots as $lot ){
                /* @var $lot \AppBundle\Entity\Lot */
                $routesIds[] = $lot->getRouteId();
                $routesPrices[ $lot->getRouteId() ] = $lot->getPrice();
            }

            $user1cIds = [];
            $refCarrierUsers = $this->em->getRepository('AppBundle:RefCarrierUser')->findAll();
            foreach( $refCarrierUsers as $refCarrierUser ){
                /* @var $refCarrierUser \AppBundle\Entity\RefCarrierUser */
                $user1cIds[ $refCarrierUser->getName() ] = $refCarrierUser->getId1C();
            }

            echo "routes data composition\n";

            $routesStartPrices = [];
            $q = $this->em->getConnection()->prepare('SELECT user_id, id1c, trade_cost FROM route WHERE id IN('.implode(',', $routesIds).')');
            $q->execute();
            $routesArr = $q->fetchAll();
            $routes = '<routes>';
            foreach( $routesArr as $route ){
                /* @var $route \AppBundle\Entity\Route */
                $routes .= " <route>
                                <id>".$route->getId1C()."</id>
                                <carrierId>".$user1cIds[ $route->getUserId()->getUsername() ]."</carrierId>
                                <tradeCost>".$routesPrices[ $route->getId() ]."</tradeCost>
                             </route>";

                $routesStartPrices[ $route->getId() ] = $route->getTradeCost();
            }
            $routes .= '</routes>';

            $lot1cStatus = [];
            $refLotStatuses = $this->em->getRepository('AppBundle:RefLotStatus')->findAll();
            foreach( $refLotStatuses as $refLotStatus ){
                /* @var $refLotStatus \AppBundle\Entity\RefLotStatus */
                $lot1cStatus[ $refLotStatus->getId() ] = $refLotStatus->getId1C();
            }

            echo "lots data composition\n";

            $lots = '<lots>';
            foreach( $auction_end_lots as $lot ){
                /* @var $lot \AppBundle\Entity\Lot */

                $q = "SELECT b.value AS bet, b.user_id AS uid FROM bet b WHERE b.lot_id = ".($lot->getId())." ORDER BY b.value ASC LIMIT 1";
                $r = $this->em->getConnection()->prepare($q);
                $r->execute();
                $bet = $r->fetchAll();

                $lotStatusId = 3; //лот нерасторгован
                if( !empty($bet) ){
                    $lotStatusId = 5; //лот расторгован
                }

                $lots .= "<lot><id>".$lot->getId1C()."</id><statusId>".$lot1cStatus[ $lotStatusId ]."</statusId></lot>";
            }
            $lots .= "</lots>";

            $xml = '<?xml version="1.0" encoding="UTF-8"?><messageFromPortal sendNumber="'.($recNum + 1).'" recNumber="'.$sendNum.'" messageCreationTime="'.$current_date->format('c').'">';
            $xml .= $routes;
            $xml .= $lots;
            $xml .= "   </messageFromPortal>";

            echo "xml data composed\n";

            file_put_contents('data/messageFromPortal.xml', $xml);

            echo "xml file created\n";
            
            return true;
        }
        
        return false;
    }
}
