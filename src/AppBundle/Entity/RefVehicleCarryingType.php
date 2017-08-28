<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="refvehiclecarryingtype",indexes={@ORM\Index(name="searchBy1Cid", columns={"id1c"})})
 */
class RefVehicleCarryingType
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
     * @ORM\Column(type="string", length=150)
     */
    protected $name;

    /**
     * One RefVehicleCarryingType has Many Transport vehicles.
     * @ORM\OneToMany(targetEntity="Transport", mappedBy="payload")
     */
    protected $transport;

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
     * @return RefVehicleCarryingType
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
     * @return RefVehicleCarryingType
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
        $this->transport = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add transport
     *
     * @param \AppBundle\Entity\Transport $transport
     *
     * @return RefVehicleCarryingType
     */
    public function addTransport(\AppBundle\Entity\Transport $transport)
    {
        $this->transport[] = $transport;

        return $this;
    }

    /**
     * Remove transport
     *
     * @param \AppBundle\Entity\Transport $transport
     */
    public function removeTransport(\AppBundle\Entity\Transport $transport)
    {
        $this->transport->removeElement($transport);
    }

    /**
     * Get transport
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTransport()
    {
        return $this->transport;
    }
}
