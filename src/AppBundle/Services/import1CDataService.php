<?php

namespace AppBundle\Services;

use AppBundle\Entity\Lot;

class import1CDataService{

    protected $em;

    public function __construct($entityManager){
        $this->em = $entityManager;
    }

    public function import1CData(array $data){

        if( !empty($data['lots']) ){
            foreach ($data['lots'] as $lot){

                //var_dump($data['ref']['lotstatus'][ $lot['statusid'] ]);
                //die();

                $_lot = new Lot();
                $_lot->setStatus( $data['ref']['lotStatus'][ $lot['statusID'] ]['name'] );
                $_lot->setDuration( $lot['duration'] );
                $_lot->setProlong( $lot['prolong'] );
                $_lot->setStartDate( new \DateTime($lot['startDate']) );
                
                //$_lot->setRouteId();

                $this->em->persist($_lot);
                $this->em->flush($_lot);
                var_dump($_lot->getId());
            }
        }


        echo 'Data import done';
    }
}