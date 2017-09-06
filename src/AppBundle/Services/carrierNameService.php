<?php

namespace AppBundle\Services;

class carrierNameService
{
    protected $em;
    protected $ts;

    public function __construct($entityManager, $tokenStorage)
    {
        $this->em = $entityManager;
        $this->ts = $tokenStorage;
    }

    public function carrierNameService(){
    }

    public function getCarrierName(){
        $userId = null;
        if( $this->ts ){
            $user =  $this->ts->getToken()->getUser();
            if( $user ){
                $userId = $user->getId();
            }
        }

        if( !is_null($userId) ){
            //get carrier name of current user
            $sql = "SELECT rc.name FROM refcarrier as rc LEFT JOIN fos_user u ON u.carrier_id1c = rc.id1c WHERE u.id = '$userId'";
            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->execute();
            $carrierName = $stmt->fetchAll();

            return (!empty($carrierName) ? $carrierName[0]['name'] : '');
        }

        return '';
    }

    public function __toString()
    {
        return $this->getCarrierName();
    }
}