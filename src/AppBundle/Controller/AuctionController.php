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

        $em = $this->getDoctrine()->getManager();

        $lots = $em
            ->getRepository('AppBundle:Lot')
            ->createQueryBuilder('l')
            ->leftJoin('l.routeId', 'r')
            ->leftJoin('l.bet', 'b')
            ->getQuery()
            ->getResult();

        $forms = [];
        $user = $this->get('security.token_storage')->getToken()->getUser();
        foreach($lots as $lot){
            $bet = new Bet();
            
            $bet->setLotId($lot);
            $bet->setUserId($user);
            
            $form = $this->createForm('AppBundle\Form\BetType', $bet);

            $forms[ $lot->getId() ] = $form->createView();
        }
        
        return $this->render('auctionPage.html.twig', array(
            'lots' => $lots,
            'forms' => $forms,
        ));
    }
}