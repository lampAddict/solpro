<?php

namespace AppBundle\Services;

use Symfony\Component\Validator\Constraints\DateTime;

class export1CDataService
{
    protected $em;
    protected $um;

    public function __construct($entityManager, $userManager)
    {
        $this->em = $entityManager;
        $this->um = $userManager;
    }

    public function exportData($recNum){
        $auction_end_lots = $this->em->getRepository('AppBundle:Lot')->findBy(['auctionStatus'=>0]);
        if( !empty($auction_end_lots) ){

            $stmt = $this->em->getConnection()->prepare('SELECT send_num FROM exchange ORDER BY id DESC LIMIT 1');
            $stmt->execute();
            $sendNum = $stmt->fetchAll();
            $cdate = new \DateTime(date('c', time()));
            $xml = '<?xml version="1.0" encoding="UTF-8"?><messageFromPortal sendNumber="'.($sendNum[0]['send_num'] + 1).'" recNumber="'.$recNum.'" messageCreationTime="'.$cdate.'">';
            $xml .= '<lots>';
            foreach( $auction_end_lots as $lot){
                /* @var $lot \AppBundle\Entity\Lot */
                $xml .= '<lot><id>'.$lot->getId1C().'</id><statusId></statusId></lot>';
            }
            $xml .= '</lots></messageFromPortal>';

            file_put_contents('data/messageFromPortal.xml', $xml);
            return true;
        }
        
        return false;
    }
}
