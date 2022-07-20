<?php

namespace ApiBackendBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Table('access_tokens')]
#[ORM\HasLifecycleCallbacks]
/**
 * @Entity({ abstract: true })
 */
class AccessToken
{
    public function __construct(#[ORM\ManyToOne(targetEntity: 'User')] protected UserInterface $user)
    {
        $this->isAlive = true;
        $this->token = sha1($user->getUsername().openssl_random_pseudo_bytes(15));
    }
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;
    #[ORM\Column(type: 'string')]
    protected $token;
    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    protected $isAlive;
    /**
     * @var datetime
     */
    #[ORM\Column(type: 'datetime')]
    #[ORM\OrderBy(['date' => 'ASC'])]
    protected $createdAt;
    public function getToken()
    {
        return $this->token;
    }
    public function getIsAlive()
    {
        return $this->isAlive;
    }
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    public function setUser(UserInterface $user)
    {
        $this->user = $user;
    }
    public function expire() {
        $this->isAlive = false;
    }
    /**
     * Gets triggered only on insert
     */
    #[ORM\PrePersist]
    public function onPrePersist()
    {
        $this->createdAt = new \Datetime();
    }
}