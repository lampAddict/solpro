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
        
        $lastDateExchangeTime = date('c', time());

        $prevDateExchangeTime = 0;
        if( $recNum > 0 ){
            $q = $this->em->getConnection()->prepare('SELECT id, date_exchange FROM exchange WHERE send_num = '.($recNum - 1).' ORDER BY id ASC LIMIT 1');
            $q->execute();
            $r = $q->fetchAll();
            if( empty($r) ){
                $prevDateExchangeTime = new \DateTime(date('c', time() - 365*24*60*60));
                $prevDateExchangeTime = $prevDateExchangeTime->format('c');
            }
            else{
                $prevDateExchangeTime = $r[0]['date_exchange'];
            }
        }
        
        echo "Exchange table checked\n";

        $data_added = false;
        
        $current_date = new \DateTime(date('c', time()));
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<messageFromPortal sendNumber="'.($recNum + 1).'" recNumber="'.$sendNum.'" messageCreationTime="'.$current_date->format('c').'">';

        //lot's data
        $q = $this->em->getConnection()->prepare("SELECT id1c, status_id1c FROM lot WHERE updated_at BETWEEN '".$prevDateExchangeTime."' AND '".$lastDateExchangeTime."'");
        $q->execute();
        $lotsArr = $q->fetchAll();
        if( !empty($lotsArr) ){

            echo "Compose lots data\n";

            $lots = '<lots>';
            foreach( $lotsArr as $lot ){
                $lots .= '<lot>'
                            .'<id>'.$lot['id1c'].'</id>'
                            .'<statusId>'.$lot['status_id1c'].'</statusId>'
                        .'</lot>';
            }
            $lots .= '</lots>';

            $xml .= $lots;

            $data_added = true;
        }

        //routes's data
        $q = $this->em->getConnection()->prepare("SELECT id, id1c, user_id, driver_id, vehicle_id FROM route WHERE updated_at BETWEEN '".$prevDateExchangeTime."' AND '".$lastDateExchangeTime."'");
        $q->execute();
        $routesArr = $q->fetchAll();
        if( !empty($routesArr) ){

            echo "Compose routes data\n";

            $routesIds = [];
            $usersIds = [];
            foreach( $routesArr as $route ){

                $routesIds[] = $route['id'];

                if( !is_null($route['user_id']) )
                    $usersIds[] = $route['user_id'];
            }

            //compose routes prices data
            $routesPrices = [];
            $lotsPrices = $this->em->getRepository('AppBundle:Lot')->findBy(['routeId'=>$routesIds]);
            foreach( $lotsPrices as $lot ){
                /* @var $lot \AppBundle\Entity\Lot */
                $routesPrices[ $lot->getRouteId()->getId() ] = $lot->getPrice();
            }

            //compose carrier users data
            $carrierUser1CIds = [];
            if( !empty($usersIds) ){
                $users = $this->em->getRepository('AppBundle:User')->findBy(['id'=>$usersIds]);
                foreach( $users as $user ){
                    /* @var $user \AppBundle\Entity\User */
                    $carrierUser1CIds[ $user->getId() ] = $user->getCarrierId1C();
                }
            }

            //compose routes data
            $routes = '<routes>';
            foreach( $routesArr as $route ){
                /* @var $route \AppBundle\Entity\Route */
                $routes .= ' <route>'
                                .'<id>'.$route['id1c'].'</id>'
                                .'<carrierId>'.(is_null($route['user_id']) ? '' : (isset($carrierUser1CIds[ $route['user_id'] ]) ? $carrierUser1CIds[ $route['user_id'] ] : '')).'</carrierId>'
                                .'<tradeCost>'.$routesPrices[ $route['id'] ].'</tradeCost>'
                                .( !is_null($route['driver_id']) ? '<driverId>'.$route['driver_id'].'</driverId>' : '' )
                                .( !is_null($route['vehicle_id']) ? '<vehicleId>'.$route['vehicle_id'].'</vehicleId>' : '' )
                            .'</route>';
            }
            $routes .= '</routes>';

            $xml .= $routes;

            $data_added = true;
        }

        //driver's data
        $q = $this->em->getConnection()->prepare("SELECT id, passport_type, passport_series, passport_number, passport_date_issue, passport_issued_by FROM driver WHERE updated_at BETWEEN '".$prevDateExchangeTime."' AND '".$lastDateExchangeTime."'");
        $q->execute();
        $driversArr = $q->fetchAll();
        if( !empty($driversArr) ){
            echo "Drivers data composition\n";

            $docTypes = [];
            $docTypesArr = $this->em->getRepository('AppBundle:RefPassport')->findAll();
            foreach( $docTypesArr as $docType){
                /* @var $docType \AppBundle\Entity\RefPassport */
                $docTypes[ $docType->getId() ] = $docType->getId1C();
            }

            $drivers = '<drivers>';
            foreach( $driversArr as $driver ){
                $drivers .= ' <driver>'
                                .'<id>'.$driver['id'].'</id>'
                                .'<docIDType>'.$docTypes[ $driver['passport_type'] ].'</docIDType>'
                                .'<series>'.$driver['passport_series'].'</series>'
                                .'<number>'.$driver['passport_number'].'</number>'
                                .'<date>'.$driver['passport_date_issue'].'</date>'
                                .'<issuedBy>'.$driver['passport_issued_by'].'</issuedBy>'
                            .'</driver>';
            }
            $drivers .= '</drivers>';

            $xml .= $drivers;

            $data_added = true;
        }


        //vehicle's data
        $q = $this->em->getConnection()->prepare("SELECT id, name, reg_num, trailer_reg_num FROM transport WHERE updated_at BETWEEN '".$prevDateExchangeTime."' AND '".$lastDateExchangeTime."'");
        $q->execute();
        $vehicleArr = $q->fetchAll();
        if( !empty($vehicleArr) ) {
            echo "Vehicles data composition\n";

            $vehicles = '<vehicles>';
            foreach( $vehicleArr as $vehicle ){
                $vehicles .= ' <vehicle>'
                                .'<id>'.$vehicle['id'].'</id>'
                                .'<mark>'.$vehicle['name'].'</mark>'
                                .'<registrationNumber>'.$vehicle['reg_num'].'</registrationNumber>'
                                .'<truckRegistrationNumber>'.(!is_null($vehicle['trailer_reg_num']) ? $vehicle['trailer_reg_num'] : '').'</truckRegistrationNumber>'
                            .'</vehicle>';
            }
            $vehicles .= '</vehicles>';

            $xml .= $vehicles;

            $data_added = true;
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
