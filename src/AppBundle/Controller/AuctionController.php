<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Bet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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

        $qb = $em
            ->getRepository('AppBundle:Lot')
            ->createQueryBuilder('l');

        $lots = $qb
            ->leftJoin('l.routeId', 'r')
            /*
            ->leftJoin(
                'AppBundle\Entity\Bet',
                'b',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'l.id = b.lot_id'
            )
            */
            ->Where('l.auctionStatus = 1')
            /*
            ->andWhere(
                $qb->expr()->lte('l.startDate', 'CURRENT_TIMESTAMP()')
            )
            */
            ->getQuery()
            ->getResult();

        $forms = [];
        foreach( $lots as $lot ){
            $bet = new Bet();
            
            $bet->setLotId($lot->getId());
            
            $form = $this->createForm('AppBundle\Form\BetType', $bet, ['lot'=>$lot]);

            $form->handleRequest($request);
            if(    $form->isSubmitted()
                && $form->isValid()
                && intval($request->request->get('appbundle_bet')['lot_id']) == $lot->getId()
            ){
                //check if user authenticated
                if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
                    throw $this->createAccessDeniedException();
                }
                //$user = $this->get('security.token_storage')->getToken()->getUser();

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
                    && ($lot->getStartDate()->getTimestamp() + $lot->getDuration()) < time()
                ){
                    $bet->setValue( intval($request->request->get('appbundle_bet')['value']) );
                    $lot->setPrice( intval($request->request->get('appbundle_bet')['value']) );

                    $em->persist($bet);
                    $em->persist($lot);

                    $em->flush();
                    
                    $redis->set('lcp_'.$lot->getId(), json_encode(['price'=>$lot->getPrice(), 'owner'=>$this->getUser()->getId()]));
                    //$this->get('memcache.default')->set('lcp_'.$lot->getId(), $lot->getPrice(), 0, 1*60*60);
                }
            }

            $forms[ $lot->getId() ] = $form->createView();
        }
        
        return $this->render('auctionPage.html.twig', array(
             'lots' => $lots
            ,'forms' => $forms
        ));
    }
}