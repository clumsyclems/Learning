<?php

namespace ApiBackendBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Type;

class CustomEntity
{
    public const SYSTEM = "system";
    public const STATUS_ACTIVE = true;
    public const STATUS_INACTIVE = false;

    /**
     * username of last editor
     *
     * @var string
     * @Expose
     * @Type("string")
     */
    #[ORM\Column(name: 'last_editor', type: 'string', length: 20)]
    protected $lastEditor;

    /**
     * @var datetime
     * @Expose
     * @Type("DateTime<'Y-m-d\TH:i:sO'>")
     */
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected $createdAt;

    /**
     * @var datetime
     * @Expose
     * @Type("DateTime<'Y-m-d\TH:i:sO'>")
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    protected $updatedAt;

    /**
     * Set last_editor
     *
     * @return CustomEntity
     */
    public function setLastEditor($lastEditor)
    {
        $this->lastEditor = $lastEditor;

        return $this;
    }

    /**
     * Get last_editor
     *
     * @return string
     */
    public function getLastEditor()
    {
        return $this->lastEditor;
    }

    /**
     * Get created_at
     *
     * @return datetime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Get updated_at
     *
     * @return datetime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Gets triggered only on insert
     */
    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->createdAt = new \Datetime();
    }

    /**
     * Gets triggered on update
     */
    #[ORM\PreUpdate]
    public function onPreUpdate()
    {
        $this->updatedAt = new \DateTime();
    }

    public function populate($array)
    {
        foreach($array as $k => $v) {
            $method = 'set' . ucfirst($k);

            if (method_exists($this, $method)) {
                $this->$method($v);
            }
        }
    }
}
