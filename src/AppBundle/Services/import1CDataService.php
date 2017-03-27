<?php

namespace AppBundle\Services;

use AppBundle\Entity\Exchange;
use AppBundle\Entity\Lot;
use AppBundle\Entity\Order;
use AppBundle\Entity\Route;
use AppBundle\Entity\RefLotStatus;
use AppBundle\Entity\RefRouteStatus;
use AppBundle\Entity\RefRegion;
use AppBundle\Entity\RefPartner;
use AppBundle\Entity\RefVehicleType;
use AppBundle\Entity\RefCarrier;
use AppBundle\Entity\RefCarrierUser;
use Symfony\Component\Validator\Constraints\Date;


class import1CDataService{

    protected $em;
    protected $um;

    public function __construct($entityManager, $userManager){
        $this->em = $entityManager;
        $this->um = $userManager;
    }
    
    private function importReferences($entityName, $refsData, $fields=['id', 'name']){

        $className = 'AppBundle\Entity\\'.$entityName;

        $fieldMethods = [    'name'      => 'setName'
                            ,'id'        => 'setId1C'
                            ,'inn'       => 'setInn'
                            ,'access'    => 'setAccess'
                            ,'carrierId' => 'setCarrierId'
                            ,'login'     => 'setLogin'
                            ,'password'  => 'setPassword'
                            ,'email'     => 'setEmail'
                        ];

        foreach( $refsData as $k=>$v ){
            $e = new $className();
            foreach ($fields as $fld){
                $e->{$fieldMethods[$fld]}( $v[$fld] );
            }
            $e_exist = $this->em->getRepository('AppBundle:'.$entityName)->findOneBy(['id1C'=>$k]);
            if( is_null($e_exist) ){
                $this->em->persist($e);
            }
        }
    }

