<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Bet;
use AppBundle\Entity\Filter;
use AppBundle\Entity\RefLotStatus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Date;

class AuctionController extends Controller
{

    /**
     * Composer filter condition for auction page based on user preferences
     *
     * @param array $_filters array which contains json encoded user preferences
     * @return string $where composed condition
     */
    private function makeFilterCondition( $_filters ){
        $where = 'l.auction_status = 1';
        $filters = (array)$_filters;
        if( !empty($filters) ){

            if(    $_filters->status_active
                && $_filters->status_active == 1
            ){
                $where .= ' AND l.start_date <= CURRENT_TIMESTAMP()';// AND l.start_date + l.duration*60 > CURRENT_TIMESTAMP()';
            }

            if(    $_filters->status_planned
                && $_filters->status_planned == 1
            ){
                if( $where != 'l.auction_status = 1' ){
                    $where = 'l.auction_status = 1';
                }
                else{
                    $where .= ' AND l.start_date > CURRENT_TIMESTAMP()';
                }
            }

            if(    $_filters->region_from
                && $_filters->region_from != ''
            ){
                $where .= ' AND r.region_from = \''.$_filters->region_from.'\'';
            }

            if(    $_filters->region_to
                && $_filters->region_to != ''
            ){
                $where .= ' AND r.region_to = \''.$_filters->region_to.'\'';
            }

            if(    $_filters->vehicle_types
                && is_array($_filters->vehicle_types)
            ){
                $where .= ' AND r.vehicle_type IN (\''.join('\',\'', $_filters->vehicle_types).'\')';
            }

            if(    $_filters->load_date_from
                && $_filters->load_date_from != ''
            ){
                $utz = $this->getUser()->getTimezone();
                $tz = new \DateTimeZone(($utz == '' ? 'UTC' : $utz));
                $date_from = \DateTime::createFromFormat('H:i d.m.Y', $_filters->load_date_from, $tz);
                $where .= ' AND r.load_date >= \''.($date_from->format('Y-m-d H:i:s')).'\'';
            }

            if(    $_filters->load_date_to
                && $_filters->load_date_to != ''
            ){
                $utz = $this->getUser()->getTimezone();
                $tz = new \DateTimeZone(($utz == '' ? 'UTC' : $utz));
                $date_from = \DateTime::createFromFormat('H:i d.m.Y', $_filters->load_date_to, $tz);
                $where .= ' AND r.load_date <= \''.($date_from->format('Y-m-d H:i:s')).'\'';
            }

            if(    $_filters->bet
                && intval($_filters->bet) > 0
            ){
                $em = $this->getDoctrine()->getManager();

                //get bets history and current lot owner
                $sql = 'SELECT b.user_id AS uid, b.lot_id, min(b.value) AS bet, l.price as price FROM bet b LEFT JOIN lot l ON b.lot_id = l.id WHERE b.user_id = '.$this->getUser()->getId().' GROUP BY b.user_id, b.lot_id';
                $stmt = $em->getConnection()->prepare($sql);
                $stmt->execute();
                $bets = $stmt->fetchAll();

                $lotIds = [];
                if( !empty($bets) ){
                    foreach( $bets as $bet ){
                        if(    intval($_filters->bet) == 1 //my bet
                            && $bet['price'] == $bet['bet']
                        ){
                            array_push($lotIds, $bet['lot_id']);
                        }

                        if(    intval($_filters->bet) == 2 //my bet is higher
                            && $bet['price'] < $bet['bet']
                        ){
                            array_push($lotIds, $bet['lot_id']);
                        }
                    }
                    if( !empty($lotIds) ){
                        $where .= ' AND l.id IN ('.join(',', $lotIds).')';
                    }
                    else{
                        $where .= ' AND 1<>1';
                    }
                }
                else{
                    $where .= ' AND 1<>1';
                }
            }

        }

        return $where;
    }

    /**
     * Get list of sender and delivery regions
     *
     * @return array
     */
    private function getSenderDeliveryRegionsLists(){

        $em = $this->getDoctrine()->getManager();

        $_regionsFrom = [];
        $_regionsTo = [];

        //determine delivery and sender regions
        $sql = 'SELECT DISTINCT r.region_from, r.region_to FROM lot l LEFT JOIN route r ON r.id = l.route_id WHERE l.auction_status = 1';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $_lots = $stmt->fetchAll();

        if( !empty($_lots) ){
            foreach( $_lots as $lot ){
                //fill regions data array
                $_regionsFrom[ $lot['region_from'] ] = 1;
                $_regionsTo[ $lot['region_to'] ] = 1;
            }

            ksort($_regionsFrom);
            ksort($_regionsTo);
        }

        return ['from'=>array_keys($_regionsFrom), 'to'=>array_keys($_regionsTo)];
    }

