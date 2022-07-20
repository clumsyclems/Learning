<?php

namespace ApiBackendBundle\Entity;

use ApiFrontBundle\Entity\Customer;
use ApiFrontBundle\Entity\Lang;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Type;
use \Doctrine\Common\Collections\Criteria;

/**
 * Transaction
 */
#[ORM\Table(name: 'transaction')]
#[Index(name: 'search_contact', columns: ['contact_name'])]
#[Index(name: 'search_simid', columns: ['sim_id'])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: 'ApiBackendBundle\Repository\TransactionRepository')]
class Transaction extends CustomEntity
{
    public const SERVICE_AIRTIME = 'airtime';
    public const SERVICE_TRAVELSIMCARD = 'travelsimcard';
    public const SERVICE_TRAVEL_ESIM = 'esim';
    private $_oDecodedTopUpJson = null;
    private $_oDecodedProductJson = null;
    private function decodeTopupJson(){
        try
        {
            if(is_null($this->_oDecodedTopUpJson) && !is_null($this->topUpResponseJSON) && !empty($this->topUpResponseJSON))
                $this->_oDecodedTopUpJson = json_decode($this->topUpResponseJSON, null, 512, JSON_THROW_ON_ERROR);
        }
        catch (\Exception)
        {}
    }
    private function decodeProductJson(){
        try
        {
            if(is_null($this->_oDecodedProductJson) && !is_null($this->productJSON) && !empty($this->productJSON))
                $this->_oDecodedProductJson = json_decode($this->productJSON, null, 512, JSON_THROW_ON_ERROR);
        }
        catch (\Exception)
        {
        }
    }
    public function getTopUpProductRequested()
    {
        $_strReturn="";
        if($this->service==self::SERVICE_TRAVELSIMCARD)
        {
            $this->decodeProductJson();

            if(!is_null($this->_oDecodedProductJson) && is_object($this->_oDecodedProductJson) && isset($this->_oDecodedProductJson->duration))
                $_strReturn = $this->_oDecodedProductJson->data_europe . $this->_oDecodedProductJson->unit;
        }
        else
        {
            $this->decodeTopupJson();
            if(!is_null($this->_oDecodedTopUpJson) && is_object($this->_oDecodedTopUpJson) && isset($this->_oDecodedTopUpJson->product_requested))
                $_strReturn= $this->_oDecodedTopUpJson->product_requested;
        }

        return $_strReturn;
    }
    public function getTopUpLocalInfoValue()
    {
        $this->decodeTopupJson();
        if(!is_null($this->_oDecodedTopUpJson) && is_object($this->_oDecodedTopUpJson) && isset($this->_oDecodedTopUpJson->local_info_value))
            return $this->_oDecodedTopUpJson->local_info_value;
        else
            return "";
    }
    /**
     * @Type("string")
     * @Expose
     */
    #[ORM\Column(name: 'id', type: 'string', length: 32)]
    #[ORM\Id]
    private string $id;
    /**
     * @Expose
     */
    #[ORM\ManyToOne(targetEntity: 'ApiBackendBundle\Entity\TransactionStatus')]
    #[ORM\JoinColumn(nullable: false)]
    private $status;
    /**
     * We store the json serialization of sender properties
     * @Expose
     */
    #[ORM\Column(name: 'sender_json', type: 'text', nullable: false)]
    private string $senderJSON;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'sender_ip', type: 'string', length: 40, nullable: true)]
    private string $senderIP;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'receiver_phone_code', type: 'string', length: 5, nullable: true)]
    private string $receiverPhoneCode;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'receiver_phone_number', type: 'string', length: 15, nullable: true)]
    private string $receiverPhoneNumber;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'sim_id', type: 'string', length: 13, nullable: true)]
    private string $simId;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'receiver_country', type: 'string', length: 255, nullable: true)]
    private $receiverCountry;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'product_reference', type: 'string', length: 40, nullable: false)]
    private string $productReference;
    /**
     * We store the json serialization of product properties
     * @Expose
     */
    #[ORM\Column(name: 'product_json', type: 'text', nullable: true)]
    private string $productJSON;
    /**
     * @Expose
     */
    #[ORM\ManyToOne(targetEntity: 'Supplier')]
    #[ORM\JoinColumn(nullable: false)]
    private $productSupplier;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'price', type: 'decimal', precision: 10, scale: 2, nullable: false)]
    private float $price;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'fee', type: 'decimal', precision: 10, scale: 2, nullable: false)]
    private float $fee;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'discount', type: 'decimal', precision: 10, scale: 2, nullable: false)]
    private float $discount;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'promotion_code', type: 'string', length: 80, nullable: true)]
    private string $promotionCode;
    /**
     * We store the json serialization of product properties
     * @Expose
     */
    #[ORM\Column(name: 'promotion_json', type: 'text', nullable: true)]
    private string $promotionJSON;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'promo_orange_desc', type: 'string', length: 2000, nullable: true)]
    private string $promoOrangeDesc;
    #[ORM\Column(name: 'promo_orange_value', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private float $promoOrangeValue;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_transaction_id', type: 'string', length: 32, nullable: true)]
    private string $_paymentTxId;
    /**
     * We store the parameters used to build the Mercanet payment panel
     * @Expose
     */
    #[ORM\Column(name: 'payment_controls', type: 'text', nullable: true)]
    private string $paymentControls;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_status', type: 'boolean', nullable: true)]
    private bool $paymentStatus;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_details', type: 'string', length: 255, nullable: true)]
    private string $paymentDetails;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_response_code', type: 'string', length: 10, nullable: true)]
    private string $paymentResponseCode;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_complement_code', type: 'string', length: 10, nullable: true)]
    private string $paymentComplementCode;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_acquire_response_code', type: 'string', length: 10, nullable: true)]
    private string $paymentAcquirerResponseCode;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_holder_authent_status', type: 'string', length: 32, nullable: true)]
    private string $paymentHolderAuthentStatus;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_complement_codeinfos', type: 'string', length: 255, nullable: true)]
    private string $paymentComplementCodeInfos;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_ip', type: 'string', length: 80, nullable: true)]
    private string $paymentIP;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_authorization', type: 'string', length: 10, nullable: true)]
    private string $paymentAuthorization;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_last_update', type: 'datetime', nullable: true)]
    private \DateTime $paymentLastUpdate;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_capture_status', type: 'boolean', nullable: true)]
    private bool $paymentCaptureStatus;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_capture_details', type: 'string', length: 255, nullable: true)]
    private string $paymentCaptureDetails;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_capture_date', type: 'datetime', nullable: true)]
    private \DateTime $paymentCaptureDate;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'payment_id', type: 'string', length: 19, nullable: true)]
    private string $paymentOneClickId;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'topup_transaction_id', type: 'string', length: 50, nullable: true)]
    private string $topUpTxId;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'topup_status', type: 'boolean', nullable: true)]
    private bool $topUpStatus;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'topup_details', type: 'string', length: 255, nullable: true)]
    private string $topUpDetails;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'topup_error_code', type: 'string', length: 60, nullable: true)]
    private string $topUpErrorCode;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'topup_last_update', type: 'datetime', nullable: true)]
    private \DateTime $topUpLastUpdate;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'topup_creance_id', type: 'string', length: 50, nullable: true)]
    private string $topUpCreanceId;
    /**
     * We store the json serialization of creance
     * @Expose
     */
    #[ORM\Column(name: 'topup_creance_json', type: 'text', nullable: true)]
    private string $topUpCreanceJSON;
    /**
     * Final amount received by the recipient
     * @Expose
     */
    #[ORM\Column(name: 'topup_local_info_amount', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private string $topUpLocalInfoAmount;
    /**
     * Currency for the final amount
     * @Expose
     */
    #[ORM\Column(name: 'topup_local_info_currency', type: 'string', length: 10, nullable: true)]
    private string $topUpLocalInfoCurrency;
    /**
     * We store the json serialization of top up response
     * @Expose
     */
    #[ORM\Column(name: 'topup_response_json', type: 'text', nullable: true)]
    private string $topUpResponseJSON;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'sms_txt_for_receiver', type: 'text', nullable: true)]
    private string $smsTxtForReceiver;
    /**
     * @Expose

     */
    #[ORM\Column(name: 'email_txt_for_receiver', type: 'text', nullable: true)]
    private string $emailTxtForReceiver;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'template_txt_for_receiver', type: 'string', length: 30, nullable: true)]
    private string $tmplTxtForReceiver;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'contact_name', type: 'string', length: 255, nullable: true)]
    private string $contactName;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'callback_1', type: 'boolean', nullable: true)]
    private bool $callback1;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'callback_2', type: 'boolean', nullable: true)]
    private bool $callback2;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'receiver_email', type: 'string', length: 255, nullable: true)]
    private string $receiverEmail;
    /**
     * @Expose
     */
    #[ORM\ManyToOne(targetEntity: 'ApiFrontBundle\Entity\Customer')]
    #[ORM\JoinColumn(nullable: true)]
    private \ApiFrontBundle\Entity\Customer $customer;
    /**
     * @Expose
     */
    #[ORM\ManyToOne(targetEntity: 'ApiFrontBundle\Entity\Lang')]
    #[ORM\JoinColumn(nullable: true)]
    private \ApiFrontBundle\Entity\Lang $lang;
    /**
     * @Expose
     */
    #[ORM\ManyToOne(targetEntity: 'ApiBackendBundle\Entity\Country')]
    #[ORM\JoinColumn(nullable: true)]
    private \ApiBackendBundle\Entity\Country $paymentCardCountry;
    /**
     * @Expose
     */
    #[ORM\ManyToOne(targetEntity: 'ApiBackendBundle\Entity\Country')]
    #[ORM\JoinColumn(nullable: true)]
    private \ApiBackendBundle\Entity\Country $paymentIPCountry;
    /**
     * @Expose
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private float $topUpWholesalePrice;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'service', type: 'string', length: 15, nullable: true)]
    private string $service;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'energie_account_id', type: 'string', length: 32, nullable: true)]
    private string $energieAccountId;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'kyc_status', type: 'string', nullable: true)]
    private string $kycStatus;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'idemia_identity_id', type: 'string', nullable: true)]
    private string $idemiaIdentityId;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'idemia_identity_details', type: 'text', nullable: true)]
    private string $idemiaIdentityDetails;
    /**
     * @Expose
     */
    #[ORM\Column(name: 'idemia_sdk_mode', type: 'string', nullable: true)]
    private string $idemiaSDKMode;
    public function __construct()
    {
        $this->id = "ORLTRVL" . uniqid();
    }
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
    public function getStatus()
    {
        return $this->status;
    }
    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
    /**
     * @return string
     */
    public function getSenderJSON()
    {
        return $this->senderJSON;
    }
    /**
     * @param string $senderJSON
     */
    public function setSenderJSON($senderJSON)
    {
        $this->senderJSON = $senderJSON;
    }
    /**
     * @return string
     */
    public function getReceiverPhoneCode()
    {
        return $this->receiverPhoneCode;
    }
    /**
     * @param string $receiverPhoneCode
     */
    public function setReceiverPhoneCode($receiverPhoneCode)
    {
        $this->receiverPhoneCode = $receiverPhoneCode;
    }
    /**
     * @return string
     */
    public function getReceiverPhoneNumber()
    {
        return $this->receiverPhoneNumber;
    }
    /**
     * @param string $receiverPhoneNumber
     */
    public function setReceiverPhoneNumber($receiverPhoneNumber)
    {
        $this->receiverPhoneNumber = $receiverPhoneNumber;
    }
    /**
     * @return mixed
     */
    public function getReceiverCountry()
    {
        return $this->receiverCountry;
    }
    /**
     * @param mixed $receiverCountry
     */
    public function setReceiverCountry($receiverCountry)
    {
        $this->receiverCountry = $receiverCountry;
    }
    /**
     * @return string
     */
    public function getProductReference()
    {
        return $this->productReference;
    }
    /**
     * @param string $productReference
     */
    public function setProductReference($productReference)
    {
        $this->productReference = $productReference;
    }
    /**
     * @return string
     */
    public function getProductJSON()
    {
        return $this->productJSON;
    }
    /**
     * @param string $productJSON
     */
    public function setProductJSON($productJSON)
    {
        $this->productJSON = $productJSON;
    }
    /**
     * @return mixed
     */
    public function getProductSupplier()
    {
        return $this->productSupplier;
    }
    /**
     * @param mixed $productSupplier
     */
    public function setProductSupplier($productSupplier)
    {
        $this->productSupplier = $productSupplier;
    }
    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }
    /**
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }
    /**
     * @return float
     */
    public function getFee()
    {
        return $this->fee;
    }
    /**
     * @param float $fee
     */
    public function setFee($fee)
    {
        $this->fee = $fee;
    }
    /**
     * @return float
     */
    public function getDiscount()
    {
        return $this->discount;
    }
    /**
     * @param float $discount
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
    }
    /**
     * @return string
     */
    public function getPromotionCode()
    {
        return $this->promotionCode;
    }
    /**
     * @param string $promotionCode
     */
    public function setPromotionCode($promotionCode)
    {
        $this->promotionCode = $promotionCode;
    }
    /**
     * @return string
     */
    public function getPromotionJSON()
    {
        return $this->promotionJSON;
    }
    /**
     * @param string $promotionJSON
     */
    public function setPromotionJSON($promotionJSON)
    {
        $this->promotionJSON = $promotionJSON;
    }
    /**
     * @return string
     */
    public function getPromoOrangeDesc()
    {
        return $this->promoOrangeDesc;
    }
    /**
     * @param string $promoOrangeDesc
     */
    public function setPromoOrangeDesc($promoOrangeDesc)
    {
        $this->promoOrangeDesc = $promoOrangeDesc;
    }
    /**
     * @return float
     */
    public function getPromoOrangeValue()
    {
        return $this->promoOrangeValue;
    }
    /**
     * @param float $promoOrangeValue
     */
    public function setPromoOrangeValue($promoOrangeValue)
    {
        $this->promoOrangeValue = $promoOrangeValue;
    }
    /**
     * @return string
     */
    public function getPaymentTxId()
    {
        return $this->_paymentTxId;
    }
    /**
     * @param string $paymentTxId
     */
    public function setPaymentTxId($paymentTxId)
    {
        $this->_paymentTxId = $paymentTxId;
    }
    /**
     * @return boolean
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }
    /**
     * @param boolean $paymentStatus
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;
    }
    /**
     * @return string
     */
    public function getPaymentDetails()
    {
        return $this->paymentDetails;
    }
    /**
     * @param string $paymentDetails
     */
    public function setPaymentDetails($paymentDetails)
    {
        $this->paymentDetails = $paymentDetails;
    }
    /**
     * @return string
     */
    public function getPaymentResponseCode()
    {
        return $this->paymentResponseCode;
    }
    /**
     * @param string $paymentResponseCode
     */
    public function setPaymentResponseCode($paymentResponseCode)
    {
        $this->paymentResponseCode = $paymentResponseCode;
    }
    /**
     * @return string
     */
    public function getPaymentComplementCode()
    {
        return $this->paymentComplementCode;
    }
    /**
     * @param string $paymentComplementCode
     */
    public function setPaymentComplementCode($paymentComplementCode)
    {
        $this->paymentComplementCode = $paymentComplementCode;
    }
    /**
     * @return string
     */
    public function getPaymentComplementCodeInfos()
    {
        return $this->paymentComplementCodeInfos;
    }
    /**
     * @param string $paymentComplementCodeInfos
     */
    public function setPaymentComplementCodeInfos($paymentComplementCodeInfos)
    {
        $this->paymentComplementCodeInfos = $paymentComplementCodeInfos;
    }
    /**
     * @return string
     */
    public function getPaymentIP()
    {
        return $this->paymentIP;
    }
    /**
     * @param string $paymentIP
     */
    public function setPaymentIP($paymentIP)
    {
        $this->paymentIP = $paymentIP;
    }
    /**
     * @return string
     */
    public function getPaymentAuthorization()
    {
        return $this->paymentAuthorization;
    }
    /**
     * @param string $paymentAuthorization
     */
    public function setPaymentAuthorization($paymentAuthorization)
    {
        $this->paymentAuthorization = $paymentAuthorization;
    }
    /**
     * @return \DateTime
     */
    public function getPaymentLastUpdate()
    {
        return $this->paymentLastUpdate;
    }
    /**
     * @param \DateTime $paymentLastUpdate
     */
    public function setPaymentLastUpdate($paymentLastUpdate)
    {
        $this->paymentLastUpdate = $paymentLastUpdate;
    }
    /**
     * @return boolean
     */
    public function isPaymentCaptureStatus()
    {
        return $this->paymentCaptureStatus;
    }
    /**
     * @param boolean $paymentCaptureStatus
     */
    public function setPaymentCaptureStatus($paymentCaptureStatus)
    {
        $this->paymentCaptureStatus = $paymentCaptureStatus;
    }
    /**
     * @return string
     */
    public function getPaymentCaptureDetails()
    {
        return $this->paymentCaptureDetails;
    }
    /**
     * @param string $paymentCaptureDetails
     */
    public function setPaymentCaptureDetails($paymentCaptureDetails)
    {
        $this->paymentCaptureDetails = $paymentCaptureDetails;
    }
    /**
     * @return \DateTime
     */
    public function getPaymentCaptureDate()
    {
        return $this->paymentCaptureDate;
    }
    /**
     * @param \DateTime $paymentCaptureDate
     */
    public function setPaymentCaptureDate($paymentCaptureDate)
    {
        $this->paymentCaptureDate = $paymentCaptureDate;
    }
    /**
     * @return string
     */
    public function getTopUpTxId()
    {
        return $this->topUpTxId;
    }
    /**
     * @param string $topUpTxId
     */
    public function setTopUpTxId($topUpTxId)
    {
        $this->topUpTxId = $topUpTxId;
    }
    /**
     * @return boolean
     */
    public function getTopUpStatus()
    {
        return $this->topUpStatus;
    }
    /**
     * @param boolean $topUpStatus
     */
    public function setTopUpStatus($topUpStatus)
    {
        $this->topUpStatus = $topUpStatus;
    }
    /**
     * @return string
     */
    public function getTopUpDetails()
    {
        return $this->topUpDetails;
    }
    /**
     * @param string $topUpDetails
     */
    public function setTopUpDetails($topUpDetails)
    {
        $this->topUpDetails = $topUpDetails;
    }
    /**
     * @return string
     */
    public function getTopUpErrorCode()
    {
        return $this->topUpErrorCode;
    }
    /**
     * @param string $topUpErrorCode
     */
    public function setTopUpErrorCode($topUpErrorCode)
    {
        $this->topUpErrorCode = $topUpErrorCode;
    }
    /**
     * @return \DateTime
     */
    public function getTopUpLastUpdate()
    {
        return $this->topUpLastUpdate;
    }
    /**
     * @param \DateTime $topUpLastUpdate
     */
    public function setTopUpLastUpdate($topUpLastUpdate)
    {
        $this->topUpLastUpdate = $topUpLastUpdate;
    }
    /**
     * @return string
     */
    public function getTopUpCreanceId()
    {
        return $this->topUpCreanceId;
    }
    /**
     * @param string $topUpCreanceId
     */
    public function setTopUpCreanceId($topUpCreanceId)
    {
        $this->topUpCreanceId = $topUpCreanceId;
    }
    /**
     * @return string
     */
    public function getTopUpCreanceJSON()
    {
        return $this->topUpCreanceJSON;
    }
    /**
     * @param string $topUpCreanceJSON
     */
    public function setTopUpCreanceJSON($topUpCreanceJSON)
    {
        $this->topUpCreanceJSON = $topUpCreanceJSON;
    }
    /**
     * @return string
     */
    public function getTopUpResponseJSON()
    {
        return $this->topUpResponseJSON;
    }
    /**
     * @param string $topUpResponseJSON
     */
    public function setTopUpResponseJSON($topUpResponseJSON)
    {
        $this->topUpResponseJSON = $topUpResponseJSON;
    }
    /**
     * @return string
     */
    public function getSmsTxtForReceiver()
    {
        return $this->smsTxtForReceiver;
    }
    /**
     * @param string $smsTxtForReceiver
     */
    public function setSmsTxtForReceiver($smsTxtForReceiver)
    {
        $this->smsTxtForReceiver = $smsTxtForReceiver;
    }
    /**
     * @return string
     */
    public function getEmailTxtForReceiver()
    {
        return $this->emailTxtForReceiver;
    }
    /**
     * @param string $emailTxtForReceiver
     */
    public function setEmailTxtForReceiver($emailTxtForReceiver)
    {
        $this->emailTxtForReceiver = $emailTxtForReceiver;
    }
    /**
     * @return string
     */
    public function getTmplTxtForReceiver()
    {
        return $this->tmplTxtForReceiver;
    }
    /**
     * @param string $tmplTxtForReceiver
     */
    public function setTmplTxtForReceiver($tmplTxtForReceiver)
    {
        $this->tmplTxtForReceiver = $tmplTxtForReceiver;
    }
    public function getTotal()
    {
        return $this->getPrice() + $this->getFee() - $this->getDiscount();
    }
    /**
     * @return string
     */
    public function getReceiverEmail()
    {
        return $this->receiverEmail;
    }
    /**
     * @param string $receiverEmail
     */
    public function setReceiverEmail($receiverEmail)
    {
        $this->receiverEmail = $receiverEmail;
    }
    /**
     * @return string
     */
    public function getContactName()
    {
        return $this->contactName;
    }
    /**
     * @param string $contactName
     */
    public function setContactName($contactName)
    {
        $this->contactName = $contactName;
    }
    /**
     * @return string
     */
    public function getSenderIP()
    {
        return $this->senderIP;
    }
    /**
     * @param string $senderIP
     */
    public function setSenderIP($senderIP)
    {
        $this->senderIP = $senderIP;
    }
    /**
     * @return string
     */
    public function getPaymentControls()
    {
        return $this->paymentControls;
    }
    /**
     * @param string $paymentControls
     */
    public function setPaymentControls($paymentControls)
    {
        $this->paymentControls = $paymentControls;
    }
    /**
     * @return string
     */
    public function getPaymentOneClickId()
    {
        return $this->paymentOneClickId;
    }
    /**
     * @param string $paymentOneClickId
     */
    public function setPaymentOneClickId($paymentOneClickId)
    {
        $this->paymentOneClickId = $paymentOneClickId;
    }
    /**
     * @return boolean
     */
    public function isCallback1()
    {
        return $this->callback1;
    }
    /**
     * @param boolean $callback1
     */
    public function setCallback1($callback1)
    {
        $this->callback1 = $callback1;
    }
    /**
     * @return boolean
     */
    public function isCallback2()
    {
        return $this->callback2;
    }
    /**
     * @param boolean $callback2
     */
    public function setCallback2($callback2)
    {
        $this->callback2 = $callback2;
    }
    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }
    /**
     * @param Customer $customer
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;
    }
    /**
     * @return string
     */
    public function getTopUpLocalInfoAmount()
    {
        return $this->topUpLocalInfoAmount;
    }
    /**
     * @param string $topUpLocalInfoAmount
     */
    public function setTopUpLocalInfoAmount($topUpLocalInfoAmount)
    {
        $this->topUpLocalInfoAmount = $topUpLocalInfoAmount;
    }
    /**
     * @return string
     */
    public function getTopUpLocalInfoCurrency()
    {
        return $this->topUpLocalInfoCurrency;
    }
    /**
     * @param string $topUpLocalInfoCurrency
     */
    public function setTopUpLocalInfoCurrency($topUpLocalInfoCurrency)
    {
        $this->topUpLocalInfoCurrency = $topUpLocalInfoCurrency;
    }
    /**
     * @return string
     */
    public function getPaymentAcquirerResponseCode()
    {
        return $this->paymentAcquirerResponseCode;
    }
    /**
     * @param string $paymentAcquirerResponseCode
     */
    public function setPaymentAcquirerResponseCode($paymentAcquirerResponseCode)
    {
        $this->paymentAcquirerResponseCode = $paymentAcquirerResponseCode;
    }
    /**
     * @return string
     */
    public function getPaymentHolderAuthentStatus()
    {
        return $this->paymentHolderAuthentStatus;
    }
    /**
     * @param string $paymentHolderAuthentStatus
     */
    public function setPaymentHolderAuthentStatus($paymentHolderAuthentStatus)
    {
        $this->paymentHolderAuthentStatus = $paymentHolderAuthentStatus;
    }
    /**
     * @return Lang
     */
    public function getLang()
    {
        return $this->lang;
    }
    /**
     * @param Lang $lang
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
    }
    /**
     * @return Country
     */
    public function getPaymentCardCountry()
    {
        return $this->paymentCardCountry;
    }
    /**
     * @param Country $paymentCardCountry
     */
    public function setPaymentCardCountry($paymentCardCountry)
    {
        $this->paymentCardCountry = $paymentCardCountry;
    }
    /**
     * @return Country
     */
    public function getPaymentIPCountry()
    {
        return $this->paymentIPCountry;
    }
    /**
     * @param Country $paymentIPCountry
     */
    public function setPaymentIPCountry($paymentIPCountry)
    {
        $this->paymentIPCountry = $paymentIPCountry;
    }
    /**
     * @return float
     */
    public function getTopUpWholesalePrice()
    {
        return $this->topUpWholesalePrice;
    }
    /**
     * @param float $topUpWholesalePrice
     */
    public function setTopUpWholesalePrice($topUpWholesalePrice)
    {
        $this->topUpWholesalePrice = $topUpWholesalePrice;
    }
    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }
    /**
     * @param string $service
     */
    public function setService($service)
    {
        $this->service = $service;
    }
    /**
     * @return string
     */
    public function getEnergieAccountId()
    {
        return $this->energieAccountId;
    }
    /**
     * @param string $energieAccountId
     */
    public function setEnergieAccountId($energieAccountId)
    {
        $this->energieAccountId = $energieAccountId;
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
     * @return string
     */
    public function getKycStatus()
    {
        return $this->kycStatus;
    }
    /**
     * @param string $kycStatus
     */
    public function setKycStatus($kycStatus)
    {
        $this->kycStatus = $kycStatus;
    }
    /**
     * @param string $idemiaIdentityId
     */
    public function setIdemiaIdentityId($idemiaIdentityId)
    {
        $this->idemiaIdentityId = $idemiaIdentityId;
    }
    /**
     * @return string
     */
    public function getIdemiaIdentityId()
    {
        return $this->idemiaIdentityId;
    }
    /**
     * @return string
     */
    public function getIdemiaIdentityDetails()
    {
        return $this->idemiaIdentityDetails;
    }
    /**
     * @param string $idemiaIdentityDetails
     */
    public function setIdemiaIdentityDetails($idemiaIdentityDetails)
    {
        $this->idemiaIdentityDetails = $idemiaIdentityDetails;
    }
    /**
     * @return string
     */
    public function getIdemiaSDKMode()
    {
        return $this->idemiaSDKMode;
    }
    /**
     * @param string $idemiaSDKMode
     */
    public function setIdemiaSDKMode($idemiaSDKMode)
    {
        $this->idemiaSDKMode = $idemiaSDKMode;
    }
} // end class

