<?php

namespace AppBundle\Services;

use AppBundle\Entity\Lot;

class import1CDataService{

    public function import1CData(array $data){

        if( !empty($data['lots']) ){
            foreach ($data['lots'] as $lot){
                //$lot = new Lot();
                var_dump($lot);
            }
        }


        echo 'Data import done';
    }
}