<?php

namespace ApiBackendBundle\Entity;

use ApiFrontBundle\Entity\Lang;
use Doctrine\ORM\Mapping as ORM;
use \Doctrine\Common\Collections\Criteria;

/**
 * SimOffer
 */
#[ORM\Table(name: 'sim_offer')]
#[ORM\Entity(repositoryClass: 'ApiBackendBundle\Repository\SimOfferRepository')]
class SimOffer
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;
    #[ORM\Column(name: 'reference', type: 'string', length: 32)]
    private string $reference;
    #[ORM\Column(name: 'esim_supplier_reference', type: 'string', length: 32, nullable: true)]
    private string $eSimSupplierReference;
    #[ORM\Column(name: 'esim_email_order_text', type: 'string', length: 2000, nullable: true)]
    private string $eSimEmailOrderText;
    #[ORM\Column(name: 'is_physical', type: 'boolean')]
    private bool $isPhysical;
    #[ORM\Column(name: 'is_digital', type: 'boolean')]
    private bool $isDigital;
    /**
     * @var SimOfferType
     */
    #[ORM\ManyToOne(targetEntity: 'ApiBackendBundle\Entity\SimOfferType')]
    #[ORM\JoinColumn(name: 'sim_offer_type_id', referencedColumnName: 'id', nullable: false)]
    private $type;
    /**
     * @var SimOfferValidity
     */
    #[ORM\ManyToOne(targetEntity: 'ApiBackendBundle\Entity\SimOfferValidity')]
    #[ORM\JoinColumn(name: 'sim_offer_validity_id', referencedColumnName: 'id', nullable: false)]
    private $simOfferValidity;
    #[ORM\OneToMany(targetEntity: 'ApiBackendBundle\Entity\SimOfferLabel', mappedBy: 'simOffer', cascade: ['persist'], orphanRemoval: true)]
    private $labels;
    #[ORM\Column(name: 'gross_data_volume', type: 'integer', nullable: false, options: ['default' => 0])]
    private int $grossDataVolume;
    #[ORM\Column(name: 'is_active', type: 'boolean')]
    private bool $isActive;
    #[ORM\Column(name: 'price', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private string $price;
    #[ORM\Column(name: 'visual', type: 'blob', nullable: true)]
    private object $visuel;
    #[ORM\Column(name: 'visualMobile', type: 'blob', nullable: true)]
    private object $visuelMobile;
    /**
     * @var SoldCountry
     */
    #[ORM\ManyToOne(targetEntity: 'SoldCountry')]
    #[ORM\JoinColumn(nullable: true)]
    private $soldCountry;
    /**
     * @var Supplier
     */
    #[ORM\ManyToOne(targetEntity: 'Supplier')]
    #[ORM\JoinColumn(nullable: true)]
    private $supplier;
    /**
     * @var KycNeeded
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $kycNeeded;
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    /**
     * @param boolean $isPhysical
     * @return SimOffer
     */
    public function setIsPhysical($isPhysical)
    {
        $this->isPhysical = $isPhysical;
        return $this;
    }
    /**
     * @return bool
     */
    public function getIsPhysical()
    {
        return $this->isPhysical;
    }
    /**
     * @return SimOffer
     */
    public function setIsDigital($isDigital)
    {
        $this->isDigital = $isDigital;

        return $this;
    }
    /**
     * @return bool
     */
    public function getIsDigital()
    {
        return $this->isDigital;
    }
    /**
     * @param SimOfferType $type
     */
    public function setType(SimOfferType $type)
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return SimOfferType
     */
    public function getType()
    {
        return $this->type;
    }
    /**
     * @return SimOfferLabel
     */
    public function getLabelByLang(Lang $l)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('lang', $l));
        $result = $this->labels->matching($criteria);
        return (is_countable($result) ? count($result) : 0) > 0 ? $result[0] : array();
    }
    /**
     * @return mixed
     */
    public function getLabels()
    {
        return $this->labels;
    }
    /**
     * @param boolean $isActive
     * @return SimOffer
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }
    /**
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }
    /**
     * @param decimal $price
     * @return SimOffer
     */
    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }
    /**
     * @return decimal
     */
    public function getPrice()
    {
        return $this->price;
    }
    /**
     * @return int
     */
    public function getGrossDataVolume()
    {
        return $this->grossDataVolume;
    }
    /**
     * @param int $grossDataVolume
     */
    public function setGrossDataVolume($grossDataVolume)
    {
        $this->grossDataVolume = $grossDataVolume;
    }
    /**
     * @return SimOfferValidity
     */
    public function getSimOfferValidity()
    {
        return $this->simOfferValidity;
    }
    /**
     * @param SimOfferValidity $simOfferValidity
     */
    public function setSimOfferValidity($simOfferValidity)
    {
        $this->simOfferValidity = $simOfferValidity;
    }
    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }
    /**
     * @param string $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }
    public function getVisuel(): object|string
    {
        return $this->visuel ;
    }
    /**
     * @param object $visuel
     */
    public function setVisuel($visuel)
    {
        $this->visuel = $visuel;
    }
    /**
     * @return SoldCountry
     */
    public function getSoldCountry()
    {
        return $this->soldCountry;
    }
    /**
     * @param SoldCountry $soldCountry
     */
    public function setSoldCountry($soldCountry)
    {
        $this->soldCountry = $soldCountry;
    }
    /**
     * @return Supplier
     */
    public function getSupplier()
    {
        return $this->supplier;
    }
    /**
     * @param Supplier $supplier
     */
    public function setSupplier($supplier)
    {
        $this->supplier = $supplier;
    }
    /**
     * @return string
     */
    public function getESimSupplierReference()
    {
        return $this->eSimSupplierReference;
    }
    /**
     * @param string $eSimSupplierReference
     */
    public function setESimSupplierReference($eSimSupplierReference)
    {
        $this->eSimSupplierReference = $eSimSupplierReference;
    }
    /**
     * @return string
     */
    public function getESimEmailOrderText()
    {
        return $this->eSimEmailOrderText;
    }
    /**
     * @param string $eSimEmailOrderText
     */
    public function setESimEmailOrderText($eSimEmailOrderText)
    {
        $this->eSimEmailOrderText = $eSimEmailOrderText;
    }
    /**
     * @return KycNeeded
     */
    public function getKycNeeded()
    {
        return $this->kycNeeded;
    }
    /**
     * @param KycNeeded $kycNeeded
     */
    public function setKycNeeded($kycNeeded)
    {
        $this->kycNeeded = $kycNeeded;
    }
    /**
     * @param object $visuelMobile
     */
    public function setVisuelMobile($visuelMobile)
    {
        $this->visuelMobile = $visuelMobile;
    }
    /**
     * @return object
     */
    public function getVisuelMobile()
    {
        return $this->visuelMobile;
    }
} // end class
