<?php

namespace ApiBackendBundle\Entity;

use ApiFrontBundle\Entity\Customer;
use ApiFrontBundle\Entity\Lang;

/**
 * Class TransactionExport
 *
 */
class TransactionExport extends Transaction
{
    public const CLIENT_SUPPRIME = "client supprimé";
    public const NON_DISPONIBLE = "Non disponible";
    public const NON_UTILISE = "Non utilisé";

    /**
     * TransactionExport constructor.
     * @param Transaction $transaction
     * @param Customer $customer
     * @param Lang $lang
     */
    public function __construct($transaction, $customer, $paymentCardCountry, $paymentIPCountry, $product, $receiverCountryLabel)
    {
        parent::setId($transaction['id']);
        parent::setPaymentTxId($transaction['_paymentTxId']);
        if ($transaction['paymentAuthorization'] != null) {
            parent::setPaymentAuthorization($transaction['paymentAuthorization']);
        } else {
            parent::setPaymentAuthorization(self::NON_DISPONIBLE);
        }

        $this->createdDate = $transaction['createdAt'];
        if ($customer != null) {
            $this->emailSender = $customer->getEmail();
            $this->firstNameSender = $customer->getFirstName();
            $this->lastNameSender = $customer->getLastName();
            $this->countrySender = $customer->getCountry()->getCountryLabels()[0]->getWording();
            if ($customer->getOffer() == Customer::OFFER_TRAVELERS) {
                $this->numSender = $customer->getSim()->getId();
            } else {
                $this->numSender = $customer->getPhoneCode() . " " . $customer->getPhoneNumber();
            }
        } else {

            $sender = json_decode($transaction['senderJSON'], null, 512, JSON_THROW_ON_ERROR);
            if ($sender) {
                $this->emailSender = $sender->email;
                $this->firstNameSender = $sender->firstName;
                $this->lastNameSender = $sender->lastName;
                $this->countrySender = $sender->country->countryLabels[0]->wording;
                if ($sender->offer == 'travelers') {
                    $this->numSender = $sender->simId;
                } else {
                    $this->numSender = $sender->phoneCode . " " . $sender->phoneNumber;
                }
            } else {
                $this->emailSender = TransactionExport::CLIENT_SUPPRIME;
                $this->firstNameSender = TransactionExport::CLIENT_SUPPRIME;
                $this->lastNameSender = TransactionExport::CLIENT_SUPPRIME;
                $this->countrySender = TransactionExport::CLIENT_SUPPRIME;
                $this->numSender = TransactionExport::CLIENT_SUPPRIME;
            }
        }
        parent::setPaymentIP($transaction['paymentIP']);
        if ($transaction['service'] != Transaction::SERVICE_TRAVELSIMCARD && $transaction['receiverPhoneCode'] != null && $transaction['receiverPhoneNumber'] != null) {
            $this->numReceiver = $transaction['receiverPhoneCode'] . " " . $transaction['receiverPhoneNumber'];
        } elseif ($transaction['service'] == Transaction::SERVICE_TRAVELSIMCARD && $transaction['simId'] != null) {
            $this->numReceiver = $transaction['simId'];
        } else {
            $this->numReceiver = self::NON_DISPONIBLE;
        }

        if ($transaction['energieAccountId']) {
            parent::setEnergieAccountId($transaction['energieAccountId']);
            if ($product != null) {
                $this->productInfos = $product->getPackage()->getDays() . ' jours';
            } else {
                //Energie product got their nb of days in the reference
                $this->productInfos = substr($transaction['productReference'], strpos($transaction['productReference'], "BX") + 2) . ' jours';
            }
        } else {
            if ($transaction['energieAccountId'] == "energie") {
                parent::setEnergieAccountId(self::NON_DISPONIBLE);
            } else {
                parent::setEnergieAccountId(self::NON_UTILISE);

            }
        }

        if ($transaction['service'] == Transaction::SERVICE_TRAVELSIMCARD) {
            $this->opReceiver = 'Travel SIM Card';
            $this->countryReceiver = 'Europe';
            $this->deviseReceiver = 'EUR';

            try {
                $_product = json_decode($transaction['productJSON'], null, 512, JSON_THROW_ON_ERROR);
                $this->productInfos = $_product->name;
                $this->totalDeviseReceiver = $_product->selling_price;
            } catch (\Exception) {
                $this->productInfos = 'Non disponible';
                $this->totalDeviseReceiver = $transaction['price'];
            }

        } elseif (isset($transaction['topUpResponseJSON']) && $transaction['topUpResponseJSON'] != null) {
            $topupResponse = json_decode($transaction['topUpResponseJSON'], null, 512, JSON_THROW_ON_ERROR);
            $this->opReceiver = $this->getValueFromJson($topupResponse, 'operator');
            if ($this->opReceiver == TransactionExport::NON_DISPONIBLE && $product != null) {
                $this->opReceiver = $product->getOperator()->getWording();
            }

            $this->countryReceiver = $receiverCountryLabel;
            if ($product != null) {
                $this->countryReceiver = $product->getOperator()->getSaleCountry()->getCountry()->getCountryLabels()[0]->getWording();
            }
            $this->deviseReceiver = $this->getValueFromJson($topupResponse, 'local_info_currency');
            if ($this->deviseReceiver == TransactionExport::NON_DISPONIBLE && $product != null) {
                $this->deviseReceiver = $product->getOperator()->getCurrency();
            }
            $this->totalDeviseReceiver = $this->getValueFromJson($topupResponse, 'local_info_amount');
            if ($this->totalDeviseReceiver == TransactionExport::NON_DISPONIBLE && isset($transaction['topUpLocalInfoAmount'])) {
                $this->totalDeviseReceiver = $transaction['topUpLocalInfoAmount'];
            }
        } else {
            $this->opReceiver = TransactionExport::NON_DISPONIBLE;
            $this->countryReceiver = TransactionExport::NON_DISPONIBLE;
            $this->deviseReceiver = TransactionExport::NON_DISPONIBLE;
            $this->totalDeviseReceiver = TransactionExport::NON_DISPONIBLE;
        }

        parent::setPrice($transaction['price']);
        parent::setFee($transaction['fee']);
        $this->total = $transaction['price'] - $transaction['discount'] + $transaction['fee'];
        $this->statusLibelle = $transaction['status']['status'];
        $this->discount = $transaction['discount'];
        $this->buyingPrice = $transaction['price'];
        if ($this->promotion = $transaction['promotionCode'] != null) {
            $this->promotion = $transaction['promotionCode'];
        } else {
            $this->promotion = self::NON_UTILISE;
        }

        if ($transaction["paymentComplementCode"] != null) {
            $this->paymentComplementCode = $transaction["paymentComplementCode"];
        } else {
            $this->paymentComplementCode = self::NON_DISPONIBLE;
        }

        if ($transaction["lang"] != null) {
            $this->langueLibelle = $transaction["lang"]["local"];
        } else {
            $this->langueLibelle = self::NON_DISPONIBLE;
        }

        if ($transaction["topUpTxId"] != null) {
            $this->topUpTxId = $transaction["topUpTxId"];
        } else {
            $this->topUpTxId = self::NON_DISPONIBLE;
        }

        if ($transaction["topUpWholesalePrice"] != null) {
            $this->topUpWholesalePrice = $transaction["topUpWholesalePrice"];
        } else {
            $this->topUpWholesalePrice = self::NON_DISPONIBLE;
        }

        if ($paymentCardCountry != null) {
            $this->paymentCardCountry = $paymentCardCountry->getCountryLabels()[0]->getWording();
        } else {
            $this->paymentCardCountry = self::NON_DISPONIBLE;
        }

        if ($paymentIPCountry != null) {
            $this->paymentIpCountry = $paymentIPCountry->getCountryLabels()[0]->getWording();
        } else {
            $this->paymentIpCountry = self::NON_DISPONIBLE;
        }

        $this->service = $transaction['service'];

        if ($this->paymentResponseCode = $transaction['paymentResponseCode'] != null) {
            $this->paymentResponseCode = $transaction['paymentResponseCode'];
        }
        if ($this->paymentHolderAuthentStatus = $transaction['paymentHolderAuthentStatus'] != null) {
            $this->paymentHolderAuthentStatus = $transaction['paymentHolderAuthentStatus'];
        }
    }

    private $createdDate;
    private $emailSender;
    private $firstNameSender;
    private $lastNameSender;
    private $countrySender;
    private $numSender;
    private $numReceiver;
    private $opReceiver;
    private $countryReceiver;
    private $deviseReceiver;
    private $totalDeviseReceiver;
    private $total;
    private $discount;
    private $promotion;
    private $statusLibelle;
    private $langueLibelle;
    private $topUpTxId;
    private $topUpWholesalePrice;
    private $paymentCardCountry;
    private $paymentIpCountry;
    private $paymentComplementCode;
    private $paymentResponseCode;
    private $paymentHolderAuthentStatus;
    private $service;
    private $buyingPrice;
    private $productInfos;
    // private $fee;

    private function getValueFromJson($json, $param)
    {
        if (isset($json->$param)) {
            return $json->$param;
        } else {
            return TransactionExport::NON_DISPONIBLE;
        }

    }
}
