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
#[ORM\Table(name: 'sim_offer_label')]
#[UniqueConstraint(name: 'lang_sim_offer', columns: ['lang_id', 'sim_offer_id'])]
#[ORM\Entity(repositoryClass: 'ApiBackendBundle\Repository\SimOfferLabelRepository')]
class SimOfferLabel
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: 'ApiFrontBundle\Entity\Lang')]
    #[ORM\JoinColumn(name: 'lang_id', referencedColumnName: 'id', nullable: false)]
    protected $lang;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    private string $name;

    #[ORM\Column(name: '`data`', type: 'string', length: 255, nullable: true)]
    private string $data;

    #[ORM\Column(name: '`call`', type: 'string', length: 255, nullable: true)]
    private string $call;

    #[ORM\Column(name: '`text`', type: 'string', length: 255, nullable: true)]
    private string $text;

    #[ORM\Column(name: '`description`', type: 'text', nullable: true)]
    private string $description;

    #[ORM\Column(name: '`mentions`', type: 'text', nullable: true)]
    private string $mentions;

    #[ORM\ManyToOne(targetEntity: 'ApiBackendBundle\Entity\SimOffer', cascade: ['persist'], inversedBy: 'labels')]
    #[ORM\JoinColumn(name: 'sim_offer_id', referencedColumnName: 'id', nullable: false)]
    private $simOffer;

    public function __construct()
    {
        $this->name = "";
        $this->data = "";
        $this->call = "";
        $this->text = "";
        $this->description = "";
        $this->mentions = "";
    }
    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @param mixed $id
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
    public function getData()
    {
        return $this->data;
    }
    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
    /**
     * @return string
     */
    public function getCall()
    {
        return $this->call;
    }
    /**
     * @param string $call
     */
    public function setCall($call)
    {
        $this->call = $call;
    }
    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }
    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }
    /**
     * @return string
     */
    public function getSimOffer()
    {
        return $this->simOffer;
    }
    /**
     * @param string $validity
     */
    public function setSimOffer($simOffer)
    {
        $this->simOffer = $simOffer;
    }
    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
    /**
     * @return string
     */
    public function getMentions()
    {
        return $this->mentions;
    }
    /**
     * @param string $mentions
     */
    public function setMentions($mentions)
    {
        $this->mentions = $mentions;
    }
} // end class
