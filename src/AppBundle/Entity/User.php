<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use AppBundle\Entity\Transport as Transport;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * One User has Many Transports (Vehicles).
     * @ORM\OneToMany(targetEntity="Transport", mappedBy="user_id")
     */
    protected $transport;

    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

    /**
     * Add transport
     *
     * @param \AppBundle\Entity\Transport $transport
     * @return User
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
