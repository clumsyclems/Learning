<?php

namespace ApiBackendBundle\Entity;

use ApiBackendBundle\Entity\Country;
use ApiBackendBundle\Entity\CustomEntity;
use ApiFrontBundle\Entity\Lang;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * SimOfferLabel label
 */
#[ORM\Table(name: 'sim_offer_validity_label')]
#[UniqueConstraint(name: 'lang_sim_offer_validity', columns: ['lang_id', 'sim_offer_validity_id'])]
#[ORM\Entity(repositoryClass: 'ApiBackendBundle\Repository\SimOfferValidityLabelRepository')]
class SimOfferValidityLabel
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: 'ApiFrontBundle\Entity\Lang')]
    #[ORM\JoinColumn(name: 'lang_id', referencedColumnName: 'id', nullable: false)]
    private $lang;

    #[ORM\ManyToOne(targetEntity: 'ApiBackendBundle\Entity\SimOfferValidity', cascade: ['persist'], inversedBy: 'labels')]
    #[ORM\JoinColumn(name: 'sim_offer_validity_id', referencedColumnName: 'id', nullable: false)]
    private $simOfferValidity;

    #[ORM\Column(name: 'short_name', type: 'string', length: 30, nullable: false)]
    private string $shortName;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private string $name;
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
     * @return mixed
     */
    public function getSimOfferValidity()
    {
        return $this->simOfferValidity;
    }
    /**
     * @param mixed $simOfferValidity
     */
    public function setSimOfferValidity($simOfferValidity)
    {
        $this->simOfferValidity = $simOfferValidity;
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
     * @return string
     */
    public function getShortName()
    {
        return $this->shortName;
    }
    /**
     * @param string $shortName
     */
    public function setShortName($shortName)
    {
        $this->shortName = $shortName;
    }
} // end class
