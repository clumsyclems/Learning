<?php

namespace ApiFrontBundle\Controller;

use ApiBackendBundle\Entity\Transaction;
use ApiBackendBundle\Service\ResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\FOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Controller\Annotations\Post;
use ApiFrontBundle\Annotation\CheckParam;
use ApiBackendBundle\Exception\SendExceptionAsResponse;
use ApiFrontBundle\Entity\Customer;

class ControllerBase extends FOSRestController
{

    private $_oToken;
    private $_oCustomer;

    private $_dayAmountAvailable;
    private $_monthAmountAvailable;
    private $_dayNumberAvailable;
    private $_monthNumberAvailable;

    private $_dayAmountLimit;
    private $_monthAmountLimit;
    private $_dayNumberLimit;
    private $_monthNumberLimit;

    private $_dayAmount;
    private $_monthAmount;
    private $_dayNumber;
    private $_monthNumber;

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->_oToken;
    }

    /**
     * @return mixed
     */
    public function getCustomer()
    {
        return $this->_oCustomer;
    }

    /**
     * @return mixed
     */
    public function getDayAmountAvailable()
    {
        return $this->_dayAmountAvailable;
    }

    /**
     * @return mixed
     */
    public function getMonthAmountAvailable()
    {
        return $this->_monthAmountAvailable;
    }

    /**
     * @return mixed
     */
    public function getDayNumberAvailable()
    {
        return $this->_dayNumberAvailable;
    }

    /**
     * @return mixed
     */
    public function getMonthNumberAvailable()
    {
        return $this->_monthNumberAvailable;
    }

    /**
     * @return mixed
     */
    public function getDayAmountLimit()
    {
        return $this->_dayAmountLimit;
    }

    /**
     * @return mixed
     */
    public function getMonthAmountLimit()
    {
        return $this->_monthAmountLimit;
    }

    /**
     * @return mixed
     */
    public function getDayNumberLimit()
    {
        return $this->_dayNumberLimit;
    }

    /**
     * @return mixed
     */
    public function getMonthNumberLimit()
    {
        return $this->_monthNumberLimit;
    }

    /**
     * @return mixed
     */
    public function getDayAmount()
    {
        return $this->_dayAmount;
    }

    /**
     * @return mixed
     */
    public function getMonthAmount()
    {
        return $this->_monthAmount;
    }

    /**
     * @return mixed
     */
    public function getDayNumber()
    {
        return $this->_dayNumber;
    }

    /**
     * @return mixed
     */
    public function getMonthNumber()
    {
        return $this->_monthNumber;
    }


    protected function checkIfTransactionBelongToCustomer($transaction) {
        $_oCust = $this->_oToken->getCustomer();
        if (is_null($_oCust) || !is_object($_oCust))
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_AUTHTOKEN_3'))), Response::HTTP_BAD_REQUEST);

        if ($_oCust->getId() !== $transaction->getCustomer()->getId())
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_ID_3'))), Response::HTTP_BAD_REQUEST);
    }


    // Generic method to check authentication Token for a rest method of the Front API
    protected function checkAuthToken(Request $request, $checkActiveStatus = false)
    {
        // if authtoken is given, we check its validity
        if(!is_null($request->get('authToken')) && !empty($request->get('authToken')))
        {
            $em = $this->getDoctrine()->getManager();
            $rpt = $em->getRepository('ApiFrontBundle:CustomerToken');
            $token = $rpt->findOneBy(array('token' => $request->get('authToken')));

            if (!is_object($token) || !$token->isValid())
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_AUTHTOKEN_2'))), Response::HTTP_BAD_REQUEST);

            $this->_oToken = $token;

            // Get the customer linked to the token
            $_oCust = $token->getCustomer();
            if (is_null($_oCust) || !is_object($_oCust))
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_AUTHTOKEN_3'))), Response::HTTP_BAD_REQUEST);

            $this->_oCustomer = $_oCust;
            if ($checkActiveStatus) {
                if ($this->_oCustomer->getStatus() != Customer::STATUS_ACTIVE)
                    throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_AUTHTOKEN_4'))), Response::HTTP_BAD_REQUEST);
            }
        } // end if(!is_null($request->get('authToken')) && !empty($request->get('authToken')))

    } // end method private function checkAuthToken()

    // Generic method to check Customer Order Ability for a rest method of the Front API
    protected function checkOrderAbility()
    {
        $logger = $this->get('logger');

        if(!is_object($this->_oCustomer) || $this->_oCustomer->getStatus()!=Customer::STATUS_ACTIVE)
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_AUTHTOKEN_4'))), Response::HTTP_BAD_REQUEST);

        $this->initLimitValues();

        // Test Limit by amount and day
        if($this->_dayAmount>=$this->_dayAmountLimit)
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_LIMIT_1','amount' => $this->_dayAmount,'limit' => $this->_dayAmountLimit))), Response::HTTP_BAD_REQUEST);

        // Test Limit by amount and month
        if($this->_monthAmount>=$this->_monthAmountLimit)
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_LIMIT_2','amount' => $this->_monthAmount,'limit' => $this->_monthAmountLimit))), Response::HTTP_BAD_REQUEST);

        // Test Limit by number and day
        if($this->_dayNumber>=$this->_dayNumberLimit)
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_LIMIT_3','orders' => $this->_dayNumber,'limit' => $this->_dayNumberLimit))), Response::HTTP_BAD_REQUEST);

        // Test Limit by number and month
        if($this->_monthNumber>=$this->_monthNumberLimit)
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_LIMIT_4','orders' => $this->_monthNumber,'limit' => $this->_monthNumberLimit))), Response::HTTP_BAD_REQUEST);

    }// end f

    public function initLimitValues()
    {

        $logger = $this->get('logger');

        $_oRpParam = $this->getDoctrine()->getRepository('ApiBackendBundle:Parameter');
        $_oDayAmountLimitParameter = $_oRpParam->findTopUpLimitAmountAndDay();
        if (!is_object($_oDayAmountLimitParameter)||!is_numeric($_oDayAmountLimitParameter->getValue())){
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            $logger->error('Base controller - Unable to load parameter TopUpLimitAmountAndDay');
        }
        $_oMonthAmountLimitParameter = $_oRpParam->findTopUpLimitAmountAndMonth();
        if (!is_object($_oMonthAmountLimitParameter)||!is_numeric($_oMonthAmountLimitParameter->getValue())){
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            $logger->error('Base controller - Unable to load parameter TopUpLimitAmountAndMonth');
        }
        $_oDayNumberLimitParameter = $_oRpParam->findTopUpLimitNumberAndDay();
        if (!is_object($_oDayNumberLimitParameter)||!is_numeric($_oDayNumberLimitParameter->getValue())){
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            $logger->error('Base controller - Unable to load parameter TopUpLimitNumberAndDay');
        }
        $_oMonthNumberLimitParameter = $_oRpParam->findTopUpLimitNumberAndMonth();
        if (!is_object($_oMonthNumberLimitParameter)||!is_numeric($_oMonthNumberLimitParameter->getValue())){
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            $logger->error('Base controller - Unable to load parameter TopUpLimitNumberAndMonth');
        }

        $_oRpAmounts = $this->getDoctrine()->getRepository('ApiFrontBundle:Customer');
        $_dDayAmount = $_oRpAmounts->getDayAmountByCustomer($this->_oCustomer);
        $_dMonthAmount = $_oRpAmounts->getMonthAmountByCustomer($this->_oCustomer);
        $_dDayNumber = $_oRpAmounts->getDayNumberByCustomer($this->_oCustomer);
        $_dMonthNumber = $_oRpAmounts->getMonthNumberByCustomer($this->_oCustomer);

        $this->_dayAmount = $_dDayAmount;
        $this->_monthAmount = $_dMonthAmount;
        $this->_dayNumber = $_dDayNumber;
        $this->_monthNumber = $_dMonthNumber;

        $this->_dayAmountAvailable = floatval($_oDayAmountLimitParameter->getValue()) - $_dDayAmount;
        $this->_monthAmountAvailable = floatval($_oMonthAmountLimitParameter->getValue()) - $_dMonthAmount;
        $this->_dayNumberAvailable = intval($_oDayNumberLimitParameter->getValue()) - $_dDayNumber;
        $this->_monthNumberAvailable = intval($_oMonthNumberLimitParameter->getValue()) - $_dMonthNumber;

        $this->_dayAmountLimit = floatval($_oDayAmountLimitParameter->getValue());
        $this->_monthAmountLimit = floatval($_oMonthAmountLimitParameter->getValue());
        $this->_dayNumberLimit = intval($_oDayNumberLimitParameter->getValue());
        $this->_monthNumberLimit = intval($_oMonthNumberLimitParameter->getValue());

    }


    public function buildTransactionStatusArray(Transaction $_oTx, $_lng)
    {

        $logger = $this->get('logger');

        // We need order status objects
        $rptxs = $this->getDoctrine()->getRepository('ApiBackendBundle:TransactionStatus');
        $_oStatusPaymentOk = $rptxs->findPaymentOK();
        $_oStatusInit = $rptxs->findInit();
        $_oStatusPaymentKo = $rptxs->findPaymentKO();
        $_oStatusPendindTopUp = $rptxs->findPendingTopUp();
        $_oStatusTopUpKo = $rptxs->findTopUpKO();
        $_oStatusSuccess = $rptxs->findSuccess();
        $_oStatusCaptureKo = $rptxs->findCaptureKO();
        $_oStatusClosed = $rptxs->findClosed();
        $_oStatusKYCNeeded = $rptxs->findKYCNeeded();
        $_oStatusKYCKo = $rptxs->findKYCKO();

        $_strErrorMsg = '';
        $productInfos = "";
        $productInfosType = "";
        $productLabel = "";

        if (
            !is_object($_oStatusPaymentOk) || !is_object($_oStatusInit) || !is_object($_oStatusPaymentKo) || !is_object($_oStatusPendindTopUp)
            || !is_object($_oStatusTopUpKo) || !is_object($_oStatusSuccess) || !is_object($_oStatusCaptureKo) || !is_object($_oStatusClosed) || !is_object($_oStatusKYCNeeded) || !is_object($_oStatusKYCKo)
        ) {
            $logger->error("Transaction API - method postTransactionStatusAction - can not load order statuses");
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        } // end if

        // ----------------------------- Check status
        $_oTxStatus = $_oTx->getStatus();
        // success | captureko | closed -> send success
        if ($_oTxStatus == $_oStatusSuccess || $_oTxStatus == $_oStatusClosed || $_oTxStatus == $_oStatusCaptureKo) {
            $_strStatus = "success";
        }

        // paymentko | topupko | Unmanaged -> send error
        elseif ($_oTxStatus == $_oStatusTopUpKo || $_oTxStatus == $_oStatusPaymentKo || $_oTxStatus == $_oStatusKYCKo) {
            $_strStatus = "failed";
            $_TxService = $this->getTransactionService();
            $_strErrorMsg = $_TxService->defineErrorMessage($_oTx, $_lng);
        }
        elseif ($_oTxStatus == $_oStatusKYCNeeded) {
            $_strStatus = "kycRequired";
        }
        // case else should be pending top up or payment ok or init but in case of we have a else
        // payment ok & pendingtopup -> send pending top up, to call later...
        // init should only be for callback API method
        else {
            $_strStatus = "pending";
        }

        $_oCountry = null;
        if (!is_null($_oTx->getReceiverCountry()) && !empty($_oTx->getReceiverCountry())) {
            $_oCountry = json_decode($_oTx->getReceiverCountry(), null, 512, JSON_THROW_ON_ERROR);
        }

        $_strOperator = "Unkown";

        if ($_oTx->getService() == Transaction::SERVICE_AIRTIME) {
            $_aProdRef = explode('-', $_oTx->getProductReference());
            if (!is_array($_aProdRef) || count($_aProdRef) < 4 || count($_aProdRef) > 6) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            }

            $rpOp = $this->getDoctrine()->getRepository('ApiBackendBundle:Operator');
            $_oOperator = $rpOp->findOneById($_aProdRef[0] . '-' . $_aProdRef[1] . '-' . $_aProdRef[2]);
            if (!is_null($_oOperator) && is_object($_oOperator)) {
                $_strOperator = $_oOperator->getWording();
            }
        } elseif ($_oTx->getService() == Transaction::SERVICE_TRAVEL_ESIM) {
            $_strOperator = 'Orange Travel';
        } elseif ($_oTx->getService() == Transaction::SERVICE_TRAVELSIMCARD) {
            $_strOperator = 'Orange Travel SIM Card';
        }

        $_aCountry = null;
        if (is_object($_oCountry)) {
            $_aCountry = array(
                'id' => $_oCountry->id,
                'phonecode' => $_oCountry->phonecode,
                'isOrange' => $_oCountry->isOrange,
                'iso' => $_oCountry->iso,
            );
        }

        $_strSimId = "";
        if (!empty($_oTx->getSimId())) {
            $_strSimId = $_oTx->getSimId();
        }

        $_aTx = array(
            "id" => $_oTx->getId(),
            "status" => $_strStatus,
            "service" => $_oTx->getService(),
            "errorMessage" => $_strErrorMsg,
            "date" => $_oTx->getCreatedAt()->getTimestamp(),
            "prefix" => $_oTx->getReceiverPhoneCode(),
            "phone" => $_oTx->getReceiverPhoneNumber(),
            "simId" => $_strSimId,
            "contact" => $_oTx->getContactName(),
            "country" => $_aCountry,
            "productReference" => $_oTx->getProductReference(),
            "productPrice" => $_oTx->getPrice(),
            "taxAndFee" => $_oTx->getFee(),
            "discount" => $_oTx->getDiscount(),
            "promoCode" => $_oTx->getPromotionCode(),
            "promoOrangeDesc" => $_oTx->getPromoOrangeDesc(),
            "total" => $_oTx->getTotal(),
            "operator" => $_strOperator,
            "localAmount" => $_oTx->getPromoOrangeValue() ?: $_oTx->getTopUpLocalInfoAmount(),
            "localCurrency" => $_oTx->getTopUpLocalInfoCurrency(),
            "productInfosType" => $productInfosType,
            "productInfos" => $productInfos,
            "productLabel" => $productLabel,
        );

        if($_oTx->getService()==Transaction::SERVICE_TRAVEL_ESIM){
            $_aTx['simOffer'] = json_decode($_oTx->getProductJSON(), true, 512, JSON_THROW_ON_ERROR);
        } // end if($_oTx->getService()==Transaction::SERVICE_TRAVEL_ESIMSERVICE_){

        return $_aTx;

    } // end private function buildTransactionStatusArray(Transaction $_oTx)

} // end of class
