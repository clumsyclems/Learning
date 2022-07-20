<?php

namespace ApiBackendBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Index;

/**
 * PromotionOperator
 */
#[ORM\Table(name: 'promotion_operator')]
#[ORM\Index(name: 'operator_ids', columns: ['operator_id'])]
#[ORM\Entity(repositoryClass: 'ApiBackendBundle\Repository\PromotionOperatorRepository')]
class PromotionOperator
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;
    #[ORM\ManyToOne(targetEntity: 'ApiBackendBundle\Entity\Promotion', inversedBy: 'promotionOperators')]
    #[ORM\JoinColumn(name: 'promotion_code', referencedColumnName: 'code', nullable: false)]
    private $promotion;
    #[ORM\Column(name: 'operator_id', type: 'string', length: 255)]
    private string $operatorId;
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
    public function getCode()
    {
        return $this->code;
    }
    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }
    /**
     * Set promotion
     *
     * @return Promotion
     */
    public function setPromotion($promotion)
    {
        $this->promotion = $promotion;

        return $this;
    }
    /**
     * Get promotion
     */
    public function getPromotion()
    {
        return $this->promotion;
    }
    /**
     * @return string
     */
    public function getOperatorId()
    {
        return $this->operatorId;
    }
    /**
     * @param string $operatorId
     */
    public function setOperatorId($operatorId)
    {
        $this->operatorId = $operatorId;
    }
}
