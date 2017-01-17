<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class RoutesController extends Controller
{
    /**
     * @Route("/routes", name="routes")
     */
    public function indexAction(Request $request)
    {
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        $routes = $em
            ->getRepository('AppBundle:Route')
            ->createQueryBuilder('r')
            ->where('r.user_id = '.$this->getUser()->getId())
            ->getQuery()
            ->getResult();

        return $this->render('routesPage.html.twig', array(
            'routes' => $routes
        ));
    }
}