<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="orders")
 */
class Order
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
     * @ORM\Column(type="string", length=20)
     */
    protected $code;

    /**
     * @ORM\Column(type="datetimetz")
     */
    protected $date;

    /**
     * @ORM\Column(type="string", length=300)
     */
    protected $consignee;

    /**
     * @ORM\Column(type="text")
     */
    protected $unloadAddress;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $weight;

    /**
     * @ORM\Column(type="smallint")
     */
    protected $countNum;

    /**
     * @ORM\Column(type="text")
     */
    protected $loadSpecialConditions;

    /**
     * @ORM\Column(type="text")
     */
    protected $unloadSpecialConditions;


    /**
     * @ORM\Column(type="string", length=400)
     */
    protected $manager;

    /**
     * @ORM\Column(type="text")
     */
    protected $comment;

    //many orders - one route
    /**
     * @ORM\ManyToOne(targetEntity="Route", inversedBy="orders")
     * @ORM\JoinColumn(name="route_id", referencedColumnName="id")
     */
    protected $route_id;

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
     * @return Order
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
     * Set code
     *
     * @param string $code
     * @return Order
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
     * Set date
     *
     * @param \DateTime $date
     * @return Order
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
     * Set consignee
     *
     * @param string $consignee
     * @return Order
     */
    public function setConsignee($consignee)
    {
        $this->consignee = $consignee;

        return $this;
    }

    /**
     * Get consignee
     *
     * @return string 
     */
    public function getConsignee()
    {
        return $this->consignee;
    }

    /**
     * Set unloadAddress
     *
     * @param string $unloadAddress
     * @return Order
     */
    public function setUnloadAddress($unloadAddress)
    {
        $this->unloadAddress = $unloadAddress;

        return $this;
    }

    /**
     * Get unloadAddress
     *
     * @return string 
     */
    public function getUnloadAddress()
    {
        return $this->unloadAddress;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return Order
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer 
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set count
     *
     * @param integer $count
     * @return Order
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Get count
     *
     * @return integer 
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set loadSpecialConditions
     *
     * @param string $loadSpecialConditions
     * @return Order
     */
    public function setLoadSpecialConditions($loadSpecialConditions)
    {
        $this->loadSpecialConditions = $loadSpecialConditions;

        return $this;
    }

    /**
     * Get loadSpecialConditions
     *
     * @return string 
     */
    public function getLoadSpecialConditions()
    {
        return $this->loadSpecialConditions;
    }

    /**
     * Set unloadSpecialConditions
     *
     * @param integer $unloadSpecialConditions
     * @return Order
     */
    public function setUnloadSpecialConditions($unloadSpecialConditions)
    {
        $this->unloadSpecialConditions = $unloadSpecialConditions;

        return $this;
    }

    /**
     * Get unloadSpecialConditions
     *
     * @return integer 
     */
    public function getUnloadSpecialConditions()
    {
        return $this->unloadSpecialConditions;
    }

    /**
     * Set manager
     *
     * @param string $manager
     * @return Order
     */
    public function setManager($manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * Get manager
     *
     * @return string 
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return Order
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
     * Set route_id
     *
     * @param \AppBundle\Entity\Route $routeId
     * @return Order
     */
    public function setRouteId(\AppBundle\Entity\Route $routeId = null)
    {
        $this->route_id = $routeId;

        return $this;
    }

    /**
     * Get route_id
     *
     * @return \AppBundle\Entity\Route 
     */
    public function getRouteId()
    {
        return $this->route_id;
    }

    /**
     * Set countNum
     *
     * @param integer $countNum
     * @return Order
     */
    public function setCountNum($countNum)
    {
        $this->countNum = $countNum;

        return $this;
    }

    /**
     * Get countNum
     *
     * @return integer 
     */
    public function getCountNum()
    {
        return $this->countNum;
    }
}