    public function import1CData(array $data){

        //check if data has been already loaded
        $data_loaded = $this->em->getRepository('AppBundle:Exchange')->findOneBy(['recNum'=>$data['sendNum']]);
        if( !is_null($data_loaded) ){
            /* @var $data_loaded \AppBundle\Entity\Exchange */
            $data_loaded->setDateExchange( new \DateTime(date('c', time())) );
            $this->em->flush();
            //skip data if it was loaded in earlier messages
            return false;
        }

        //import lot status references
        if( !empty($data['ref']['lotStatus']) ){
            $this->importReferences('RefLotStatus', $data['ref']['lotStatus']);
            $this->em->flush();
        }

        //import route status  references
        if( !empty($data['ref']['routeStatuse']) ){
            $this->importReferences('RefRouteStatus', $data['ref']['routeStatuse']);
            $this->em->flush();
        }

        //import region references
        if( !empty($data['ref']['region']) ){
            $this->importReferences('RefRegion', $data['ref']['region']);
            $this->em->flush();
        }

        //import partner references
        if( !empty($data['ref']['partner']) ){
            $this->importReferences('RefPartner', $data['ref']['partner'], ['id', 'name', 'inn']);
            $this->em->flush();
        }

        //import vehicle type references
        if( !empty($data['ref']['vehicleType']) ){
            $this->importReferences('RefVehicleType', $data['ref']['vehicleType']);
            $this->em->flush();
        }

        //import vehicle carrying type references
        if( !empty($data['ref']['vehicleCarringType']) ){
            $this->importReferences('RefVehicleCarryingType', $data['ref']['vehicleCarringType']);
            $this->em->flush();
        }

        //import carrier references
        if( !empty($data['ref']['carrier']) ){
            $this->importReferences('RefCarrier', $data['ref']['carrier'], ['id', 'name', 'access']);
            $this->em->flush();
        }

        //import types of persons ID's
        if( !empty($data['ref']['docIDType']) ){
            $this->importReferences('RefPassport', $data['ref']['docIDType'], ['id', 'name']);
            $this->em->flush();
        }

        echo "References imported\n";

        //import carrier users references
        if( !empty($data['ref']['carrierUser']) ){
            $this->importReferences('RefCarrierUser', $data['ref']['carrierUser'], ['id', 'name', 'access', 'carrierId', 'login', 'password', 'email']);

            //create user profiles
            foreach( $data['ref']['carrierUser'] as $k=>$v ){
                $usr_exist = $this->em->getRepository('AppBundle:User')->findOneBy(['username'=>$v['login']]);
                if( is_null($usr_exist) ){
                    $user = $this->um->createUser();
                    $user
                        ->setUsername($v['login'])
                        ->setEmail($v['email'])
                        ->setPlainPassword($v['password'])
                        ->setCarrierId1C($v['carrierId'])
                        ->setEnabled(true)
                    ;
                    $this->em->persist($user);
                }
            }

            $this->em->flush();
        }

        echo "Carrier users profiles created\n";

        $routeDbIds = [];
        if( !empty($data['routes']) ){

            foreach( $data['routes'] as $route ){
                /* @var $_route \AppBundle\Entity\Route */
                $_route = $this->em->getRepository('AppBundle:Route')->findOneBy(['id1C'=>$route['id']]);
                if( !is_null($_route) ){

                    /* @var $routeStatusUpdate \AppBundle\Entity\RefRouteStatus */
                    $routeStatusUpdate = $this->em->getRepository('AppBundle:RefRouteStatus')->findOneBy(['id1C'=>$route['statusId']]);

                    //update route status
                    if( $_route->getStatus() != $routeStatusUpdate->getName() ){
                        $_route->setStatus( $routeStatusUpdate->getName() );
                        $_route->setUpdatedAt( new \DateTime(date('c', time())) );
                        $this->em->flush();
                    }

                    continue;
                }
                /* @var $_route \AppBundle\Entity\Route */
                $_route = new Route();

                $user = null;
                if(    intval($route['routeType']) == 0
                    && $route['carrierId'] != ''
                ){
                    foreach( $data['ref']['carrierUser'] as $cu ){
                        if( $cu['carrierId'] == $route['carrierId'] ){
                            $user = $this->em->getRepository('AppBundle:User')->findOneBy(['username'=>$cu['login']]);
                            break;
                        }
                    }
                }

                if( !is_null($user) )
                    $_route->setUserId( $user );

                if(    isset($route['carrierId'])
                    && $route['carrierId'] != ''
                ){
                    $_route->setCarrier( $data['ref']['carrier'][ $route['carrierId'] ]['name'] );
                }
                else{
                    $_route->setCarrier( '' );
                }

                $routeStatus      = isset($data['ref']['routeStatuse'][ $route['statusId'] ]) ? $data['ref']['routeStatuse'][ $route['statusId'] ]['name'] : '';
                $routeRegionFrom  = isset($data['ref']['region'][ $route['loadRegionId'] ]) ? $data['ref']['region'][ $route['loadRegionId'] ]['name'] : '';
                $routeRegionTo    = isset($data['ref']['region'][ $route['unloadRegionId'] ]) ? $data['ref']['region'][ $route['unloadRegionId'] ]['name'] : '';
                $routeVehicleType = isset($data['ref']['vehicleType'][ $route['vehicleTypeId'] ]) ? $data['ref']['vehicleType'][ $route['vehicleTypeId'] ]['name']: '';

                $_route->setId1C( $route['id'] );

                $loadDate = new \DateTime($route['loadDate']);
                //$loadDate->setTimezone( new \DateTimeZone('UTC') );
                $_route->setLoadDate( $loadDate );

                $_route->setRouteDirectAssign( $route['routeType'] );
                $_route->setCode( $route['code'] );
                $_route->setName( $route['name'] );
                $_route->setStatus( $routeStatus );
                $_route->setRegionFrom( $routeRegionFrom );
                $_route->setRegionTo( $routeRegionTo );
                $_route->setVehicleType( $routeVehicleType );

                if(    isset($route['vehicleCarringId'])
                    && $route['vehicleCarringId'] != ''
                    && !is_null($data['ref']['vehicleCarringType'][ $route['vehicleCarringId'] ]['name'])
                ){
                    $_route->setVehiclePayload( $data['ref']['vehicleCarringType'][ $route['vehicleCarringId'] ]['name'] );
                }
                else{
                    $_route->setVehiclePayload(' ');
                }

                $_route->setVehicleRegNumber( '' );
                $_route->setTradeCost( $route['tradeCost'] );
                $_route->setTradeStep( $route['tradeStep'] );
                $_route->setCargoCount( $route['cargoCount'] );
                $_route->setCargoWeight( $route['cargoWeight'] );
                $_route->setComment( $route['comment'] );
                $_route->setDriverId( null );
                $_route->setVehicleId( null );
                $_route->setUpdatedAt( new \DateTime(date('c', time())) );

                $this->em->persist($_route);
                $this->em->flush();

                $routeDbIds[ $route['id'] ] = ['routeId'=>$_route, 'startPrice'=>$route['tradeCost']];

                if(    !empty($route['orders'])
                    && isset($routeDbIds[ $route['id'] ])
                ){
                    foreach( $route['orders'] as $order ){

                        if( !is_null($this->em->getRepository('AppBundle:Order')->findOneBy(['id1C'=>$order['id']])) ){
                            continue;
                        }
                        /* @var $_order \AppBundle\Entity\Order */
                        $_order = new Order();
                        $_order->setRouteId( $_route );
                        $_order->setId1C( $order['id'] );
                        $_order->setCode( $order['code'] );
                        $_order->setDate( new \DateTime($order['date']) );
                        $_order->setConsignee( $data['ref']['partner'][ $order['consignee'] ]['name'] );
                        $_order->setUnloadAddress( $order['unloadAddress'] );
                        $_order->setWeight( $order['weight'] );
                        $_order->setCountNum( $order['count'] );
                        $_order->setLoadSpecialConditions( $order['loadSpecialConditions'] );
                        $_order->setUnloadSpecialConditions( $order['unloadSpecialConditions'] );

                        if(    isset($order['manager'])
                            && $order['manager'] != ''
                            && !empty($data['ref']['contact'])
                        ){
                            $_order->setManager( $data['ref']['contact'][ $order['manager'] ]['name'] );
                        }
                        else{
                            $_order->setManager( '' );
                        }

                        $_order->setComment( $order['comment'] );

                        $this->em->persist($_order);
                        $this->em->flush();
                    }
                }
            }

            echo "Routes imported\n";
        }

        if( !empty($data['lots']) ){

            foreach( $data['lots'] as $lot ){

                if(    !is_null($this->em->getRepository('AppBundle:Lot')->findOneBy(['id1C'=>$lot['id']]))
                    || !isset($routeDbIds[ $lot['routeId'] ])
                ){
                    continue;
                }
                /* @var $_lot \AppBundle\Entity\Lot */
                $_lot = new Lot();
                $_lot->setId1C( $lot['id'] );
                $_lot->setStatusId1c( $lot['statusID'] );
                $_lot->setDuration( $lot['duration'] );

                $startDate = new \DateTime($lot['startDate']);
                $startDate->sub(new \DateInterval('PT3H'));//subtract 3 hours from start date, 'cause it must be UTC and by default here comes Europe/Moscow time
                $startDate->setTimezone( new \DateTimeZone('UTC') );
                $_lot->setStartDate( $startDate );

                $_lot->setCreatedAt( new \DateTime(date('c', time())) );
                $_lot->setUpdatedAt( new \DateTime(date('c', time())) );
                $_lot->setAuctionStatus(1);//lot is in auction state

                if( isset($routeDbIds[ $lot['routeId'] ]) ){
                    $route = $routeDbIds[ $lot['routeId'] ]['routeId'];
                    $_lot->setRouteId( $route );
                    $_lot->setPrice( $routeDbIds[ $lot['routeId'] ]['startPrice'] );

                    if( $route->getUserId() ){
                        $_lot->setAuctionStatus(0);
                    }
                }

                $this->em->persist($_lot);
                $this->em->flush();
            }

            echo "Lots imported\n";
        }
        
        //save received message number to db
        $exchange = new Exchange();
        $exchange->setSendNum( $data['recNum'] );
        $exchange->setRecNum( $data['sendNum'] );
        $exchange->setDateExchange( new \DateTime(date('c', time())) );
        $this->em->persist($exchange);
        $this->em->flush();

        echo "Data import done\n\n";
        
        return true;
    }
}