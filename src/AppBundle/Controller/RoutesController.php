<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
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
            ->leftJoin(
                'AppBundle\Entity\Driver',
                'd',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'r.id = d.route_id'
            )
            ->where('r.user_id = '.$this->getUser()->getId())
            ->getQuery()
            ->getResult();

        $drivers = $em
            ->getRepository('AppBundle:Driver')
            ->createQueryBuilder('d')
            ->leftJoin('d.transport_id', 'v')
            ->where('d.route_id IS NULL AND d.user_id = '.$this->getUser()->getId())
            ->getQuery()
            ->getResult();

        $vehicles = $em
            ->getRepository('AppBundle:Transport')
            ->createQueryBuilder('t')
            ->where('t.driver_id IS NULL AND t.user_id = '.$this->getUser()->getId())
            ->getQuery()
            ->getResult();

        return $this->render('routesPage.html.twig', array(
             'routes' => $routes
            ,'drivers' => $drivers
            ,'vehicles' => $vehicles
        ));
    }

    /**
     * @Route("/attachDriver", name="attachDriver")
     */
    public function attachDriverAction(Request $request)
    {
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();
        /* @var $route \AppBundle\Entity\Route */
        $route = $em->getRepository('AppBundle:Route')->findOneBy(['id'=>intval($request->request->get('route')), 'user_id'=>$this->getUser()->getId()]);
        if( $route ){
            /* @var $driver \AppBundle\Entity\Driver */
            $driver = $em->getRepository('AppBundle:Driver')->findOneBy(['id'=>intval($request->request->get('driver')), 'user_id'=>$this->getUser()->getId(), 'route_id'=>null]);
            if( $driver ){
                /* @var $driverSameRoute \AppBundle\Entity\Driver */
                $driverSameRoute = $em->getRepository('AppBundle:Driver')->findOneBy(['user_id'=>$this->getUser()->getId(), 'route_id'=>$route->getId()]);

                /* @var $vehicle \AppBundle\Entity\Transport */
                $vehicle = $em->getRepository('AppBundle:Transport')->findOneBy(['id'=>intval($request->request->get('vehicle')), 'user_id'=>$this->getUser()->getId()]);
                if( $vehicle ){
                    if( is_null($vehicle->getDriverId()) ){
                        $vehicle->setDriverId($driver);
                        $em->persist($vehicle);

                        $driver->setRouteId($route);
                        $em->persist($driver);

                        if( $driverSameRoute ){
                            $driverSameRoute->setRouteId(null);
                            $em->persist($driverSameRoute);
                        }

                        $em->flush();
                        return new JsonResponse(['result'=>true]);
                    }
                }
                else{
                    if( !is_null($driver->getTransportId()) ){
                        $driver->setRouteId($route);
                        $em->persist($driver);

                        if( $driverSameRoute ){
                            $driverSameRoute->setRouteId(null);
                            $em->persist($driverSameRoute);
                        }

                        $em->flush();
                        return new JsonResponse(['result'=>true]);
                    }
                }
            }
        }

        return new JsonResponse(['result'=>false]);
    }
}