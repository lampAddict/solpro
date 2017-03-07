<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="driver")
 */
class Driver
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="smallint", options={"default" = "1"})
     */
    protected $status;

    /**
     * @ORM\Column(type="string", length=512)
     */
    protected $fio;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $phone;

    /**
     * @ORM\ManyToOne(targetEntity="RefPassport", inversedBy="driver")
     * @ORM\JoinColumn(name="passport_type", referencedColumnName="id")
     */
    protected $passport_type;
    
    /**
     * @ORM\Column(type="string", length=10)
     */
    protected $passport_series;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $passport_number;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $passport_date_issue;

    /**
     * @ORM\Column(type="text")
     */
    protected $passport_issued_by;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $driverLicense;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="driver")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user_id;

    /**
     * One Driver has Many Routes.
     * @ORM\OneToMany(targetEntity="Route", mappedBy="driver_id")
     */
    protected $route_id;

    /**
     * @ORM\Column(type="datetimetz")
     */
    protected $updated_at;
    
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
     * @param integer $status
     * @return Driver
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
     * Set fio
     *
     * @param string $fio
     * @return Driver
     */
    public function setFio($fio)
    {
        $this->fio = $fio;

        return $this;
    }

    /**
     * Get fio
     *
     * @return string 
     */
    public function getFio()
    {
        return $this->fio;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return Driver
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set passport
     *
     * @param string $passport
     * @return Driver
     */
    public function setPassport($passport)
    {
        $this->passport = $passport;

        return $this;
    }

    /**
     * Get passport
     *
     * @return string 
     */
    public function getPassport()
    {
        return $this->passport;
    }

    /**
     * Set driverLicense
     *
     * @param string $driverLicense
     * @return Driver
     */
    public function setDriverLicense($driverLicense)
    {
        $this->driverLicense = $driverLicense;

        return $this;
    }

    /**
     * Get driverLicense
     *
     * @return string 
     */
    public function getDriverLicense()
    {
        return $this->driverLicense;
    }

    /**
     * Set user_id
     *
     * @param \AppBundle\Entity\User $userId
     * @return Driver
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
     * Set route_id
     *
     * @param \AppBundle\Entity\Route $routeId
     * @return Driver
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
     * @return Driver
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
     * Set passportSeries
     *
     * @param string $passportSeries
     *
     * @return Driver
     */
    public function setPassportSeries($passportSeries)
    {
        $this->passport_series = $passportSeries;

        return $this;
    }

    /**
     * Get passportSeries
     *
     * @return string
     */
    public function getPassportSeries()
    {
        return $this->passport_series;
    }

    /**
     * Set passportNumber
     *
     * @param string $passportNumber
     *
     * @return Driver
     */
    public function setPassportNumber($passportNumber)
    {
        $this->passport_number = $passportNumber;

        return $this;
    }

    /**
     * Get passportNumber
     *
     * @return string
     */
    public function getPassportNumber()
    {
        return $this->passport_number;
    }

    /**
     * Set passportDateIssue
     *
     * @param string $passportDateIssue
     *
     * @return Driver
     */
    public function setPassportDateIssue($passportDateIssue)
    {
        $this->passport_date_issue = $passportDateIssue;

        return $this;
    }

    /**
     * Get passportDateIssue
     *
     * @return string
     */
    public function getPassportDateIssue()
    {
        return $this->passport_date_issue;
    }

    /**
     * Set passportIssuedBy
     *
     * @param string $passportIssuedBy
     *
     * @return Driver
     */
    public function setPassportIssuedBy($passportIssuedBy)
    {
        $this->passport_issued_by = $passportIssuedBy;

        return $this;
    }

    /**
     * Get passportIssuedBy
     *
     * @return string
     */
    public function getPassportIssuedBy()
    {
        return $this->passport_issued_by;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Driver
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

    /**
     * Set passportType
     *
     * @param integer $passportType
     *
     * @return Driver
     */
    public function setPassportType($passportType)
    {
        $this->passport_type = $passportType;

        return $this;
    }

    /**
     * Get passportType
     *
     * @return integer
     */
    public function getPassportType()
    {
        return $this->passport_type;
    }
}
