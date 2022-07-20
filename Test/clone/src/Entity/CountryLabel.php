<?php

namespace ApiBackendBundle\Entity;

use ApiBackendBundle\Entity\Country;
use ApiBackendBundle\Entity\CustomEntity;
use ApiFrontBundle\Entity\Lang;
use Doctrine\ORM\Mapping as ORM;

/**
 * Country label
 */
#[ORM\Table(name: 'countries_label')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'ApiBackendBundle\Repository\CountryLabelRepository')]
class CountryLabel
{
    public function __construct(
        #[ORM\ManyToOne(targetEntity: 'Country', inversedBy: 'countryLabels')] 
        #[ORM\JoinColumn(name: 'country_id', referencedColumnName: 'id')] 
        #[ORM\Id] protected $country, #[ORM\ManyToOne(targetEntity: 'ApiFrontBundle\Entity\Lang')] 
        #[ORM\JoinColumn(name: 'lang_id', referencedColumnName: 'id')] #[ORM\Id] protected $lang, private $wording)
    {
    }
    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }
    /**
     * @param mixed $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }
    /**
     * @return mixed
     */
    public function getLang()
    {
        return $this->lang;
    }
    /**
     * @param mixed $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }
    /**
     * @return string
     */
    public function getWording()
    {
        return $this->wording;
    }
    /**
     * @param string $wording
     */
    public function setWording($wording)
    {
        $this->wording = $wording;
    }
}

