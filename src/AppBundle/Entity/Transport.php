<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="transport")
 */
class Transport
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $name;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $type;

    /**
     * @ORM\Column(type="string", length=10)
     */
    protected $payload;

    /**
     * @ORM\Column(type="string", length=30)
     */
    protected $regNum;

    /**
     * @ORM\Column(type="string", length=30, nullable=true, options={"default" = ""})
     */
    protected $trailerRegNum = null;

    /**
     * @ORM\Column(type="smallint", options={"default" = "1"})
     */
    protected $status;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="transport")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user_id;

    /**
     * One Vehicle has Many Routes.
     * @ORM\OneToMany(targetEntity="Route", mappedBy="vehicle_id")
     */
    protected $route_id;

    public function __toString() {
        return $this->name;
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
     * Set name
     *
     * @param string $name
     * @return Transport
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
     * Set type
     *
     * @param string $type
     * @return Transport
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set payload
     *
     * @param integer $payload
     * @return Transport
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Get payload
     *
     * @return integer 
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Set regNum
     *
     * @param string $regNum
     * @return Transport
     */
    public function setRegNum($regNum)
    {
        $this->regNum = $regNum;

        return $this;
    }

    /**
     * Get regNum
     *
     * @return string 
     */
    public function getRegNum()
    {
        return $this->regNum;
    }

    /**
     * Set trailerRegNum
     *
     * @param string $trailerRegNum
     * @return Transport
     */
    public function setTrailerRegNum($trailerRegNum)
    {
        $this->trailerRegNum = $trailerRegNum;

        return $this;
    }

    /**
     * Get trailerRegNum
     *
     * @return string 
     */
    public function getTrailerRegNum()
    {
        return $this->trailerRegNum;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return Transport
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set user_id
     *
     * @param \AppBundle\Entity\User $userId
     * @return Transport
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
     * Set driver_id
     *
     * @param \AppBundle\Entity\Driver $driverId
     * @return Transport
     */
    public function setDriverId(\AppBundle\Entity\Driver $driverId = null)
    {
        $this->driver_id = $driverId;

        return $this;
    }

    /**
     * Get driver_id
     *
     * @return \AppBundle\Entity\Driver 
     */
    public function getDriverId()
    {
        return $this->driver_id;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->route_id = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add routeId
     *
     * @param \AppBundle\Entity\Route $routeId
     *
     * @return Transport
     */
    public function addRouteId(\AppBundle\Entity\Route $routeId)
    {
        $this->route_id[] = $routeId;

        return $this;
    }

    /**
     * Remove routeId
     *
     * @param \AppBundle\Entity\Route $routeId
     */
    public function removeRouteId(\AppBundle\Entity\Route $routeId)
    {
        $this->route_id->removeElement($routeId);
    }

    /**
     * Get routeId
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRouteId()
    {
        return $this->route_id;
    }
}
