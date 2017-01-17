<?php

namespace AppBundle\Services;

use AppBundle\Entity\Lot;
use AppBundle\Entity\Order;
use AppBundle\Entity\Route;

class import1CDataService{

    protected $em;

    public function __construct($entityManager){
        $this->em = $entityManager;
    }

    public function import1CData(array $data){

        $routeDbIds = [];
        if( !empty($data['routes']) ){

            foreach( $data['routes'] as $route ){

                $_route = new Route();

                $user = null;
                if(    $route['routeType'] == 1
                    && intval($route['carrierId']) > 0
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
                $_route->setVehicleDriver( null );
                $_route->setTradeCost( $route['tradeCost'] );
                $_route->setTradeStep( $route['tradeStep'] );
                $_route->setCargoCount( $route['cargoCount'] );
                $_route->setCargoWeight( $route['cargoWeight'] );
                $_route->setComment( $route['comment'] );

                $this->em->persist($_route);
                $this->em->flush();

                $routeDbIds[ $route['id'] ] = ['routeId'=>$_route, 'startPrice'=>$route['tradeCost']];

                if( !empty($route['orders']) ){

                    foreach( $route['orders'] as $order ){
                        
                        $_order = new Order();
                        $_order->setRouteId( $_route );
                        $_order->setId1C( $order['id'] );
                        $_order->setCode( $order['code'] );
                        $_order->setDate( new \DateTime($order['date']) );
                        $_order->setConsignee( $order['consignee'] );
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

                $_lot = new Lot();
                $_lot->setId1C( $lot['id'] );
                $_lot->setStatus( $data['ref']['lotStatus'][ $lot['statusID'] ]['name'] );
                $_lot->setDuration( $lot['duration'] );
                $_lot->setStartDate( new \DateTime($lot['startDate']) );
                $_lot->setCreatedAt( new \DateTime(date('c', time())) );

                if( isset($routeDbIds[ $lot['routeId'] ]) ){
                    $_lot->setRouteId( $routeDbIds[ $lot['routeId'] ]['routeId'] );
                    $_lot->setPrice( $routeDbIds[ $lot['routeId'] ]['startPrice'] );
                }

                $_lot->setAuctionStatus(1);//lot is in auction state
                
                $this->em->persist($_lot);
                $this->em->flush();
            }
        }

        echo 'Data import done';
    }
}