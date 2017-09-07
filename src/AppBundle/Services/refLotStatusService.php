<?php

namespace AppBundle\Services;

class refLotStatusService
{
    protected $em;
    protected $routeStatusByPid;

    public function __construct($entityManager)
    {
        $this->em = $entityManager;
        $this->routeStatusByPid = [];
    }

    public function refLotStatusService(){
    }

    public function getLotStatuses(){
        $rlsArr = $this->em->getRepository('AppBundle:RefLotStatus')->findAll();
        /* @var $rls \AppBundle\Entity\RefLotStatus */
        foreach ($rlsArr as $rls){
            $this->routeStatusByPid[ $rls->getPid() ] = $rls->getId1C();
        }

        return $this->routeStatusByPid;
    }
}