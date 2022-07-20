<?php

namespace ApiBackendBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Expose;
/**
 * SimReseller
 */
#[ORM\Table(name: 'sim_reseller')]
#[ORM\Entity(repositoryClass: 'ApiBackendBundle\Repository\SimResellerRepository')]
class SimReseller
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;
    #[ORM\Column(name: 'email', type: 'string', length: 255, nullable: true)]
    private string $email;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'phone', type: 'string', length: 20, nullable: true)]
    private string $phone;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'business_name', type: 'string', length: 100)]
    private string $businessName;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'website', type: 'string', length: 255, nullable: true)]
    private string $website;
    #[ORM\Column(name: 'is_active', type: 'boolean')]
    private bool $isActive;
    #[ORM\Column(name: 'is_world', type: 'boolean')]
    private bool $isWorld;
    #[ORM\Column(name: 'is_amazon', type: 'boolean')]
    private bool $isAmazon;
    /**
     * @Serializer\Exclude()
     */
    #[ORM\OneToMany(targetEntity: 'ApiBackendBundle\Entity\SimOfferResellerCountry', mappedBy: 'simReseller', cascade: ['remove'])]
    private $offerResellerCountries;
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }
    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }
    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }
    /**
     * @return string
     */
    public function getBusinessName()
    {
        return $this->businessName;
    }
    /**
     * @param string $businessName
     */
    public function setBusinessName($businessName)
    {
        $this->businessName = $businessName;
    }
    /**
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }
    /**
     * @param string $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }
    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isActive;
    }
    /**
     * @param bool $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }
    /**
     * @return bool
     */
    public function isWorld()
    {
        return $this->isWorld;
    }
    /**
     * @param bool $isWorld
     */
    public function setIsWorld($isWorld)
    {
        $this->isWorld = $isWorld;
    }
    /**
     * @return bool
     */
    public function isAmazon()
    {
        return $this->isAmazon;
    }
    /**
     * @param bool $isAmazon
     */
    public function setIsAmazon($isAmazon)
    {
        $this->isAmazon = $isAmazon;
    }
    /**
     * @return SimOfferResellerCountry[]
     */
    public function getOfferResellerCountries()
    {
        return $this->offerResellerCountries;
    }
    /**
     * @param SimOfferResellerCountry[] $offerResellerCountries
     */
    public function setOfferResellerCountries($offerResellerCountries)
    {
        $this->offerResellerCountries = $offerResellerCountries;
    }
} // end class
