<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="bet")
 */
class Bet
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    //one lot - many bets
    /*
    /**
     * @ORM\ManyToOne(targetEntity="Lot", inversedBy="bet")
     * @ORM\JoinColumn(name="lot_id", referencedColumnName="id")
     */
    /**
     * @ORM\Column(type="integer")
     */
    protected $lot_id;

    //one user - many bets
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="bet")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user_id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $value;
    
    /**
     * @ORM\Column(type="datetimetz")
     */
    protected $created_at;

    public function __construct(){
        $this->created_at = new \DateTime();
    }

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
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Bet
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
     * Set lot_id
     *
     * @param integer $lotId
     * @return Bet
     */
    public function setLotId($lotId = null)
    {
        $this->lot_id = $lotId;

        return $this;
    }

    /**
     * Get lot_id
     *
     * @return \AppBundle\Entity\Lot 
     */
    public function getLotId()
    {
        return $this->lot_id;
    }

    /**
     * Set user_id
     *
     * @param \AppBundle\Entity\User $userId
     * @return Bet
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
     * Set value
     *
     * @param integer $value
     * @return Bet
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return integer 
     */
    public function getValue()
    {
        return $this->value;
    }
}
