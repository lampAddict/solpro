<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="refvehicletype",indexes={@ORM\Index(name="searchBy1Cid", columns={"id1c"})})
 */
class RefVehicleType
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
     * One RefVehicleType has Many Transport vehicles.
     * @ORM\OneToMany(targetEntity="Transport", mappedBy="type")
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
     * @return RefVehicleType
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
     * @return RefVehicleType
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
     * @return RefVehicleType
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
