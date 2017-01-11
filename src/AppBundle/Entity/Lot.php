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
    protected $status;

    /**
     * @ORM\Column(type="datetimetz")
     */
    protected $startDate;

    /**
     * @ORM\Column(type="integer")
     */
    protected $duration;

    /**
     * @ORM\Column(type="integer")
     */
    protected $prolong;

    /**
     * One Lot has One Route.
     * @ORM\OneToOne(targetEntity="Route", inversedBy="lot_id")
     * @ORM\JoinColumn(name="route_id", referencedColumnName="id")
     */
    protected $routeId;

    /**
     * @ORM\Column(type="datetimetz")
     */
    protected $created_at;

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
     * Set status
     *
     * @param string $status
     * @return Lot
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
}