    /**
     * Get stored vehicle types
     * 
     * @return array
     */
    private function getPossibleVehicleTypes(){
        $em = $this->getDoctrine()->getManager();
        return $em->getRepository('AppBundle:RefVehicleType')->findAll();
    }
    
    /**
     * @Route("/auction", name="auction")
     */
    public function indexAction(Request $request)
    {
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted('ROLE_AUCTION');

        $redis = $this->container->get('snc_redis.default');

        $em = $this->getDoctrine()->getManager();

        //get user auction filter preferences
        $_filters = [];

        $filters = $em
            ->getRepository('AppBundle:Filter')
            ->createQueryBuilder('f')
            ->where('f.uid = '.$this->getUser()->getId())
            ->andWhere('f.type = 0')//auction filter type
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if( !empty($filters) ){
            $_filters = json_decode($filters[0]->getParams());
        }

        $where = $this->makeFilterCondition( $_filters );

        //get lots data
        $sql = 'SELECT l.*, r.id as route_id FROM lot l LEFT JOIN route r ON l.route_id = r.id WHERE l.route_id IS NOT NULL AND '.$where.' ORDER BY l.start_date DESC';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $_lots = $stmt->fetchAll();

        //get routes ids
        $routesIds = [];
        if( !empty($_lots) ){
            foreach( $_lots as $lot ){
                $routesIds[] = $lot['route_id'];
            }
        }
        else{
            $_lots = [];
        }

        $_routes = [];
        $_orders = [];
        //get routes and orders data
        if( !empty($routesIds) ){
            $sql = 'SELECT * FROM route WHERE id IN ('.join(',', $routesIds).')';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $routesData = $stmt->fetchAll();
            foreach( $routesData as $routeData ){
                $routeData['load_date'] = new \DateTime( $routeData['load_date'] );
                $_routes[ $routeData['id'] ] = $routeData;
            }

            $sql = 'SELECT * FROM orders WHERE route_id IN (' . join(',', $routesIds).')';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $ordersData = $stmt->fetchAll();
            foreach( $ordersData as $orderData ){
                $orderData['date'] = new \DateTime( $orderData['date'] );
                if( isset($_orders[ $orderData['route_id'] ]) ){
                    $_orders[ $orderData['route_id'] ][] = $orderData;
                }
                else{
                    $_orders[ $orderData['route_id'] ] = [ $orderData ];
                }
            }
        }

        $_forms = [];
        if( !empty($_lots) ){
            foreach( $_lots as $indx=>$lot ){

                /* @var $bet \AppBundle\Entity\Bet */
                $bet = new Bet();
                $bet->setLotId( $lot['id'] );
                
                $form = $this->createForm('AppBundle\Form\BetType', $bet, ['lot'=>$lot, 'route'=>$_routes[ $lot['route_id'] ]]);
                $_forms[ $lot['id'] ] = $form->createView();

                $_lots[ $indx ]['start_date']  = new \DateTime( $lot['start_date'] );

                //do `place bet` request processing
                $form->handleRequest($request);
                if(    $form->isSubmitted()
                    && $form->isValid()
                    && intval($request->request->get('appbundle_bet')['lot_id']) == $lot['id']
                ){
                    
                    $_lot = $em
                        ->getRepository('AppBundle:Lot')
                        ->createQueryBuilder('l')
                        ->where('l.id = '.$request->request->get('appbundle_bet')['lot_id'])
                        ->setMaxResults( 1 )
                        ->getQuery()
                        ->getResult();

                    $bet = new Bet();

                    /* @var $_lot \AppBundle\Entity\Lot */
                    $_lot = $_lot[0];
                    $bet->setLotId( $_lot->getId() );
                    $bet->setUserId( $this->getUser() );
                    $bet->setCreatedAt(new \DateTime());
                    if(    intval($request->request->get('appbundle_bet')['value']) <= $_lot->getPrice() - $_routes[ $lot['route_id'] ]['trade_step']
                        && intval($request->request->get('appbundle_bet')['value']) > 0
                        && $_lot->getStartDate()->getTimestamp() <= time() //auction has started
                        && ($_lot->getStartDate()->getTimestamp() + $_lot->getDuration()*60) >= time() //auction has not ended yet
                    ){
                        $bet->setValue( intval($request->request->get('appbundle_bet')['value']) );
                        $_lot->setPrice( intval($request->request->get('appbundle_bet')['value']) );

                        $prolongation = 0;
                        //auction prolongation if bet was made during last minute
                        if( $_lot->getStartDate()->getTimestamp() + $_lot->getDuration()*60 - time() < 2*60 ){
                            $prolongation = $_lot->getDuration() + 2;//minutes
                            $_lot->setDuration( $prolongation );
                        }
                        
                        $em->persist($bet);
                        //$em->persist($lot);
                        
                        //update cache lot information
                        if( $redis->exists('lcp_'.$_lot->getId()) === 0 ){
                            $redis->set('lcp_'.$_lot->getId(), json_encode(['price'=>$_lot->getPrice(), 'owner'=>$this->getUser()->getId(), 'history'=>[$this->getUser()->getId()]]));
                        }
                        else{
                            $lotBetData = json_decode( $redis->get('lcp_'.$_lot->getId()) );
                            if( !in_array($this->getUser()->getId(), $lotBetData->history) ){
                                array_push($lotBetData->history, $this->getUser()->getId() . '');
                            }
                            $lotBetData->price = $_lot->getPrice() . '';
                            $lotBetData->owner = $this->getUser()->getId() . '';
                            $redis->set('lcp_'.$_lot->getId(), json_encode($lotBetData));
                        }
                        //update cache lot end time information
                        $redis->set( 'laet_' . $_lot->getId(), $_lot->getStartDate()->getTimestamp() + $_lot->getDuration() * 60 );

                        $form = $this->createForm('AppBundle\Form\BetType', $bet, ['lot'=>$_lot]);
                        $_forms[ $_lot->getId() ] = $form->createView();

                        $em->flush();
                        
                        return new JsonResponse([    'result'=>true
                                                    ,'price'=>$_lot->getPrice() . ''
                                                    ,'bet'=>($_lot->getPrice() - $_routes[ $lot['route_id'] ]['trade_step'])
                                                    ,'prolongation'=>$prolongation*60//seconds
                        ]);
                    }

                    return new JsonResponse(['result'=>false]);
                }

            }
        }

        //get bets history and current lot owner
        $sql = 'SELECT b.lot_id, min(b.value) AS bet, b.user_id AS uid FROM bet b GROUP BY b.user_id, b.lot_id';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $bets = $stmt->fetchAll();

        $_bets = [];
        foreach( $bets as $bet ){
            if( !isset($_bets[ $bet['lot_id'] ]) ){
                $_bets[ $bet['lot_id'] ] = [
                     'owner'=>$bet['uid']
                    ,'history'=>[]
                    ,'bet'=>$bet['bet']
                ];
            }
            else{
                if( $_bets[ $bet['lot_id'] ]['bet'] > $bet['bet'] ){
                    $_bets[ $bet['lot_id'] ]['bet'] = $bet['bet'];
                    $_bets[ $bet['lot_id'] ]['owner'] = $bet['uid'];
                }
            }

            if(  !in_array($bet['uid'], $_bets[ $bet['lot_id'] ]['history']) ){
                array_push($_bets[ $bet['lot_id'] ]['history'], $bet['uid']);
            }
        }

        return $this->render('auctionPage.html.twig', array(
             'lots' => $_lots
            ,'routes' => $_routes
            ,'orders' => $_orders
            ,'forms' => $_forms
            ,'bets' => $_bets
            ,'filters' => $_filters
            ,'tz' => ($this->getUser()->getTimezone() != '' ? $this->getUser()->getTimezone() : 'UTC')
            ,'regions' => $this->getSenderDeliveryRegionsLists()
            ,'vtypes' => $this->getPossibleVehicleTypes()
        ));
    }

