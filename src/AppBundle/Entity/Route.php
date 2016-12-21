<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="route")
 */
class Route
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
     * @ORM\Column(type="datetimetz")
     */
    protected $loadDate;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $routeDirectAssign;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $code;
    
    /**
     * @ORM\Column(type="string", length=256)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=256)
     */
    protected $status;

    /**
     * @ORM\Column(type="string", length=256)
     */
    protected $carrier;

    /**
     * @ORM\Column(type="string", length=256)
     */
    protected $regionFrom;

    /**
     * @ORM\Column(type="string", length=256)
     */
    protected $regionTo;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $vehicleType;

    /**
     * @ORM\Column(type="string", length=10)
     */
    protected $vehiclePayload;

    /**
     * @ORM\Column(type="string", length=30)
     */
    protected $vehicleRegNumber;

    /**
     * @ORM\Column(type="integer")
     */
    protected $tradeCost;
    /**
     * @ORM\Column(type="integer")
     */
    protected $tradeStep;
    /**
     * @ORM\Column(type="integer")
     */
    protected $cargoWeight;
    /**
     * @ORM\Column(type="integer")
     */
    protected $cargoCount;
    /**
     * @ORM\Column(type="text")
     */
    protected $comment;
    /**
     * @ORM\Column(type="smallint")
     */
    protected $executionCost;

    //one route - many orders
    /**
     * One Route has Many Orders.
     * @ORM\OneToMany(targetEntity="Order", mappedBy="route_id")
     */
    protected $orders;

    //one user - many routes
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="route")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user_id;

    //one route - one driver
    /**
     * One Route has One Driver.
     * @ORM\OneToOne(targetEntity="Driver", mappedBy="route_id")
     */
    protected $vehicleDriver;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->orders = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set id1C
     *
     * @param string $id1C
     * @return Route
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
     * Set loadDate
     *
     * @param \DateTime $loadDate
     * @return Route
     */
    public function setLoadDate($loadDate)
    {
        $this->loadDate = $loadDate;

        return $this;
    }

    /**
     * Get loadDate
     *
     * @return \DateTime 
     */
    public function getLoadDate()
    {
        return $this->loadDate;
    }

    /**
     * Set routeDirectAssign
     *
     * @param integer $routeDirectAssign
     * @return Route
     */
    public function setRouteDirectAssign($routeDirectAssign)
    {
        $this->routeDirectAssign = $routeDirectAssign;

        return $this;
    }

    /**
     * Get routeDirectAssign
     *
     * @return integer 
     */
    public function getRouteDirectAssign()
    {
        return $this->routeDirectAssign;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Route
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Route
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Route
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set carrier
     *
     * @param string $carrier
     * @return Route
     */
    public function setCarrier($carrier)
    {
        $this->carrier = $carrier;

        return $this;
    }

    /**
     * Get carrier
     *
     * @return string 
     */
    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * Set regionFrom
     *
     * @param string $regionFrom
     * @return Route
     */
    public function setRegionFrom($regionFrom)
    {
        $this->regionFrom = $regionFrom;

        return $this;
    }

    /**
     * Get regionFrom
     *
     * @return string 
     */
    public function getRegionFrom()
    {
        return $this->regionFrom;
    }

    /**
     * Set regionTo
     *
     * @param string $regionTo
     * @return Route
     */
    public function setRegionTo($regionTo)
    {
        $this->regionTo = $regionTo;

        return $this;
    }

    /**
     * Get regionTo
     *
     * @return string 
     */
    public function getRegionTo()
    {
        return $this->regionTo;
    }

    /**
     * Set vehicleType
     *
     * @param integer $vehicleType
     * @return Route
     */
    public function setVehicleType($vehicleType)
    {
        $this->vehicleType = $vehicleType;

        return $this;
    }

    /**
     * Get vehicleType
     *
     * @return integer 
     */
    public function getVehicleType()
    {
        return $this->vehicleType;
    }

    /**
     * Set vehiclePayload
     *
     * @param string $vehiclePayload
     * @return Route
     */
    public function setVehiclePayload($vehiclePayload)
    {
        $this->vehiclePayload = $vehiclePayload;

        return $this;
    }

    /**
     * Get vehiclePayload
     *
     * @return string 
     */
    public function getVehiclePayload()
    {
        return $this->vehiclePayload;
    }

    /**
     * Set vehicleRegNumber
     *
     * @param string $vehicleRegNumber
     * @return Route
     */
    public function setVehicleRegNumber($vehicleRegNumber)
    {
        $this->vehicleRegNumber = $vehicleRegNumber;

        return $this;
    }

    /**
     * Get vehicleRegNumber
     *
     * @return string 
     */
    public function getVehicleRegNumber()
    {
        return $this->vehicleRegNumber;
    }

    /**
     * Set tradeCost
     *
     * @param integer $tradeCost
     * @return Route
     */
    public function setTradeCost($tradeCost)
    {
        $this->tradeCost = $tradeCost;

        return $this;
    }

    /**
     * Get tradeCost
     *
     * @return integer 
     */
    public function getTradeCost()
    {
        return $this->tradeCost;
    }

    /**
     * Set tradeStep
     *
     * @param integer $tradeStep
     * @return Route
     */
    public function setTradeStep($tradeStep)
    {
        $this->tradeStep = $tradeStep;

        return $this;
    }

    /**
     * Get tradeStep
     *
     * @return integer 
     */
    public function getTradeStep()
    {
        return $this->tradeStep;
    }

    /**
     * Set cargoWeight
     *
     * @param integer $cargoWeight
     * @return Route
     */
    public function setCargoWeight($cargoWeight)
    {
        $this->cargoWeight = $cargoWeight;

        return $this;
    }

    /**
     * Get cargoWeight
     *
     * @return integer 
     */
    public function getCargoWeight()
    {
        return $this->cargoWeight;
    }

    /**
     * Set cargoCount
     *
     * @param integer $cargoCount
     * @return Route
     */
    public function setCargoCount($cargoCount)
    {
        $this->cargoCount = $cargoCount;

        return $this;
    }

    /**
     * Get cargoCount
     *
     * @return integer 
     */
    public function getCargoCount()
    {
        return $this->cargoCount;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return Route
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set executionCost
     *
     * @param integer $executionCost
     * @return Route
     */
    public function setExecutionCost($executionCost)
    {
        $this->executionCost = $executionCost;

        return $this;
    }

    /**
     * Get executionCost
     *
     * @return integer 
     */
    public function getExecutionCost()
    {
        return $this->executionCost;
    }

    /**
     * Add orders
     *
     * @param \AppBundle\Entity\Order $orders
     * @return Route
     */
    public function addOrder(\AppBundle\Entity\Order $orders)
    {
        $this->orders[] = $orders;

        return $this;
    }

    /**
     * Remove orders
     *
     * @param \AppBundle\Entity\Order $orders
     */
    public function removeOrder(\AppBundle\Entity\Order $orders)
    {
        $this->orders->removeElement($orders);
    }

    /**
     * Get orders
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * Set user_id
     *
     * @param \AppBundle\Entity\User $userId
     * @return Route
     */
    public function setUserId(\AppBundle\Entity\User $userId = null)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get user_id
     *
     * @return \AppBundle\Entity\User 
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set vehicleDriver
     *
     * @param \AppBundle\Entity\Driver $vehicleDriver
     * @return Route
     */
    public function setVehicleDriver(\AppBundle\Entity\Driver $vehicleDriver = null)
    {
        $this->vehicleDriver = $vehicleDriver;

        return $this;
    }

    /**
     * Get vehicleDriver
     *
     * @return \AppBundle\Entity\Driver 
     */
    public function getVehicleDriver()
    {
        return $this->vehicleDriver;
    }
}
