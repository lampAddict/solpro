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

    /**
     * One User has Many Drivers.
     * @ORM\OneToMany(targetEntity="Driver", mappedBy="user_id")
     */
    protected $driver;

    /**
     * One User has Many Routes.
     * @ORM\OneToMany(targetEntity="Route", mappedBy="user_id")
     */
    protected $route;

    /**
     * One User has Many Bets.
     * @ORM\OneToMany(targetEntity="Bet", mappedBy="user_id")
     */
    protected $bet;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $timezone;

    public function __construct()
    {
        parent::__construct();
        // your own logic
        $this->roles = array('ROLE_USER');
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

    /**
     * Add driver
     *
     * @param \AppBundle\Entity\Driver $driver
     * @return User
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

    /**
     * Add route
     *
     * @param \AppBundle\Entity\Route $route
     * @return User
     */
    public function addRoute(\AppBundle\Entity\Route $route)
    {
        $this->route[] = $route;

        return $this;
    }

    /**
     * Remove route
     *
     * @param \AppBundle\Entity\Route $route
     */
    public function removeRoute(\AppBundle\Entity\Route $route)
    {
        $this->route->removeElement($route);
    }

    /**
     * Get route
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Add bet
     *
     * @param \AppBundle\Entity\Bet $bet
     * @return User
     */
    public function addBet(\AppBundle\Entity\Bet $bet)
    {
        $this->bet[] = $bet;

        return $this;
    }

    /**
     * Remove bet
     *
     * @param \AppBundle\Entity\Bet $bet
     */
    public function removeBet(\AppBundle\Entity\Bet $bet)
    {
        $this->bet->removeElement($bet);
    }

    /**
     * Get bet
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getBet()
    {
        return $this->bet;
    }

    /**
     * Set timezone
     *
     * @param string $timezone
     *
     * @return User
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Get timezone
     *
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }
}
