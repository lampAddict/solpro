<?php

namespace AppBundle\Services;

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
                        ->setEnabled(true)
                    ;
                    $this->em->persist($user);
                }
            }

            $this->em->flush();
        }

        $routeDbIds = [];
        if( !empty($data['routes']) ){

            foreach( $data['routes'] as $route ){

                if( !is_null($this->em->getRepository('AppBundle:Route')->findOneBy(['id1C'=>$route['id']])) ){
                    continue;
                }

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

                $_route->setId1C( $route['id'] );
                $_route->setLoadDate( new \DateTime($route['loadDate']) );
                $_route->setRouteDirectAssign( $route['routeType'] );
                $_route->setCode( $route['code'] );
                $_route->setName( $route['name'] );
                $_route->setStatus( $data['ref']['routeStatuse'][ $route['statusId'] ]['name'] );
                
                $_route->setRegionFrom( $data['ref']['region'][ $route['loadRegionId'] ]['name'] );
                $_route->setRegionTo( $data['ref']['region'][ $route['unloadRegionId'] ]['name'] );
                $_route->setVehicleType( $data['ref']['vehicleType'][ $route['vehicleTypeId'] ]['name'] );
                $_route->setVehiclePayload( $data['ref']['vehicleCarringType'][ $route['vehicleCarringId'] ]['name'] );
                $_route->setVehicleRegNumber( '' );
                $_route->setTradeCost( $route['tradeCost'] );
                $_route->setTradeStep( $route['tradeStep'] );
                $_route->setCargoCount( $route['cargoCount'] );
                $_route->setCargoWeight( $route['cargoWeight'] );
                $_route->setComment( $route['comment'] );
                $_route->setDriverId( null );
                $_route->setVehicleId( null );

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
        }

        if( !empty($data['lots']) ){

            foreach ($data['lots'] as $lot){

                if(    !is_null($this->em->getRepository('AppBundle:Lot')->findOneBy(['id1C'=>$lot['id']]))
                    || !isset($routeDbIds[ $lot['routeId'] ])
                ){
                    continue;
                }

                $_lot = new Lot();
                $_lot->setId1C( $lot['id'] );
                $_lot->setStatus( $data['ref']['lotStatus'][ $lot['statusID'] ]['name'] );
                $_lot->setDuration( $lot['duration'] );
                $_lot->setStartDate( new \DateTime($lot['startDate']) );
                $_lot->setCreatedAt( new \DateTime(date('c', time())) );
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
        }

        echo "Data import done\n";
    }
}