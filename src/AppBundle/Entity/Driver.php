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
     * @ORM\Column(type="text")
     */
    protected $passport;

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
     * One Driver has One Vehicle (Transport).
     * @ORM\OneToOne(targetEntity="Transport", mappedBy="driver_id")
     */
    protected $transport_id;

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
     * Set transport_id
     *
     * @param \AppBundle\Entity\Transport $transportId
     * @return Driver
     */
    public function setTransportId(\AppBundle\Entity\Transport $transportId = null)
    {
        $this->transport_id = $transportId;

        return $this;
    }

    /**
     * Get transport_id
     *
     * @return \AppBundle\Entity\Transport 
     */
    public function getTransportId()
    {
        return $this->transport_id;
    }
}
