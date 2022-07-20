<?php

namespace ApiFrontBundle\Entity;

use ApiBackendBundle\Entity\Country;
use ApiBackendBundle\Entity\CustomEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Type;
use JsonSerializable;


/**
 * Customer
 *
 * @UniqueEntity({"phone_code", "phone_number"})
 * @ExclusionPolicy("all")
 */
#[ORM\Table(name: 'customer')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'ApiFrontBundle\Repository\CustomerRepository')]
class Customer extends CustomEntity
{
    public const STATUS_ACTIVE = 'activated';
    public const STATUS_INACTIVE = 'disabled';
    public const STATUS_FRAUD = 'fraud';
    public const STATUS_VALIDATION = 'validation';
    public const VALIDATION_TYPE_MAIL = 'mail';
    public const VALIDATION_TYPE_SMS = 'sms';
    public const OFFER_STANDARD = 'standard';
    public const OFFER_TRAVELERS = 'travelsimcard';
    /**
     * @var guid
     *
     * @Type("string")
     * @Expose
     */
    #[ORM\Column(name: 'id', type: 'guid')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'UUID')]
    private $id;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'last_name', type: 'string', length: 100, nullable: true)]
    private string $lastName;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'first_name', type: 'string', length: 100, nullable: true)]
    private string $firstName;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'email', type: 'string', length: 255, unique: true)]
    private string $email;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'phone_code', type: 'string', length: 5, nullable: true)]
    private string $phoneCode;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'phone_number', type: 'string', length: 15, nullable: true)]
    private string $phoneNumber;
    #[ORM\Column(name: 'password', type: 'string', length: 255)]
    private string $password;
    #[ORM\Column(name: 'deviceId', type: 'string', length: 64, nullable: true)]
    private string $deviceId;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'status', type: 'string', length: 10)]
    private string $status;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'profile_picture', type: 'string', length: 255, nullable: true)]
    private string $profilePicture;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'last_login', type: 'datetime', nullable: true)]
    private \DateTime $lastLogin;
    /**
     * @Expose
     */
    #[ORM\ManyToOne(targetEntity: 'ApiBackendBundle\Entity\Country')]
    #[ORM\JoinColumn(nullable: true)]
    private $country;
    /**
     * @Expose
     */
    #[ORM\ManyToOne(targetEntity: 'Lang')]
    #[ORM\JoinColumn(nullable: true)]
    private $lang;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'sender_countries', type: 'text', nullable: true)]
    protected $senderCountries;
    /**
     *
     * @Expose
     *
     */
    #[ORM\Column(name: 'validation_code', type: 'string', length: 10, nullable: false)]
    private string $validationCode;
    /**
     *
     * @Expose
     *
     */
    #[ORM\Column(name: 'validation_type', type: 'string', length: 5, nullable: false)]
    private string $validationType;
    /**
     * @var smallint
     *
     * @Expose
     *
     */
    #[ORM\Column(name: 'validation_attempts', type: 'smallint', nullable: false)]
    private $validationAttempts;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'validation_created_at', type: 'datetime', nullable: false)]
    private \DateTime $validationCreatedAt;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'validation_validated_at', type: 'datetime', nullable: true)]
    private \DateTime $validationValidatedAt;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'accept_newsletter', type: 'boolean', nullable: false)]
    private bool $acceptNewsletter;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_one_click', type: 'string', length: 19, unique: true)]
    private string $paymentOneClickId;
    /**
     * @var string
     *
     * @Expose
     */
    #[ORM\Column(name: 'last_editor', type: 'string', length: 20)]
    protected $lastEditor;
    /**
     * @var datetime
     *
     * @Expose
     */
    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected $createdAt;
    /**
     * @var datetime
     *
     * @Expose
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    protected $updatedAt;
    /**
     * @var \DateTime
     *
     * @Expose
     */
    #[ORM\Column(name: 'last_transaction_date', type: 'datetime', nullable: true)]
    protected $lastTransactionDate;
    /**
     * @var float
     * @Expose
     */
    #[ORM\Column(name: 'total_transaction', type: 'decimal', precision: 10, scale: 2, nullable: false)]
    protected $totalTransaction;
    /**
     * @var string
     *
     * @Expose
     */
    #[ORM\Column(name: 'origin', type: 'string', length: 10)]
    protected $origin;
    /**
     * @Expose()
     */
    #[ORM\Column(name: 'no_order_last_callback', type: 'datetime', nullable: true)]
    private \DateTime $noOrderLastCallback;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'offer', type: 'string', length: 15, nullable: false)]
    private string $offer;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'sim_id', type: 'string', length: 13, nullable: true)]
    private string $simId;
    #[ORM\OneToMany(targetEntity: 'ApiBackendBundle\Entity\ESimProfil', mappedBy: 'customer', cascade: ['persist'], orphanRemoval: true)]
    private $eSimProfils;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'sim_msisdn', type: 'string', length: 15, nullable: true)]
    private string $simMsisdn;
    public function __construct()
    {
        $this->eSimProfils = new ArrayCollection();
    }
    /**
     * Get id
     *
     * @return guid
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return Customer
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }
    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }
    /**
     * Set firstName
     *
     * @param string $firstName
     *
     * @return Customer
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }
    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }
    /**
     * Set email
     *
     * @param string $email
     *
     * @return Customer
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }
    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
    /**
     * Set phoneCode
     *
     * @param string $phoneCode
     *
     * @return Customer
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
     * Set phoneNumber
     *
     * @param string $phoneNumber
     *
     * @return Customer
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }
    /**
     * Get phoneNumber
     *
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }
    /**
     * Set password
     *
     * @param string $password
     *
     * @return Customer
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }
    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
    /**
     * Set status
     *
     * @param string $status
     *
     * @return Customer
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }
    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
    /**
     * Set profilePicture
     *
     * @param string $profilePicture
     *
     * @return Customer
     */
    public function setProfilePicture($profilePicture)
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }
    /**
     * Get profilePicture
     *
     * @return string
     */
    public function getProfilePicture()
    {
        return $this->profilePicture;
    }
    /**
     * Set lastLogin
     *
     * @param \DateTime $lastLogin
     *
     * @return Customer
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }
    /**
     * Get lastLogin
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }
    /**
     * @return $this
     */
    public function setCountry(Country $country=null)
    {
        $this->country = $country;

        return $this;
    }
    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }
    /**
     * @return $this
     */
    public function setLang(Lang $lang)
    {
        $this->lang = $lang;

        return $this;
    }
    /**
     * @return mixed
     */
    public function getLang()
    {
        return $this->lang;
    }
    /**
     * @return $this
     */
    public function addSenderCountry(Country $country)
    {
        $countries = $this->getSenderCountries();

        if (!$countries) {
            $countries = array();
        }
        if(!in_array($country->getISOCode(), $countries)) {
            $countries[] = $country->getISOCode();
        }
        $this->senderCountries = serialize($countries);

        return $this;
    }
    /**
     * @return mixed
     */
    public function getSenderCountries()
    {
        return unserialize($this->senderCountries);
    }
    /**
     * @return mixed
     */
    public function clearSenderCountries()
    {
        $this->senderCountries = null;
    }
    /**
     * @return string
     */
    public function getValidationCode()
    {
        return $this->validationCode;
    }
    /**
     * @param string $validationCode
     */
    public function setValidationCode($validationCode)
    {
        $this->validationCode = $validationCode;
    }
    /**
     * @return string
     */
    public function getValidationType()
    {
        return $this->validationType;
    }
    /**
     * @param string $validationType
     */
    public function setValidationType($validationType)
    {
        $this->validationType = $validationType;
    }
    /**
     * @return smallint
     */
    public function getValidationAttempts()
    {
        return $this->validationAttempts;
    }
    /**
     * @param smallint $validationAttemps
     */
    public function setValidationAttempts($validationAttempts)
    {
        $this->validationAttempts = $validationAttempts;
    }
    /**
     * @return \DateTime
     */
    public function getValidationCreatedAt()
    {
        return $this->validationCreatedAt;
    }
    /**
     * @param \DateTime $validationCreatedAt
     */
    public function setValidationCreatedAt($validationCreatedAt)
    {
        $this->validationCreatedAt = $validationCreatedAt;
    }
    /**
     * @return \DateTime
     */
    public function getValidationValidatedAt()
    {
        return $this->validationValidatedAt;
    }
    /**
     * @param \DateTime $validationValidatedAt
     */
    public function setValidationValidatedAt($validationValidatedAt)
    {
        $this->validationValidatedAt = $validationValidatedAt;
    }
    /**
     * @return boolean
     */
    public function getAcceptNewsletter()
    {
        return $this->acceptNewsletter;
    }
    /**
     * @param boolean $acceptNewsletter
     */
    public function setAcceptNewsletter($acceptNewsletter)
    {
        $this->acceptNewsletter = $acceptNewsletter;
    }
    /**
     * @return string
     */
    public function getPaymentOneClickId()
    {
        return $this->paymentOneClickId;
    }
    /**
     * @param string $paymentId
     */
    public function setPaymentOneClickId()
    {
        // Mercanet is limited to 19 chars numerics
        // We cant use a classic hash
        // use phone number reduced to 9 + timestamp reduced to 10
        //$this->paymentOneClickId = substr($this->phoneNumber, -9) . substr(time(), -10);
        $this->paymentOneClickId = substr(\CUSTOMER . phptime(), 0, 19);
    }
    /**
     * @return \DateTime
     */
    public function getLastTransactionDate()
    {
        return $this->lastTransactionDate;
    }
    /**
     * @param mixed $lastTransactionDate
     */
    public function setLastTransactionDate($lastTransactionDate)
    {
        $this->lastTransactionDate = $lastTransactionDate;
    }
    /**
     * @return mixed
     */
    public function getTotalTransaction()
    {
        return $this->totalTransaction;
    }
    /**
     * @param int $totalTransaction
     */
    public function setTotalTransaction($totalTransaction)
    {
        if($totalTransaction && $totalTransaction!=null)
            $this->totalTransaction = $totalTransaction;
        else
            $this->totalTransaction = 0;
    }
    /**
     * @return string
     */
    public function getOrigin()
    {
        return $this->origin;
    }
    /**
     * @param string $origin
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;
    }
    /**
     * @return string
     */
    public function getLastEditor()
    {
        return $this->lastEditor;
    }
    /**
     * @param string $lastEditor
     */
    public function setLastEditor($lastEditor)
    {
        $this->lastEditor = $lastEditor;
    }
    /**
     * @return datetime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    /**
     * @param datetime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }
    /**
     * @return datetime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
    /**
     * @param datetime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }
    /**
     * Gets triggered only on insert
     */
    #[ORM\PrePersist]
    public function onPrePersist()
    {
        parent::onPrePersist();
        if (!isset($this->status)) {
            $this->status = self::STATUS_VALIDATION;
        }
        if (!isset($this->paymentOneClickId)) {
            $this->paymentOneClickId = $this->setPaymentOneClickId();
        }
        if (!isset($this->origin)) {
            $this->origin = 'website';
        }
    }
    /**
     * @return \DateTime
     */
    public function getNoOrderLastCallback()
    {
        return $this->noOrderLastCallback;
    }
    /**
     * @param \DateTime $noOrderLastCallback
     */
    public function setNoOrderLastCallback($noOrderLastCallback)
    {
        $this->noOrderLastCallback = $noOrderLastCallback;
    }
    /**
     * @return string
     */
    public function getOffer()
    {
        return $this->offer;
    }
    /**
     * @param string $offer
     */
    public function setOffer($offer)
    {
        $this->offer = $offer;
    }
    /**
     * @return string
     */
    public function getSimId()
    {
        return $this->simId;
    }
    /**
     * @param string $simId
     */
    public function setSimId($simId)
    {
        $this->simId = $simId;
    }
    /**
     * @return array
     */
    public function getESimProfils()
    {
        return $this->eSimProfils;
    }
    /**
     * @param array $eSimProfils
     */
    public function setESimProfils($eSimProfils)
    {
        $this->eSimProfils = $eSimProfils;
    }
    /**
     * @return string
     */
    public function getSimMsisdn()
    {
        return $this->simMsisdn;
    }
    /**
     * @param string $simMsisdn
     */
    public function setSimMsisdn($simMsisdn)
    {
        $this->simMsisdn = $simMsisdn;
    }
    /**
     * @return string
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }
    /**
     * @param string $deviceId
     */
    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;
    }
} // end class

