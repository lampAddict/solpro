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

        $data_added = false;
        
        $current_date = new \DateTime(date('c', time()));
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<messageFromPortal sendNumber="'.($recNum + 1).'" recNumber="'.$sendNum.'" messageCreationTime="'.$current_date->format('c').'">';

        $auction_end_lots = $this->em->getRepository('AppBundle:Lot')->findBy(['auctionStatus'=>0]);
        if( !empty($auction_end_lots) ){

            echo "Compose lots data\n";

            $lots = '<lots>';
            foreach( $auction_end_lots as $lot ){
                /* @var $lot \AppBundle\Entity\Lot */
                $lots .= '<lot>'
                            .'<id>'.$lot->getId1C().'</id>'
                            .'<statusId>'.$lot->getStatusId1c().'</statusId>'
                        .'</lot>';
            }
            $lots .= '</lots>';

            $xml .= $lots;

            $data_added = true;
        }

        //TODO add select by route status condition to $userRoutesArr findBy
        $userRoutesArr = $this->em->getRepository('AppBundle:Route')->findAll();
        if( !empty($userRoutesArr) ){

            echo "Compose routes data\n";

            //compose routes prices data
            $routesIds = [];
            foreach( $userRoutesArr as $route ){
                /* @var $route \AppBundle\Entity\Route */
                $routesIds[] = $route->getId();
            }

            $routesPrices = [];
            $lotsPrices = $this->em->getRepository('AppBundle:Lot')->findBy(['route_id'=>$routesIds]);
            foreach( $lotsPrices as $lot ){
                /* @var $lot \AppBundle\Entity\Lot */
                $routesPrices[ $lot->getRouteId()->getId() ] = $lot->getPrice();
            }
            
            $user1cIds = [];
            $refCarrierUsers = $this->em->getRepository('AppBundle:RefCarrierUser')->findAll();
            foreach( $refCarrierUsers as $refCarrierUser ){
                /* @var $refCarrierUser \AppBundle\Entity\RefCarrierUser */
                $user1cIds[ $refCarrierUser->getLogin() ] = $refCarrierUser->getId1C();
            }

            $driversIds = [];
            $routes = '<routes>';
            foreach( $userRoutesArr as $route ){
                /* @var $route \AppBundle\Entity\Route */
                $routes .= ' <route>'
                                .'<id>'.$route->getId1C().'</id>'
                                .'<carrierId>'.(is_null($route->getUserId()) ? '' : (isset($user1cIds[ $route->getUserId()->getUsername() ]) ? $user1cIds[ $route->getUserId()->getUsername() ] : '')).'</carrierId>'
                                .'<tradeCost>'.$routesPrices[ $route->getId() ].'</tradeCost>'
                            .'</route>';
                if( !is_null($route->getDriverId()) ){
                    $driversIds[] = $route->getDriverId()->getId();
                }
            }
            $routes .= '</routes>';
            $xml .= $routes;
            $data_added = true;

            if( !empty($driversIds) ){

                echo "Drivers data composition\n";

                $docTypes = [];
                $docTypesArr = $this->em->getRepository('AppBundle:RefPassport')->findAll();
                foreach( $docTypesArr as $docType){
                    /* @var $docType \AppBundle\Entity\RefPassport */
                    $docTypes[ $docType->getId() ] = $docType->getId1C();
                }

                $driversArr = $this->em->getRepository('AppBundle:Driver')->findBy(['id'=>$driversIds]);
                $drivers = '<drivers>';
                foreach( $driversArr as $driver ){
                    /* @var $driver \AppBundle\Entity\Driver */
                    $drivers .= ' <driver>'
                                    .'<docIDType>'.$docTypes[ $driver->getPassportType() ].'</docIDType>'
                                    .'<series>'.$driver->getPassportSeries().'</series>'
                                    .'<number>'.$driver->getPassportNumber().'</number>'
                                    .'<date>'.$driver->getPassportDateIssue().'</date>'
                                    .'<issuedBy>'.$driver->getPassportIssuedBy().'</issuedBy>'
                                .'</driver>';
                }
                $drivers .= '</drivers>';

                $xml .= $drivers;
            }
        }
        
        $xml .= '</messageFromPortal>';
        
        if( $data_added ){
            file_put_contents('data/messageFromPortal.xml', $xml);
            echo "Xml file created\n";
            return true;
        }
        
        return false;
    }
}
