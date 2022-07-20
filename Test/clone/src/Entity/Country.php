<?php

namespace ApiBackendBundle\Entity;

use ApiFrontBundle\Entity\Lang;
use Doctrine\ORM\Mapping as ORM;
use \Doctrine\Common\Collections\Criteria;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * Country
 *
 * @ExclusionPolicy("none")
 */
#[ORM\Table(name: 'countries')]
#[ORM\Entity(repositoryClass: 'ApiBackendBundle\Repository\CountryRepository')]
class Country implements \Stringable
{
    public function __toString(): string {
        return $this->iSOCode;
    }
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;
    #[ORM\Column(name: 'ISO_code', type: 'string', length: 10, unique: true)]
    private string $iSOCode;
    #[ORM\Column(name: 'isoCode3car', type: 'string', length: 10, unique: true, nullable: true)]
    private string $isoCode3Car;
    #[ORM\Column(name: 'currency_code', type: 'string', length: 10)]
    private string $currency;
    #[ORM\Column(name: 'phone_code', type: 'string', length: 10, nullable: true)]
    private string $phoneCode;
    #[ORM\Column(name: 'canSMS', type: 'boolean')]
    private bool $canSMS;
    #[ORM\Column(name: 'is_orange', type: 'boolean')]
    private bool $isOrange;
    #[ORM\Column(name: 'is_european', type: 'boolean', options: ['default' => 0])]
    private bool $isEuropean;
    #[ORM\Column(name: 'is_risk', type: 'boolean')]
    private bool $isRisk;
    #[ORM\OneToMany(targetEntity: 'CountryLabel', mappedBy: 'country', cascade: ['persist'])]
    protected $countryLabels;
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
     * Set iSOCode
     *
     * @param string $iSOCode
     *
     * @return Country
     */
    public function setISOCode($iSOCode)
    {
        $this->iSOCode = $iSOCode;

        return $this;
    }
    /**
     * Get iSOCode
     *
     * @return string
     */
    public function getISOCode()
    {
        return $this->iSOCode;
    }
    /**
     * @return string
     */
    public function getIsoCode3Car()
    {
        return $this->isoCode3Car;
    }
    /**
     * @param string $isoCode3Car
     */
    public function setIsoCode3Car($isoCode3Car)
    {
        $this->isoCode3Car = $isoCode3Car;
    }
    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }
    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }
    /**
     * Set phoneCode
     *
     * @param string $phoneCode
     *
     * @return Country
     */
    public function setPhoneCode($phoneCode)
    {
        $this->phoneCode = $phoneCode;

        return $this;
    }
    /**
     * Get phoneCode
     *
     * @return string
     */
    public function getPhoneCode()
    {
        return $this->phoneCode;
    }
    /**
     * @return boolean
     */
    public function isCanSMS()
    {
        return $this->canSMS;
    }
    /**
     * @param boolean $canSMS
     */
    public function setCanSMS($canSMS)
    {
        $this->canSMS = $canSMS;
    }
    /**
     * @return mixed
     */
    public function getIsOrange()
    {
        return $this->isOrange;
    }
    /**
     * @param mixed $isOrange
     */
    public function setIsOrange($isOrange)
    {
        $this->isOrange = $isOrange;
    }
    /**
     * @return mixed
     */
    public function getIsRisk()
    {
        return $this->isRisk;
    }
    /**
     * @param mixed $isRisk
     */
    public function setIsRisk($isRisk)
    {
        $this->isRisk = $isRisk;
    }
    public function getCountryLabelByLang(Lang $l)
    {
        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->eq('lang', $l));

        return $this->countryLabels->matching($criteria);
    }
    /**
     * @return mixed
     */
    public function getCountryLabels()
    {
        return $this->countryLabels;
    }
}