    /**
     * @Route("/setFilter", name="setFilter")
     */
    public function setFilterAction(Request $request){
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted('ROLE_AUCTION');

        $em = $this->getDoctrine()->getManager();

        $filter = $em
            ->getRepository('AppBundle:Filter')
            ->createQueryBuilder('f')
            ->where('f.uid = '.$this->getUser()->getId())
            ->andWhere('f.type = '.intval($request->request->get('type')))
            ->getQuery()
            ->getResult();

        if( !empty($filter) ){
            /* @var $filter \AppBundle\Entity\Filter */
            $filter = $filter[0];
        }
        else{
            $filter = new Filter();
            $filter->setUid($this->getUser()->getId());
            $filter->setType($request->request->get('type'));
        }

        $filter->setParams(json_encode($request->request->get('params')));
        $em->persist($filter);
        $em->flush();
        
        return new JsonResponse(['result'=>true]);
    }

    /**
     * @Route("/unsetFilter", name="unsetFilter")
     */
    public function unsetFilterAction(Request $request){
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted('ROLE_AUCTION');

        $em = $this->getDoctrine()->getManager();

        $filter = $em
            ->getRepository('AppBundle:Filter')
            ->createQueryBuilder('f')
            ->where('f.uid = '.$this->getUser()->getId())
            ->andWhere('f.type = '.intval($request->request->get('type')))
            ->getQuery()
            ->getResult();

        if( !empty($filter) ){
            $em->remove($filter[0]);
            $em->flush();
            return new JsonResponse(['result'=>true]);
        }
        return new JsonResponse(['result'=>false]);
    }
}