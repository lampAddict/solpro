<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="refPassport")
 */
class RefPassport
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
     * @ORM\Column(type="string", length=1024)
     */
    protected $name;

    /**
     * One RefPassport has Many Drivers.
     * @ORM\OneToMany(targetEntity="Driver", mappedBy="passport_type")
     */
    protected $driver;

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
     *
     * @return RefPassport
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
     * Set name
     *
     * @param string $name
     *
     * @return RefPassport
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
     * Constructor
     */
    public function __construct()
    {
        $this->driver = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add driver
     *
     * @param \AppBundle\Entity\Driver $driver
     *
     * @return RefPassport
     */
    public function addDriver(\AppBundle\Entity\Driver $driver)
    {
        $this->driver[] = $driver;

        return $this;
    }

    /**
     * Remove driver
     *
     * @param \AppBundle\Entity\Driver $driver
     */
    public function removeDriver(\AppBundle\Entity\Driver $driver)
    {
        $this->driver->removeElement($driver);
    }

    /**
     * Get driver
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDriver()
    {
        return $this->driver;
    }
}
