<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Bet;
use AppBundle\Entity\Filter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AuctionController extends Controller
{
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

        $lots = $em
            ->getRepository('AppBundle:Lot')
            ->createQueryBuilder('l')
            ->leftJoin('l.routeId', 'r')
            ->where('l.auctionStatus = 1')
            ->orderBy('l.startDate')
            ->getQuery()
            ->getResult();

        $forms = [];
        if( !empty($lots) ){
            foreach( $lots as $lot ){

                $bet = new Bet();
                $bet->setLotId($lot->getId());
                
                $form = $this->createForm('AppBundle\Form\BetType', $bet, ['lot'=>$lot]);
                $forms[ $lot->getId() ] = $form->createView();

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

                        //auction prolongation if bet was made during last minute
                        if( $lot->getStartDate()->getTimestamp() + $lot->getDuration()*60 - time() < 2*60 ){
                            $lot->setDuration( $lot->getDuration() + 2 );
                        }
                        
                        $em->persist($bet);
                        $em->persist($lot);
                        
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
                        $forms[ $lot->getId() ] = $form->createView();

                        $em->flush();

                        return new JsonResponse(
                                                [    'result'=>true
                                                    ,'price'=>$lot->getPrice() . ''
                                                    ,'bet'=>($lot->getPrice() - $lot->getRouteId()->getTradeStep())
                                                ]
                        );
                    }

                    return new JsonResponse(['result'=>false]);
                }

            }
        }
        else{
            $lots = [];
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


        //get user filter settings
        $_filters = [];
        $filters = $em
            ->getRepository('AppBundle:Filter')
            ->createQueryBuilder('f')
            ->where('f.uid = '.$this->getUser()->getId())
            ->andWhere('f.type = 0')
            ->getQuery()
            ->getResult();

        if( !empty($filters) ){
            foreach( $filters as $filter ){
                /* @var $filter \AppBundle\Entity\Filter */
                $_filters = json_decode($filter->getParams());
            }
        }

        return $this->render('auctionPage.html.twig', array(
             'lots' => $lots
            ,'forms' => $forms
            ,'bets' => $_bets
            ,'filters'=> $_filters
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