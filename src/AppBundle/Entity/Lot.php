<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="lot")
 */
class Lot
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $id1C;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $statusId1c;

    /**
     * @ORM\Column(type="datetimetz")
     */
    protected $startDate;

    /**
     * @ORM\Column(type="integer")
     */
    protected $duration;

    /**
     * One Lot has One Route.
     * @ORM\OneToOne(targetEntity="Route", inversedBy="lot_id")
     * @ORM\JoinColumn(name="route_id", referencedColumnName="id")
     */
    protected $routeId;

    /**
     * @ORM\Column(type="integer")
     */
    protected $price;
    /*
    /**
     * One Lot has Many Bets.
     * @ORM\OneToMany(targetEntity="Bet", mappedBy="lot_id")
     */
    //protected $bet;
    
    /**
     * @ORM\Column(type="datetimetz")
     */
    protected $created_at;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $auctionStatus;

    /**
     * @ORM\Column(type="datetimetz")
     */
    protected $updated_at;

    public function __toString() {
        return (string)$this->id;
    }
    
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
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Lot
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     * @return Lot
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer 
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set prolong
     *
     * @param integer $prolong
     * @return Lot
     */
    public function setProlong($prolong)
    {
        $this->prolong = $prolong;

        return $this;
    }

    /**
     * Get prolong
     *
     * @return integer 
     */
    public function getProlong()
    {
        return $this->prolong;
    }

    /**
     * Set routeId
     *
     * @param integer $routeId
     * @return Lot
     */
    public function setRouteId($routeId)
    {
        $this->routeId = $routeId;

        return $this;
    }

    /**
     * Get routeId
     *
     * @return integer 
     */
    public function getRouteId()
    {
        return $this->routeId;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Lot
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set id1C
     *
     * @param string $id1C
     * @return Lot
     */
    public function setId1C($id1C)
    {
        $this->id1C = $id1C;

        return $this;
    }

    /**
     * Get id1C
     *
     * @return string 
     */
    public function getId1C()
    {
        return $this->id1C;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->bet = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add bet
     *
     * @param \AppBundle\Entity\Bet $bet
     * @return Lot
     */
    public function addBet(\AppBundle\Entity\Bet $bet)
    {
        $this->bet[] = $bet;

        return $this;
    }

    /**
     * Remove bet
     *
     * @param \AppBundle\Entity\Bet $bet
     */
    public function removeBet(\AppBundle\Entity\Bet $bet)
    {
        $this->bet->removeElement($bet);
    }

    /**
     * Get bet
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getBet()
    {
        return $this->bet;
    }

    /**
     * Set price
     *
     * @param integer $price
     * @return Lot
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set auctionStatus
     *
     * @param integer $auctionStatus
     * @return Lot
     */
    public function setAuctionStatus($auctionStatus)
    {
        $this->auctionStatus = $auctionStatus;

        return $this;
    }

    /**
     * Get auctionStatus
     *
     * @return integer 
     */
    public function getAuctionStatus()
    {
        return $this->auctionStatus;
    }

    /**
     * Set statusId1c
     *
     * @param string $statusId1c
     *
     * @return Lot
     */
    public function setStatusId1c($statusId1c)
    {
        $this->statusId1c = $statusId1c;

        return $this;
    }

    /**
     * Get statusId1c
     *
     * @return string
     */
    public function getStatusId1c()
    {
        return $this->statusId1c;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Lot
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
}
