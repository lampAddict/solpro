<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="refCarrierUser",indexes={@ORM\Index(name="searchBy1Cid", columns={"id1c"})})
 */
class RefCarrierUser
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
     * @ORM\Column(type="string", length=50)
     */
    protected $carrierId;

    /**
     * @ORM\Column(type="string", length=150)
     */
    protected $name;

    /**
     * @ORM\Column(type="smallint", length=150)
     */
    protected $access;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $login;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $password;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $email;

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
     * @return RefCarrierUser
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
     * Set carrierId
     *
     * @param string $carrierId
     *
     * @return RefCarrierUser
     */
    public function setCarrierId($carrierId)
    {
        $this->carrierId = $carrierId;

        return $this;
    }

    /**
     * Get carrierId
     *
     * @return string
     */
    public function getCarrierId()
    {
        return $this->carrierId;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return RefCarrierUser
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
     * Set access
     *
     * @param integer $access
     *
     * @return RefCarrierUser
     */
    public function setAccess($access)
    {
        $this->access = $access;

        return $this;
    }

    /**
     * Get access
     *
     * @return integer
     */
    public function getAccess()
    {
        return $this->access;
    }

    /**
     * Set login
     *
     * @param string $login
     *
     * @return RefCarrierUser
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return RefCarrierUser
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return RefCarrierUser
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
}
