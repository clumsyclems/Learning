<?php

namespace ApiFrontBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Lang
 */
#[ORM\Table(name: 'lang')]
#[ORM\Entity(repositoryClass: 'ApiFrontBundle\Repository\LangRepository')]
class Lang implements \Stringable
{
    public function __toString(): string
    {
        return $this->wording ?: '';
    }
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;
    #[ORM\Column(name: 'local', type: 'string', length: 10, unique: true)]
    private string $local;
    #[ORM\Column(name: 'wording', type: 'string', length: 255)]
    private string $wording;
    #[ORM\Column(name: 'is_front', type: 'boolean')]
    private bool $isFront;
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
     * Set local
     *
     * @param string $local
     *
     * @return Lang
     */
    public function setLocal($local)
    {
        $this->local = $local;

        return $this;
    }
    /**
     * Get local
     *
     * @return string
     */
    public function getLocal()
    {
        return $this->local;
    }
    /**
     * Set wording
     *
     * @param string $wording
     *
     * @return Lang
     */
    public function setWording($wording)
    {
        $this->wording = $wording;

        return $this;
    }
    /**
     * Get wording
     *
     * @return string
     */
    public function getWording()
    {
        return $this->wording;
    }
    /**
     * Set isFront
     *
     * @param string $isFront
     *
     * @return boolean
     */
    public function setIsFront($isFront)
    {
        $this->isFront = $isFront;

        return $this;
    }
    /**
     * Get isFront
     *
     * @return boolean
     */
    public function getIsFront()
    {
        return $this->isFront;
    }
}
