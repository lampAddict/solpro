<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RoutesController extends Controller
{
    /**
     * Composer filter condition for auction page based on user preferences
     *
     * @param object $_filters array which contains json encoded user preferences
     * @return string $where composed condition
     */
    private function makeFilterCondition( $_filters ){
        $where = "r.carrier = '".$this->getUser()->getCarrierId1C()."'";

        $filters = (array) $_filters;
        if( !empty($filters) ){

            if(    $_filters->status_active
                && $_filters->status_active == 1
            ){
                $where .= ' AND (r.status != \'0. Аннулирован\' AND r.status != \'Закрыт\')';
            }

            if(    $_filters->status_planned
                && $_filters->status_planned == 1
            ){
                if( $where != 'r.user_id = '.$this->getUser()->getId() ){
                    $where = 'r.user_id = '.$this->getUser()->getId();
                }
                else{
                    $where .= ' AND (r.status = \'0. Аннулирован\' OR r.status = \'Закрыт\')';
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

            if( $_filters->driver_assigned ){
                if( $_filters->driver_assigned == 1 ){
                    $where .= ' AND r.driver_id IS NOT NULL';
                }
                else{
                    $where .= ' AND r.driver_id IS NULL';
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
        $_routes = $em
            ->getRepository('AppBundle:Route')
            ->createQueryBuilder('r')
            ->where('r.user_id = '.$this->getUser()->getId())
            ->getQuery()
            ->getResult();
        if( !empty($_routes) ){
            /* @var $_route \AppBundle\Entity\Route */
            foreach( $_routes as $_route ) {

                //fill regions data array
                if (!isset($_regionsFrom[$_route->getRegionFrom()])) {
                    $_regionsFrom[$_route->getRegionFrom()] = 1;
                }

                if (!isset($_regionsTo[$_route->getRegionTo()])) {
                    $_regionsTo[$_route->getRegionTo()] = 1;
                }
            }

            ksort($_regionsFrom);
            ksort($_regionsTo);
        }

        return ['from'=>array_keys($_regionsFrom), 'to'=>array_keys($_regionsTo)];
    }

    /**
     * @Route("/routes", name="routes")
     */
    public function indexAction(Request $request)
    {
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted('ROLE_ROUTES');

        $em = $this->getDoctrine()->getManager();

        //get user routes filter preferences
        $filters = [];

        $_filters = $em
            ->getRepository('AppBundle:Filter')
            ->createQueryBuilder('f')
            ->where('f.uid = '.$this->getUser()->getId())
            ->andWhere('f.type = 1')//routes filter type
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        if( !empty($_filters) ){
            $filters = json_decode($_filters[0]->getParams());
        }

        $where = $this->makeFilterCondition( $filters );

        $routes = $em
            ->getRepository('AppBundle:Route')
            ->createQueryBuilder('r')
            ->leftJoin(
                'AppBundle\Entity\Driver',
                'd',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'r.driver_id = d.id'
            )
            ->leftJoin(
                'AppBundle\Entity\Transport',
                't',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'r.vehicle_id = t.id'
            )
            ->where( $where )
            ->orderBy('r.loadDate', 'ASC')
            ->getQuery()
            ->getResult();

        $drivers = $em
            ->getRepository('AppBundle:Driver')
            ->createQueryBuilder('d')
            ->where('d.status = 1 AND d.user_id = '.$this->getUser()->getId())
            ->getQuery()
            ->getResult();

        $vehicles = $em
            ->getRepository('AppBundle:Transport')
            ->createQueryBuilder('t')
            ->where('t.status = 1 AND t.user_id = '.$this->getUser()->getId())
            ->getQuery()
            ->getResult();

        return $this->render('routesPage.html.twig', array(
             'routes' => $routes
            ,'drivers' => $drivers
            ,'vehicles' => $vehicles
            ,'filters' => $filters
            ,'regions' => $this->getSenderDeliveryRegionsLists()
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

        $this->denyAccessUnlessGranted('ROLE_ROUTES');

        $em = $this->getDoctrine()->getManager();
        /* @var $route \AppBundle\Entity\Route */
        $route = $em->getRepository('AppBundle:Route')->findOneBy(['id'=>intval($request->request->get('route')), 'user_id'=>$this->getUser()->getId()]);
        if(    $route
            && in_array($route->getStatus(), ['1. Создан', '3. Утвержден', '4. В комплектации'])
        ){
            /* @var $driver \AppBundle\Entity\Driver */
            $driver = $em->getRepository('AppBundle:Driver')->findOneBy(['id'=>intval($request->request->get('driver')), 'user_id'=>$this->getUser()->getId()]);
            if( $driver ){
                /* @var $vehicle \AppBundle\Entity\Transport */
                $vehicle = $em->getRepository('AppBundle:Transport')->findOneBy(['id'=>intval($request->request->get('vehicle')), 'user_id'=>$this->getUser()->getId()]);
                if( $vehicle ){

                    $route->setDriverId($driver);
                    $route->setVehicleId($vehicle);
                    $route->setUpdatedAt( new \DateTime(date('c', time())) );

                    $driver->setUpdatedAt( new \DateTime(date('c', time())) );

                    $vehicle->setUpdatedAt( new \DateTime(date('c', time())) );

                    $em->flush();
                    return new JsonResponse(['result'=>true]);
                }
                else{
                }
            }
        }

        return new JsonResponse(['result'=>false]);
    }

    /**
     * @Route("/removeDriver", name="removeDriver")
     */
    public function removeDriverAction(Request $request){
        //Check if user authenticated
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted('ROLE_ROUTES');

        $em = $this->getDoctrine()->getManager();
        /* @var $route \AppBundle\Entity\Route */
        $route = $em->getRepository('AppBundle:Route')->findOneBy(['id'=>intval($request->request->get('route')), 'user_id'=>$this->getUser()->getId()]);
        if(    $route 
            && in_array($route->getStatus(), ['1. Создан', '3. Утвержден', '4. В комплектации'])
        ){

            $route->setDriverId(null);
            $route->setVehicleId(null);
            $route->setUpdatedAt( new \DateTime(date('c', time())) );

            $em->flush();

            return new JsonResponse(['result'=>true]);
        }

        return new JsonResponse(['result'=>false]);
    }
}