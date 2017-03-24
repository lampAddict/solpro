<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Bet;
use AppBundle\Entity\Filter;
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
     * @param array $filters array which contains json encoded user preferences
     * @return string $where composed condition
     */
    private function makeFilterCondition( $filters ){
        $where = 'l.auctionStatus = 1';
        if( !empty($filters) ){

            $_filters = json_decode($filters[0]->getParams());

            if(    $_filters->status_active
                && $_filters->status_active == 1
            ){
                $where .= ' AND l.startDate <= CURRENT_TIMESTAMP()';// AND l.startDate + l.duration*60 > CURRENT_TIMESTAMP()';
            }

            if(    $_filters->status_planned
                && $_filters->status_planned == 1
            ){
                if( $where != 'l.auctionStatus = 1' ){
                    $where = 'l.auctionStatus = 1';
                }
                else{
                    $where .= ' AND l.startDate > '.time();
                }
            }

            if(    $_filters->region_from
                && $_filters->region_from != ''
            ){
                $where .= ' AND r.regionFrom = \''.$_filters->region_from.'\'';
            }

            if(    $_filters->region_to
                && $_filters->region_to != ''
            ){
                $where .= ' AND r.regionTo = \''.$_filters->region_to.'\'';
            }

            if(    $_filters->vehicle_types
                && is_array($_filters->vehicle_types)
            ){
                $where .= ' AND r.vehicleType IN (\''.join('\',\'', $_filters->vehicle_types).'\')';
            }

            if(    $_filters->load_date_from
                && $_filters->load_date_from != ''
            ){
                $utz = $this->getUser()->getTimezone();
                $tz = new \DateTimeZone(($utz == '' ? 'UTC' : $utz));
                $date_from = \DateTime::createFromFormat('H:i d.m.Y', $_filters->load_date_from, $tz);
                $where .= ' AND r.loadDate >= \''.($date_from->format('Y-m-d H:i:s')).'\'';
            }

            if(    $_filters->load_date_to
                && $_filters->load_date_to != ''
            ){
                $utz = $this->getUser()->getTimezone();
                $tz = new \DateTimeZone(($utz == '' ? 'UTC' : $utz));
                $date_from = \DateTime::createFromFormat('H:i d.m.Y', $_filters->load_date_to, $tz);
                $where .= ' AND r.loadDate <= \''.($date_from->format('Y-m-d H:i:s')).'\'';
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
        $_lots = $em
            ->getRepository('AppBundle:Lot')
            ->createQueryBuilder('l')
            ->leftJoin('l.routeId', 'r')
            ->where('l.auctionStatus = 1')
            ->orderBy('l.startDate')
            ->getQuery()
            ->getResult();
        if( !empty($_lots) ){
            /* @var $lot \AppBundle\Entity\Lot */
            foreach( $_lots as $lot ) {

                //fill regions data array
                if (!isset($_regionsFrom[$lot->getRouteId()->getRegionFrom()])) {
                    $_regionsFrom[$lot->getRouteId()->getRegionFrom()] = 1;
                }

                if (!isset($_regionsTo[$lot->getRouteId()->getRegionTo()])) {
                    $_regionsTo[$lot->getRouteId()->getRegionTo()] = 1;
                }
            }

            ksort($_regionsFrom);
            ksort($_regionsTo);
        }

        return ['from'=>array_keys($_regionsFrom), 'to'=>array_keys($_regionsTo)];
    }

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

        $redis = $this->container->get('snc_redis.default');

        $em = $this->getDoctrine()->getManager();

        //get user filter preferences
        $_filters = [];

        $filters = $em
            ->getRepository('AppBundle:Filter')
            ->createQueryBuilder('f')
            ->where('f.uid = '.$this->getUser()->getId())
            ->andWhere('f.type = 0')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if( !empty($filters) ){
            $_filters = json_decode($filters[0]->getParams());
        }

        $where = $this->makeFilterCondition( $filters );

        $_lots = $em
            ->getRepository('AppBundle:Lot')
            ->createQueryBuilder('l')
            ->leftJoin('l.routeId', 'r')
            ->where($where)
            ->orderBy('l.startDate')
            ->getQuery()
            ->getResult();

        $_routes = [];
        $_orders = [];
        $_forms = [];
        if( !empty($_lots) ){
            /* @var $lot \AppBundle\Entity\Lot */
            foreach( $_lots as $lot ){
                
                //fill routes data array
                $_routes[ $lot->getRouteId()->getId() ] = $lot->getRouteId();
                
                //fill orders data array
                $_orders[ $lot->getRouteId()->getId() ] = $lot->getRouteId()->getOrders();

                /* @var $bet \AppBundle\Entity\Bet */
                $bet = new Bet();
                $bet->setLotId($lot->getId());
                
                $form = $this->createForm('AppBundle\Form\BetType', $bet, ['lot'=>$lot]);
                $_forms[ $lot->getId() ] = $form->createView();

                //update lot status if it has begun trading
                if(     $lot->getStatusId1c() == '175d0f31-a9ca-45ba-835e-bae500c8c35c' // "подготовка"
                    &&  $lot->getStartDate()->getTimestamp() >= time()
                ){
                    $lot->setStatusId1c('e9bb1413-3642-49ad-8599-6df140a01ac0'); //"торги"
                    $lot->setUpdatedAt( new \DateTime(date('c', time())) );
                    $em->flush();
                }

                //do `place bet` request processing
                $form->handleRequest($request);
                if(    $form->isSubmitted()
                    && $form->isValid()
                    && intval($request->request->get('appbundle_bet')['lot_id']) == $lot->getId()
                ){
                    
                    $lot = $em
                        ->getRepository('AppBundle:Lot')
                        ->createQueryBuilder('l')
                        ->leftJoin('l.routeId', 'r')
                        ->where('l.id = '.$request->request->get('appbundle_bet')['lot_id'])
                        ->setMaxResults( 1 )
                        ->getQuery()
                        ->getResult();

                    $bet = new Bet();

                    /* @var $lot \AppBundle\Entity\Lot */
                    $lot = $lot[0];

                    $bet->setLotId( $lot->getId() );
                    $bet->setUserId( $this->getUser() );
                    $bet->setCreatedAt(new \DateTime());
                    if(    intval($request->request->get('appbundle_bet')['value']) <= $lot->getPrice() - $lot->getRouteId()->getTradeStep()
                        && intval($request->request->get('appbundle_bet')['value']) > 0
                        && $lot->getStartDate()->getTimestamp() <= time() //auction has started
                        && ($lot->getStartDate()->getTimestamp() + $lot->getDuration()*60) >= time() //auction has not ended yet
                    ){
                        $bet->setValue( intval($request->request->get('appbundle_bet')['value']) );
                        $lot->setPrice( intval($request->request->get('appbundle_bet')['value']) );

                        $prolongation = 0;
                        //auction prolongation if bet was made during last minute
                        if( $lot->getStartDate()->getTimestamp() + $lot->getDuration()*60 - time() < 2*60 ){
                            $prolongation = $lot->getDuration() + 2;//minutes
                            $lot->setDuration( $prolongation );
                        }
                        
                        $em->persist($bet);
                        //$em->persist($lot);
                        
                        //update cache lot information
                        if( $redis->exists('lcp_'.$lot->getId()) === 0 ){
                            $redis->set('lcp_'.$lot->getId(), json_encode(['price'=>$lot->getPrice(), 'owner'=>$this->getUser()->getId(), 'history'=>[$this->getUser()->getId()]]));
                        }
                        else{
                            $lotBetData = json_decode( $redis->get('lcp_'.$lot->getId()) );
                            if( !in_array($this->getUser()->getId(), $lotBetData->history) ){
                                array_push($lotBetData->history, $this->getUser()->getId() . '');
                            }
                            $lotBetData->price = $lot->getPrice() . '';
                            $lotBetData->owner = $this->getUser()->getId() . '';
                            $redis->set('lcp_'.$lot->getId(), json_encode($lotBetData));
                        }

                        $form = $this->createForm('AppBundle\Form\BetType', $bet, ['lot'=>$lot]);
                        $_forms[ $lot->getId() ] = $form->createView();

                        $em->flush();
                        
                        return new JsonResponse([    'result'=>true
                                                    ,'price'=>$lot->getPrice() . ''
                                                    ,'bet'=>($lot->getPrice() - $lot->getRouteId()->getTradeStep())
                                                    ,'prolongation'=>$prolongation*60//seconds
                        ]);
                    }

                    return new JsonResponse(['result'=>false]);
                }

            }
        }
        else{
            $_lots = [];
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
     * @Route("/auctionSetFilter", name="setFilter")
     */
    public function auctionSetFilter(Request $request){
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

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
     * @Route("/auctionUnsetFilter", name="unSetFilter")
     */
    public function auctionUnsetFilter(Request $request){
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        $filter = $em
            ->getRepository('AppBundle:Filter')
            ->createQueryBuilder('f')
            ->where('f.uid = '.$this->getUser()->getId())
            ->andWhere('f.type = 0')
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