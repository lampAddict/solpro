<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="exchange")
 */
class Exchange
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $sendNum;

    /**
     * @ORM\Column(type="integer")
     */
    protected $recNum;

    /**
     * @ORM\Column(type="datetimetz")
     */
    protected $dateExchange;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set sendNum
     *
     * @param integer $sendNum
     *
     * @return Exchange
     */
    public function setSendNum($sendNum)
    {
        $this->sendNum = $sendNum;

        return $this;
    }

    /**
     * Get sendNum
     *
     * @return integer
     */
    public function getSendNum()
    {
        return $this->sendNum;
    }

    /**
     * Set recNum
     *
     * @param integer $recNum
     *
     * @return Exchange
     */
    public function setRecNum($recNum)
    {
        $this->recNum = $recNum;

        return $this;
    }

    /**
     * Get recNum
     *
     * @return integer
     */
    public function getRecNum()
    {
        return $this->recNum;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Exchange
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set dateExchange
     *
     * @param \DateTime $dateExchange
     *
     * @return Exchange
     */
    public function setDateExchange($dateExchange)
    {
        $this->dateExchange = $dateExchange;

        return $this;
    }

    /**
     * Get dateExchange
     *
     * @return \DateTime
     */
    public function getDateExchange()
    {
        return $this->dateExchange;
    }
}
