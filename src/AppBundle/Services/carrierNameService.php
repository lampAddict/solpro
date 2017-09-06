<?php

namespace AppBundle\Services;

class carrierNameService
{
    protected $em;
    protected $ts;
    protected $name;

    public function __construct($entityManager, $tokenStorage)
    {
        $this->em    = $entityManager;
        $this->ts    = $tokenStorage;

        $this->name  = $this->carrierNameService();
    }

    public function carrierNameService(){
        //get carrier name of current user
        $sql = 'SELECT rc.name FROM refcarrier as rc LEFT JOIN fos_user u ON u.carrier_id1c = rc.id1c WHERE u.id = "'.$this->ts->getToken()->getUser()->getId().'"';
        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
        $carrierName = $stmt->fetchAll();

        return (is_array($carrierName)?$carrierName[0]['name']:'');
    }

    public function __toString()
    {
        return $this->name;
    }
}