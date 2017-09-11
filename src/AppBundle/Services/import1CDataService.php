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
    protected $redis;
    protected $rlss;

    public function __construct($entityManager, $userManager, $redisManager, $refLotStatusService){
        $this->em    = $entityManager;
        $this->um    = $userManager;
        $this->redis = $redisManager;
        $this->rlss  = $refLotStatusService;
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

            $rlsArr = $this->em->getRepository('AppBundle:RefLotStatus')->findAll();
            $rlsData = [];
            /* @var $rls \AppBundle\Entity\RefLotStatus */
            foreach ($rlsArr as $rls){
                $rlsData[ $rls->getPid() ] = $rls;
            }

            foreach( $data['ref']['lotStatus'] as $k=>$v ){

                if( strpos($v['name'], 'Подготовка') !== false ){
                    /* @var $lotStatus \AppBundle\Entity\RefLotStatus */
                    $lotStatus = $rlsData[ RefLotStatus::AUCTION_PREPARED ];
                    $lotStatus->setName($v['name']);
                    $lotStatus->setId1C($v['id']);

                    $this->em->persist($lotStatus);
                    $this->em->flush();
                    continue;
                }

                if( strpos($v['name'], 'Торги') !== false ){
                    /* @var $lotStatus \AppBundle\Entity\RefLotStatus */
                    $lotStatus = $rlsData[ RefLotStatus::AUCTION ];
                    $lotStatus->setName($v['name']);
                    $lotStatus->setId1C($v['id']);

                    $this->em->persist($lotStatus);
                    $this->em->flush();
                    continue;
                }

                if( strpos($v['name'], 'Расторгован') !== false ){
                    /* @var $lotStatus \AppBundle\Entity\RefLotStatus */
                    $lotStatus = $rlsData[ RefLotStatus::AUCTION_SUCCEED ];
                    $lotStatus->setName($v['name']);
                    $lotStatus->setId1C($v['id']);

                    $this->em->persist($lotStatus);
                    $this->em->flush();
                    continue;
                }

                if( strpos($v['name'], 'Нерасторгован') !== false ){
                    /* @var $lotStatus \AppBundle\Entity\RefLotStatus */
                    $lotStatus = $rlsData[ RefLotStatus::AUCTION_FAILED ];
                    $lotStatus->setName($v['name']);
                    $lotStatus->setId1C($v['id']);

                    $this->em->persist($lotStatus);
                    $this->em->flush();
                    continue;
                }

                if( strpos($v['name'], 'Завершен') !== false ){
                    /* @var $lotStatus \AppBundle\Entity\RefLotStatus */
                    $lotStatus = $rlsData[ RefLotStatus::AUCTION_CLOSED ];
                    $lotStatus->setName($v['name']);
                    $lotStatus->setId1C($v['id']);

                    $this->em->persist($lotStatus);
                    $this->em->flush();
                    continue;
                }

                if( strpos($v['name'], 'Отменен') !== false ){
                    /* @var $lotStatus \AppBundle\Entity\RefLotStatus */
                    $lotStatus = $rlsData[ RefLotStatus::AUCTION_DECLINED ];
                    $lotStatus->setName($v['name']);
                    $lotStatus->setId1C($v['id']);

                    $this->em->persist($lotStatus);
                    $this->em->flush();
                    continue;
                }
            }
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
                /* @var $usr \AppBundle\Entity\User */
                $usr = $this->em->getRepository('AppBundle:User')->findOneBy(['username'=>$v['login']]);
                if( is_null($usr) ){
                    $user = $this->um->createUser();
                    $user
                        ->setUsername($v['login'])
                        ->setEmail($v['email'])
                        ->setPlainPassword($v['password'])
                        ->setCarrierId1C($v['carrierId'])
                        ->setEnabled(($v['access'] == 'true'?1:0))
                    ;
                    $this->em->persist($user);
                }
                else{
                    $usr->setEmail($v['email'])
                        ->setPlainPassword($v['password'])
                        ->setCarrierId1C($v['carrierId'])
                        ->setEnabled(($v['access'] == 'true'?1:0));
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
                if( is_null($_route) ){
                    $_route = new Route();
                }

//                $user = null;
//                if(    intval($route['routeType']) == 0
//                    && $route['carrierId'] != ''
//                ){
//                    foreach( $data['ref']['carrierUser'] as $cu ){
//                        if( $cu['carrierId'] == $route['carrierId'] ){
//                            $user = $this->em->getRepository('AppBundle:User')->findOneBy(['username'=>$cu['login']]);
//                            break;
//                        }
//                    }
//                }
//
//                if( !is_null($user) )
//                    $_route->setUserId( $user );

                //direct route assignment
                if(    isset($route['carrierId'])
                    && $route['carrierId'] != ''
                ){
                    $_route->setCarrier( $route['carrierId'] );//$data['ref']['carrier'][ $route['carrierId'] ]['name']
                }
                else{

                    if( is_null($_route->getCarrier()) )
                        $_route->setCarrier( '' );
                }

                //set route status
                $routeStatus = '';
                if( isset($route['statusId']) ){
                    $routeStatus = isset($data['ref']['routeStatuse'][ $route['statusId'] ]) ? $data['ref']['routeStatuse'][ $route['statusId'] ]['name'] : '';
                    if( $routeStatus == '' ){
                        /* @var $routeStatusUpdate \AppBundle\Entity\RefRouteStatus */
                        $routeStatusUpdate = $this->em->getRepository('AppBundle:RefRouteStatus')->findOneBy(['id1C'=>$route['statusId']]);
                        if( $routeStatusUpdate ){
                            $routeStatus = $routeStatusUpdate->getName();
                            $_route->setStatus( $routeStatus );
                        }
                    }
                    else{
                        $_route->setStatus( $routeStatus );
                    }
                }

                //set route 1C id
                $_route->setId1C( $route['id'] );

                //$loadDate->setTimezone( new \DateTimeZone('UTC') );
                //set route load date
                if( isset($route['loadDate']) ){
                    $loadDate = new \DateTime($route['loadDate']);
                    $_route->setLoadDate( $loadDate );
                }

                //set route type
                if( isset($route['routeType']) ){
                    $_route->setRouteDirectAssign( $route['routeType'] );
                }

                //set route code
                if( isset($route['code']) ){
                    $_route->setCode( $route['code'] );
                }

                //set route name
                if( isset($route['name']) ){
                    $_route->setName( $route['name'] );
                }

                //set route region_from
                if( isset($route['loadRegionId']) ){
                    $routeRegionFrom = isset($data['ref']['region'][ $route['loadRegionId'] ]) ? $data['ref']['region'][ $route['loadRegionId'] ]['name'] : '';
                    $_route->setRegionFrom( $routeRegionFrom );
                }

                //set route region_to
                if( isset($route['unloadRegionId']) ){
                    $routeRegionTo = isset($data['ref']['region'][ $route['unloadRegionId'] ]) ? $data['ref']['region'][ $route['unloadRegionId'] ]['name'] : '';
                    $_route->setRegionTo( $routeRegionTo );
                }

                //set route vehicle type
                if( isset($route['vehicleTypeId']) ){
                    $routeVehicleType = isset($data['ref']['vehicleType'][ $route['vehicleTypeId'] ]) ? $data['ref']['vehicleType'][ $route['vehicleTypeId'] ]['name']: '';
                    $_route->setVehicleType( $routeVehicleType );
                }

                //set route vehicle carrying id
                if( isset($route['vehicleCarringId']) ){
                    if(
                            $route['vehicleCarringId'] != ''
                        && !is_null($data['ref']['vehicleCarringType'][ $route['vehicleCarringId'] ]['name'])
                    ){
                        $_route->setVehiclePayload( $data['ref']['vehicleCarringType'][ $route['vehicleCarringId'] ]['name'] );
                    }
                    else{
                        $_route->setVehiclePayload(' ');
                    }
                }

                $_route->setVehicleRegNumber( '' );

                //set route trade cost
                if( isset($route['tradeCost']) ){
                    $_route->setTradeCost( $route['tradeCost'] );
                }

                //set route trade step
                if( isset($route['tradeStep']) ){
                    $_route->setTradeStep( $route['tradeStep'] );
                }

                //set route cargo count
                if( isset($route['cargoCount']) ){
                    $_route->setCargoCount( $route['cargoCount'] );
                }

                //set route cargo weight
                if( isset($route['cargoWeight']) ){
                    $_route->setCargoWeight( $route['cargoWeight'] );
                }

                //set route comment
                if( isset($route['comment']) ){
                    $_route->setComment( $route['comment'] );
                }

                //set route default driver id
                if( is_null($_route->getDriverId()) ){
                    $_route->setDriverId( null );
                }

                //set route default vehicle id
                if( is_null($_route->getVehicleId()) ){
                    $_route->setVehicleId( null );
                }

                $_route->setUpdatedAt( new \DateTime(date('c', time())) );

                if( is_null($_route->getId()) ){
                    $this->em->persist($_route);
                }

                $this->em->flush();

                $routeDbIds[ $route['id'] ] = ['routeId'=>$_route, 'startPrice'=>$route['tradeCost']];

                if(    !empty($route['orders'])
                    && isset($routeDbIds[ $route['id'] ])
                ){
                    foreach( $route['orders'] as $order ){

                        /* @var $_order \AppBundle\Entity\Order */
                        $_order = $this->em->getRepository('AppBundle:Order')->findOneBy(['id1C'=>$order['id']]);
                        if( is_null($_order) ){
                            $_order = new Order();
                        }

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

                        if( is_null($_order->getId()) ){
                            $this->em->persist($_order);
                        }

                        $this->em->flush();
                    }
                }
            }

            echo "Routes imported\n";
        }

        if( !empty($data['lots']) ){

            $routeStatusByPid = $this->rlss->getLotStatuses();

            $currentIdsStr = '';
            if( $this->redis->exists('lcp') ){
                $currentIdsStr = $this->redis->get('lcp');
            }
            $l = strlen($currentIdsStr);

            foreach( $data['lots'] as $lot ){

                //if lot has no routes then skip this lot
                if( !isset($routeDbIds[ $lot['routeId'] ]) ){
                    continue;
                }

                /* @var $_lot \AppBundle\Entity\Lot */
                $_lot = $this->em->getRepository('AppBundle:Lot')->findOneBy(['id1C'=>$lot['id']]);
                if( is_null($_lot) ){
                    $_lot = new Lot();
                }

                $lotStatus = $this->em->getRepository('AppBundle:RefLotStatus')->findOneBy(['id1C'=>$lot['statusID']]);
                /* @var $lotStatus \AppBundle\Entity\RefLotStatus */
                if( $lotStatus ){
                    //if lot already exists
                    if( !is_null($_lot->getId()) ){
                        //lot set `decline` status routine
                        if( strpos($lotStatus->getName(), 'Отменен') !== false ){

                            //stop auction routine
                            //if lot is in `auction` state
                            $lotAuctionStatus = $_lot->getAuctionStatus();
                            if( $lotAuctionStatus >=0 && $lotAuctionStatus <= 1 ){
                                $_lot->setDuration(0);
                                $this->redis->set( 'laet_' . $_lot->getId(), $_lot->getStartDate()->getTimestamp() );

                                //delete all bets
                                $this->em->createQuery("delete from AppBundle\Entity\Bet b where b.lot_id = '" . $_lot->getId() . "'")->execute();

                                //delete route owner
                                if( isset($routeDbIds[ $lot['routeId'] ]) ){
                                    /* @var $route \AppBundle\Entity\Route */
                                    $route = $routeDbIds[ $lot['routeId'] ]['routeId'];
                                    //$route->setCarrier( '' );
                                    $route->setUserId( null );
                                }

                                $_lot->setRejectionReason( $lot['rejectionReason'] );
                                $_lot->setAuctionStatus(2);//lot declined
                                $_lot->setStatusId1c( $lotStatus->getId1C() );

                                $this->em->flush();
                                continue;
                            }
                        }
                        elseif( strpos($lotStatus->getName(), 'Подготовка') !== false ){
                            //set lot status AUCTION
                            $_lot->setStatusId1c($routeStatusByPid[ RefLotStatus::AUCTION ]);
                            //lot is in auction state
                            $_lot->setAuctionStatus(1);
                        }
                        else{
                            //update lot status
                            $_lot->setStatusId1c( $lotStatus->getId1C() );
                        }
                    }
                    else{
                        //set lot status
                        if( strpos($lotStatus->getName(), 'Отменен') !== false ){
                            $_lot->setRejectionReason( $lot['rejectionReason'] );
                            $_lot->setAuctionStatus(2);//lot declined
                            $_lot->setStatusId1c( $lotStatus->getId1C() );
                        }
                        //set lot status AUCTION
                        else{
                            $_lot->setStatusId1c($routeStatusByPid[ RefLotStatus::AUCTION ]);
                            //lot is in auction state
                            $_lot->setAuctionStatus(1);
                            $_lot->setRejectionReason('');
                        }
                    }
                }

                $_lot->setId1C( $lot['id'] );
                $_lot->setDuration( $lot['duration'] );

                $startDate = new \DateTime($lot['startDate']);
                $startDate->sub(new \DateInterval('PT3H'));//subtract 3 hours from start date, 'cause it must be UTC and by default here comes Europe/Moscow time
                $startDate->setTimezone( new \DateTimeZone('UTC') );
                $_lot->setStartDate( $startDate );

                $_lot->setCreatedAt( new \DateTime(date('c', time())) );
                $_lot->setUpdatedAt( new \DateTime(date('c', time())) );

                $addLotToCache = true;

                if( isset($routeDbIds[ $lot['routeId'] ]) ){
                    /* @var $route \AppBundle\Entity\Route */
                    $route = $routeDbIds[ $lot['routeId'] ]['routeId'];

                    $_lot->setPrice( $routeDbIds[ $lot['routeId'] ]['startPrice'] );
                    $_lot->setRouteId($route);

                    //if route assigned directly no need to do auction
                    if( $route->getCarrier() != '' ){
                        //if lot is in `auction` state
                        if( $_lot->getAuctionStatus() == 1 ){
                            $_lot->setAuctionStatus(0);
                            //set lot status AUCTION SUCCEED
                            $_lot->setStatusId1c($routeStatusByPid[ RefLotStatus::AUCTION_SUCCEED ]);
                            $addLotToCache = false;
                        }
                    }
                }

                if( is_null($_lot->getId()) ){
                    $this->em->persist($_lot);
                }

                $this->em->flush();

                if( $addLotToCache ){
                    $this->redis->set( 'laet_' . $_lot->getId(), $_lot->getStartDate()->getTimestamp() + $_lot->getDuration() * 60 );

                    $this->redis->set( 'lcp_' . $_lot->getId(),  json_encode([ 'price'=>$_lot->getPrice(), 'owner'=>0, 'history'=>[], 'bet'=>0 ]) );

                    $currentIdsStr .= ($currentIdsStr == '' ? $_lot->getId() : ','.$_lot->getId());

                    echo "laet_" . $_lot->getId() . " " . $_lot->getStartDate()->getTimestamp()  . " " .  ($_lot->getDuration() * 60) . "\n";
                }
            }

            if( strlen($currentIdsStr) > $l ){
                $this->redis->set( 'lcp', $currentIdsStr);
                $this->redis->expire('lcp', 600);
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