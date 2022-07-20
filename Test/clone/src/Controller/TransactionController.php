<?php

namespace ApiFrontBundle\Controller;

use ApiBackendBundle\Entity\CustomEntity;
use ApiBackendBundle\Entity\Lang;
use ApiBackendBundle\Entity\SimOffer;
use ApiBackendBundle\Entity\Supplier;
use ApiBackendBundle\Entity\Transaction;
use ApiBackendBundle\Entity\TransactionExport;
use ApiBackendBundle\Entity\User;
use ApiBackendBundle\Exception\SendExceptionAsResponse;
use ApiBackendBundle\Repository\SimOfferRepository;
use ApiBackendBundle\Service\ESimBinariesService;
use ApiBackendBundle\Service\TransactionService;
use ApiBackendBundle\Service\TravelSimCardService;
use ApiFrontBundle\Annotation\CheckParam;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Get;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends ControllerBase
{
    public const CARD_ORDER = "CARDORDER";
    public const THREE_D_SECURE_AND_ORDER = "THREEDSECUREANDORDER";

    protected function getTravelSimCardService(): object|\TravelSimCardService
    {
        return $this->get('api_backend.travel_sim_card_service');
    }

    protected function getSimOfferService(): object|\TravelSimCardService
    {
        return $this->get('api_backend.simoffer_service');
    }

    protected function getESimBinariesService(): \ESimBinariesService|object{
        return $this->get('api_backend.esim_binaries_service');
    }

    protected function getTransactionService(): object|\TransactionService
    {
        return $this->get('api_backend.transaction_service');
    }

    /**
     * @ApiDoc(
     *     description="Initialize a new transaction",
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    },
     *    parameters={
     *      {"name"="lng", "dataType"="string", "required"=true, "description"="language / [a-zA-Z_]{5}"},
     *      {"name"="prefix", "dataType"="string", "required"=false, "description"="phone region code / [0-9]{1,5}"},
     *      {"name"="phone", "dataType"="string", "required"=false, "description"="phone number / [0-9]{6,15}"},
     *      {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *      {"name"="productReference", "dataType"="string", "required"=true, "description"="product reference / .{1,40}"},
     *      {"name"="paymentReturnUrl", "dataType"="string", "required"=true, "description"="website url for payment return / .{1,255}"},
     *      {"name"="SmsTxtForReceiver", "dataType"="string", "required"=false, "description"="SMS Txt to be sent to the receiver / .{1,255}"},
     *      {"name"="emailTxtForReceiver", "dataType"="string", "required"=false, "description"="Email Txt to be sent to the receiver / .{1,255}"},
     *      {"name"="senderIP", "dataType"="string", "required"=false, "description"="receiver IP/ [0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}"},
     *      {"name"="receiverEmail", "dataType"="string", "required"=false, "description"="Receiver email / .{1,255}"},
     *      {"name"="promoCode", "dataType"="string", "required"=false, "description"="Optional promotion code / .{1,80}"},
     *      {"name"="service", "dataType"="string", "required"=true, "description"="service / (travelsimcard)|(airtime)|(esim)"},
     *      {"name"="simId", "dataType"="string", "required"=false, "description"="SIM ID / [0-9]{10,13}"}
     *    }
     * )
     *
     * @CheckParam(name="lng", regex="/[a-zA-Z_]{5}/", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="prefix", regex="/[0-9]{1,5}/", required="false", errCode="ERR_PREFTEL_1")
     * @CheckParam(name="phone", regex="/[0-9]{6,15}/", required="false", errCode="ERR_TEL_1")
     * @CheckParam(name="authToken", regex="/.{1,40}/", errCode="ERR_AUTHTOKEN_1")
     * @CheckParam(name="productReference", regex="/.{1,40}/", errCode="ERR_PRODUCT_1")
     * @CheckParam(name="SmsTxtForReceiver", length="70", required="false", errCode="ERR_SMSTXT_1")
     * @CheckParam(name="emailTxtForReceiver", length="255", required="false", errCode="ERR_EMAILTXT_1")
     * @CheckParam(name="tmplTxtForReceiver", length="30", required="false", errCode="ERR_EMAILTMPL_1")
     * @CheckParam(name="receiverEmail", length="255", required="false", errCode="ERR_RECEIVER_EMAIL_1")
     * @CheckParam(name="senderIP", regex="/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", required="false", errCode="ERR_SENDERIP_1")
     * @CheckParam(name="promoCode", length="80", required="false", errCode="ERR_PROMOTION_CODE")
     * @CheckParam(name="simId", regex="/[0-9]{13}/", errCode="ERR_SIMID_1", required="false")
     * @CheckParam(name="service", regex="/(travelsimcard)|(airtime)|(esim)/", errCode="ERR_SERVICE_1")
     *
     * @Post("/transaction/init")
     * @param $request
     * @return Response
     */
    public function postTransactionInitAction(Request $request)
    {

        $view = null;
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();

        // check token and order ability
        $this->checkAuthToken($request);
        $this->checkOrderAbility();
        $_oCust = $this->getCustomer();
        $soldCountry = null;
        $supplier = null;

        $service = $request->get('service');

        // Check input values
        [$_oT2, $_oOLTravelers, $_aProdRef, $lng, $lngFr, $_oSim, $_oTrvlESIM, $_aeSimOffer] = $this->transactionInitCheckInputs($request, $service, $logger);

        $rptxs = $this->getDoctrine()->getRepository('ApiBackendBundle:TransactionStatus');
        $_oStatusInit = $rptxs->findInit();

        // create new pending transaction
        $_oTx = new Transaction();
        $_oTx->setStatus($_oStatusInit);
        $_oTx->setContactName('');
        $_oTx->setCustomer($_oCust);
        $serializer = $this->get('jms_serializer');
        $_jsonCust = $serializer->serialize($_oCust, 'json');
        $_oTx->setSenderJSON($_jsonCust);
        $_oTx->setLastEditor(CustomEntity::SYSTEM);
        $_oTx->setPaymentOneClickId($_oCust->getPaymentOneClickId());
        $_oTx->setLang($lng);
        $_oTx->setService($service);

        if (!empty($request->get('senderIP'))) {
            $_oTx->setSenderIP($request->get('senderIP'));
        }

        if (is_null($request->get('paymentReturnUrl')) && $this->getUser()->getRoles()[0] !== User::ROLE_FRONT_APP_TRAVELERS) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PAYMENT_2'))), Response::HTTP_BAD_REQUEST);
        }

        if (!is_null($request->get('SmsTxtForReceiver')) && !empty($request->get('SmsTxtForReceiver'))) {
            $_oTx->setSmsTxtForReceiver($request->get('SmsTxtForReceiver'));
        }

        $_bCheckForReceiverEmail = false;
        if (!is_null($request->get('emailTxtForReceiver')) && !empty($request->get('emailTxtForReceiver'))) {
            $_oTx->setEmailTxtForReceiver($request->get('emailTxtForReceiver'));
            $_bCheckForReceiverEmail = true;
        }
        if (!is_null($request->get('receiverEmail')) && !empty($request->get('receiverEmail'))) {
            $_oTx->setReceiverEmail($request->get('receiverEmail'));
            $_bCheckForReceiverEmail = true;
        }
        if (!is_null($request->get('tmplTxtForReceiver')) && !empty($request->get('tmplTxtForReceiver'))) {
            $_oTx->setTmplTxtForReceiver($request->get('tmplTxtForReceiver'));
            $_bCheckForReceiverEmail = true;
        }
        // if only one parameter of the receiver mail is filled => error
        if ($_bCheckForReceiverEmail && (empty($request->get('receiverEmail')) || empty($request->get('emailTxtForReceiver')))) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_EMAILTXT_2'))), Response::HTTP_BAD_REQUEST);
        }

        if ($service == Transaction::SERVICE_AIRTIME) {
            $supplier = $this->transactionInitAirtimeLogic($request, $_oTx, $_aProdRef, $_oT2);
        } // end if($service == 'SERVICE_AIRTIME')
        elseif ($service == Transaction::SERVICE_TRAVEL_ESIM) {
            $this->transactionInitESIMLogic($request, $_oTx, $_oTrvlESIM, $_aeSimOffer);
        } // end if($service == 'SERVICE_TRAVELSIMCARD')
        elseif ($service == Transaction::SERVICE_TRAVELSIMCARD) {
            $this->transactionInitTravelersLogic($request, $_oTx, $_aProdRef, $_oOLTravelers, $_oSim);
        } // end if($service == 'SERVICE_TRAVELSIMCARD')
        else
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_INTERNAL_SERVER_ERROR);

        // Can not manage products less than 1 euros because of Mercanet limit
        if ($_oTx->getTotal() < 1) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PRODUCT_5'))), Response::HTTP_BAD_REQUEST);
        }

        // everything is ok until now, last check, can the customer top up this time with the limits ?
        if ($this->getDayAmountAvailable() < $_oTx->getTotal()) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_LIMIT_1', 'amount' => $this->getDayAmount() + $_oTx->getTotal(), 'limit' => $this->getDayAmountLimit()))), Response::HTTP_BAD_REQUEST);
        }

        if ($this->getMonthAmountAvailable() < $_oTx->getTotal()) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_LIMIT_2', 'amount' => $this->getMonthAmount() + $_oTx->getTotal(), 'limit' => $this->getMonthAmountLimit()))), Response::HTTP_BAD_REQUEST);
        }

        // Retrieve and populate Sold Country
        if ($service == Transaction::SERVICE_TRAVELSIMCARD|| $service == Transaction::SERVICE_TRAVEL_ESIM) {
            $_oTx->setReceiverCountry(null);
        } else {
            $soldCountry = $this->transactionInitFillCountry($request, $_aProdRef, $supplier, $logger, $_oTx);
        }

        // for optimizations reasons, we populate a json field to be able to export datas
        // in case of failure before topup
        // need to load country, operator, currency...
        [$_oTmp, $_prodOperator] = $this->transactionInitFillTmpTopUpResponseJSON($soldCountry, $lngFr, $service, $_aProdRef);

        // save json top up response
        $_oTx->setTopUpResponseJSON(json_encode($_oTmp, JSON_THROW_ON_ERROR));

        // fill contact if a contact was linked to the receiver phone number...
        $rpsc = $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction');
        $_strContactName = $rpsc->findContactForAReceiver($_oTx);
        $_oTx->setContactName($_strContactName);

        // Manage discounts...
        $_oTx->setDiscount(0);
        if ($service != Transaction::SERVICE_TRAVELSIMCARD && $service != Transaction::SERVICE_TRAVEL_ESIM) {
            [$_strPromoCode, $_strPromoStatus, $_strPromoDescription] = $this->transactionInitSetDiscounts($request, $_prodOperator, $logger, $_oTx);
        }

        $em->persist($_oTx);
        $em->flush();
        $_oMercanetService = $this->get('api_backend.mercanet_service');
        if ($this->getUser()->getRoles()[0] === User::ROLE_FRONT_SITE_TRAVELERS) {

            //generate Mercanet payment panel
            $_strMercanetPanel = $_oMercanetService->initMercanet($_oTx, $request->get('paymentReturnUrl'), $request->get('lng'));

            // Update mercanet service system status
            $ServiceSystem = $this->getDoctrine()->getRepository('ApiBackendBundle:System')->findOneByCode($_oMercanetService->getSystemCode());
            $ServiceSystem->setStatus($_oMercanetService->getStatus());
            $em->persist($ServiceSystem);
            $em->flush();

            /*if ($_oMercanetService->getStatus() == false) {
                $logger->error("Transaction API - method init - error calling Mercanet service init method");
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PAYMENT_3'))), Response::HTTP_BAD_REQUEST);
            }*/

            $view = $this->view(array(
                'status' => 'OK',
                'detail' => array(
                    "amount" => $_oTx->getPrice(),
                    "fee" => $_oTx->getFee(),
                    "discount" => $_oTx->getDiscount(),
                    "total" => $_oTx->getTotal(),
                    "promoCode" => $_strPromoCode,
                    "promoStatus" => $_strPromoStatus,
                    "promoDescription" => $_strPromoDescription,
                    "promoOrangeDesc" => $_oTx->getPromoOrangeDesc(),
                    "promoOrangeValue" => $_oTx->getPromoOrangeValue(),
                    "paymentSection" => $_strMercanetPanel
                ),
            ));
        } elseif ($this->getUser()->getRoles()[0] === User::ROLE_FRONT_APP_TRAVELERS) {
            // call to mercanet init mobile payment
            $result = $_oMercanetService->initMobilePayment($_oTx, self::THREE_D_SECURE_AND_ORDER, $request->get('lng'));
            if (is_null($result)) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Update mercanet service system status
            $ServiceSystem = $this->getDoctrine()->getRepository('ApiBackendBundle:System')->findOneByCode($_oMercanetService->getSystemCode());
            $ServiceSystem->setStatus($_oMercanetService->getStatus());
            $em->persist($ServiceSystem);
            $em->flush();

            $details = array(
                "orderId" => $_oTx->getId(),
                "amount" => $_oTx->getPrice(),
                "fee" => $_oTx->getFee(),
                "discount" => $_oTx->getDiscount(),
                "total" => $_oTx->getTotal(),
                "promoCode" => $_strPromoCode,
                "promoStatus" => $_strPromoStatus,
                "promoDescription" => $_strPromoDescription,
                "promoOrangeDesc" => $_oTx->getPromoOrangeDesc(),
                "promoOrangeValue" => $_oTx->getPromoOrangeValue(),
                "paymentSection" => null
            );
            $details += $result;
            $view = $this->view(array(
                'status' => 'OK',
                'detail' => $details,
            ));
        }
        return $this->handleView($view);
    } // end of method postTransactionInitAction

    /**
     * @ApiDoc(
     *     description="Get status for a transaction",
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    },
     *    parameters={
     *      {"name"="lng", "dataType"="string", "required"=true, "description"="language / [a-zA-Z_]{5}"},
     *      {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *      {"name"="data", "dataType"="string", "required"=true, "description"="MERCANET DATA FIELD / .+"},
     *      {"name"="seal", "dataType"="string", "required"=true, "description"="MERCANET SEAL FIELD / .{1,64}"}
     *    }
     * )
     *
     * @CheckParam(name="lng", regex="/[a-zA-Z_]{5}/", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="authToken", regex="/.{1,40}/", errCode="ERR_AUTHTOKEN_1")
     * @CheckParam(name="data", length="3000", errCode="ERR_DATA_1")
     * @CheckParam(name="seal", regex="/.{1,64}/ ",errCode="ERR_DATA_3")
     * @Post("/transaction/status")
     * @param $request
     * @return Response
     */
    public function postTransactionStatusAction(Request $request)
    {

        $logger = $this->get('logger');

        // check token and order ability
        $this->checkAuthToken($request);

        // ----------------------------- extract Data Field and load transaction object
        $_oMercanetService = $this->get('api_backend.mercanet_service');
        $_oTx = $_oMercanetService->getTransactionFromData($request->get('data'), $request->get('seal'));
        if (!is_object($_oTx)) {
            $logger->error("Transaction API - method postTransactionStatusAction - can not load transaction from Data string");
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_DATA_2'))), Response::HTTP_BAD_REQUEST);
        } //end if(!is_object($_oTx))

        // Send transaction properties
        $view = $this->view(array(
            'status' => 'OK',
            'detail' => $this->buildTransactionStatusArray($_oTx, $request->get('lng')),
        ));
        return $this->handleView($view);
    } // end of method postTransactionStatusAction

    /**
     * @ApiDoc(
     *     description="Set Contact for a transaction",
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    },
     *    parameters={
     *      {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *      {"name"="orderid", "dataType"="string", "required"=true, "description"="Order Id / .{1,32}"},
     *      {"name"="contact", "dataType"="string", "required"=true, "description"="Contact name / .{1,255}"}
     *    }
     * )
     *
     * @CheckParam(name="authToken", regex="/.{1,40}/", errCode="ERR_AUTHTOKEN_1")
     * @CheckParam(name="orderid", length="32", errCode="ERR_ORDER_ID_1")
     * @CheckParam(name="contact", length="255", errCode="ERR_CONTACT_1")
     *
     * @Post("/transaction/contact")
     * @param $request
     * @return Response
     */
    public function postTransactionContactAction(Request $request)
    {

        // ----------------------------- load required objects
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();

        // check token and order ability
        $this->checkAuthToken($request);

        // load order from id
        $_oRpTx = $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction');
        $_oTx = $_oRpTx->findOneById($request->get('orderid'));

        // Case order not found
        if (is_null($_oTx) || !is_object($_oTx)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_ID_2'))), Response::HTTP_BAD_REQUEST);
        }

        // Case order does not belong to customer...
        if ($_oTx->getCustomer() == null || $_oTx->getCustomer()->getId() != $this->getCustomer()->getId()) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_ID_3'))), Response::HTTP_BAD_REQUEST);
        }

        // Order is found, update it with contact name...
        $_oTx->setContactName($request->get('contact'));
        $em->persist($_oTx);
        $em->flush();

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => array(),
        ));

        return $this->handleView($view);
    } // end of method postTransactionStatusAction
    /**
     * @ApiDoc(
     *     description="Set Contact for a transaction",
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    },
     *    parameters={
     *      {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *      {"name"="orderid", "dataType"="string", "required"=true, "description"="Order Id / .{1,32}"}
     *    }
     * )
     *
     * @CheckParam(name="authToken", regex="/.{1,40}/", errCode="ERR_AUTHTOKEN_1")
     * @CheckParam(name="orderid", length="32", errCode="ERR_TX_1")
     * @CheckParam(name="type", regex="/(qrcode)|(pdf)|(csv)/", errCode="ERR_TYPE_1")
     *
     * @Post("/transaction/esim-binary")
     * @return Response
     * @throws SendExceptionAsResponse
     * @throws \Endroid\QrCode\Exceptions\ImageFunctionFailedException
     * @throws \Endroid\QrCode\Exceptions\ImageFunctionUnknownException
     * @throws \PhpOffice\PhpWord\Exception\CopyFileException
     * @throws \PhpOffice\PhpWord\Exception\CreateTemporaryFileException
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    public function postTransactionESIMPDFAction(Request $request)
    {

        $data = null;
        // ----------------------------- load required objects
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();

        $rptxs = $this->getDoctrine()->getRepository('ApiBackendBundle:TransactionStatus');
        /** @var SimOfferRepository $repSimOffer */
        $repSimOffer = $this->getDoctrine()->getRepository('ApiBackendBundle:SimOffer');
        $_oStatusPaymentOk = $rptxs->findPaymentOK();
        $_oStatusSuccess = $rptxs->findSuccess();
        $_oStatusKYCKO = $rptxs->findKYCKO();
        $_oStatusClosed = $rptxs->findClosed();

        // check token and order ability
        $this->checkAuthToken($request);

        // load order from id
        $_oRpTx = $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction');
        $_oTx = $_oRpTx->findOneById($request->get('orderid'));

        // Case order not found
        if (is_null($_oTx) || !is_object($_oTx)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_TX_2'))), Response::HTTP_BAD_REQUEST);
        }

        // Case order does not belong to customer...
        if ($_oTx->getCustomer() == null || $_oTx->getCustomer()->getId() != $this->getCustomer()->getId()) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_TX_3'))), Response::HTTP_BAD_REQUEST);
        }

        // Order is found, if status is success or closed send the pdf binary
        if ($_oTx->getStatus()!=$_oStatusSuccess && $_oTx->getStatus()!=$_oStatusClosed) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_TX_4'))), Response::HTTP_BAD_REQUEST);
        }

        $_oResponse = $this->getTravelSimCardService()->getESIMCodes($_oTx->getTopUpTxId(), $_oTx->getId());
        $codeHttp = $_oResponse->code;
        // Cas classique, tout est ok, le profil est provisionné, on récupère les codes et on traite
        if ($codeHttp === Response::HTTP_OK) {

            $activationESIMCode = $_oResponse->result->detail->eSimProfile->activationCode;
            $adresseSmDp = $_oResponse->result->detail->eSimProfile->smdpplus;
            $codePuk = $_oResponse->result->detail->eSimProfile->puk;
            $referenceOffer= $_oTx->getProductReference();
            $msisdn = substr($_oResponse->result->detail->eSimProfile->msisdn, 1);
            $nsce = $_oResponse->result->detail->eSimProfile->nsce;
            $smdpplus = $_oResponse->result->detail->eSimProfile->smdpplus;
            $expirationDate= $_oResponse->result->detail->eSimProfile->expirationDate;

            $details = array();
            if($request->get('type')=='csv'){
                $details = array(
                    'msisdn' => $msisdn,
                    //Todo: voir comment gérer le préfixe et le code PIN pour les futurs pays
                    'prefix' => "33",
                    'pincode' => "0000",
                    'activationCode' => $activationESIMCode,
                    'puk' => $codePuk,
                    'nsce' => $nsce,
                    'smdpplus' => $smdpplus,
                    'expirationDate' => $expirationDate
                );
            }
            elseif($request->get('type')=='pdf'){

                try {
                    $dataSimOffer = $repSimOffer->getDataSimOfferByRereference($referenceOffer);
                    if($dataSimOffer != null){
                        $data = $dataSimOffer['gross_data_volume'];
                    }
                    // call TrvlSimCard to get pdf
                    $response = $this->getTravelSimCardService()->getESIMPDF($activationESIMCode, $adresseSmDp, $codePuk, $data, $referenceOffer);
                    if ($response->code === Response::HTTP_OK) {
                        $binary = $response->result->detail->binary;
                    } else {
                        throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }catch (\Exception){
                    throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_INTERNAL_SERVER_ERROR);
                }

            } // end if($request->get('type')=='pdf'){
            if($request->get('type')=='qrcode'){
                $details += array('id' => $_oTx->getId(), 'binary' =>$this->getESimBinariesService()->generateQrCode($activationESIMCode), 'mimetype' => 'image/png');
            } // end if($request->get('type')=='qrcode'){

            $details += array('id' => $_oTx->getId(), 'binary'=> $binary, 'mimetype' => 'application/pdf');
            $view = $this->view(array(
                'status' => 'OK',
                'detail' => $details
            ));

            return $this->handleView($view);

        } // end if ($codeHttp === Response::HTTP_OK)
        // Souci dans la récupération des codes, on essaie de savoir si c'est ponctuel ou permanent
        else {
            if(isset($_oResponse->result->detail[0]->errCode) && $_oResponse->result->detail[0]->errCode=="ERR_RETRY_LATER")
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_RETRY_LATER'))), Response::HTTP_NOT_FOUND);
            else
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ACTIVATION_CODE'))), Response::HTTP_INTERNAL_SERVER_ERROR);
        } // end else from if ($codeHttp === Response::HTTP_OK)

    } // end of method postTransactionESIMPDFAction

    /**
     * @ApiDoc(
     *     description="Get history of transactions for a user",
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    },
     *    parameters={
     *      {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *      {"name"="page", "dataType"="string", "required"=false, "description"="result page for history / [0-9]{0,5}"}
     *    }
     * )
     *
     * @CheckParam(name="lng", regex="/[a-zA-Z_]{5}/", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="authToken", regex="/.{1,40}/", length="40", errCode="ERR_AUTHTOKEN_1")
     * @CheckParam(name="page", regex="/[0-9]{0,5}/", length="5", errCode="ERR_PAGE_1")
     * @CheckParam(name="orderid", length="32", errCode="ERR_TX_1", required="false")
     *
     * @Post("/transaction/history")
     * @param $request
     * @return Response
     */
    public function postTransactionHistoryAction(Request $request)
    {

        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();

        // check token
        $this->checkAuthToken($request);

        $_oRpTx = $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction');
        $_aList = $_oRpTx->findPage($request->get('page'), 10, 'createdAt', true, array('action' => 'history', 'customer' => $this->getCustomer()->getId(), 'orderId' =>  $request->get('orderid')));

        if ($_aList['pagination']['total'] == 0) {
            $_aList['pagination']['total'] = 1;
        }

        $_aTxList = array();

        foreach ($_aList['data'] as $_oTx) {
            $_aTx = $this->buildTransactionStatusArray($_oTx, $request->get('lng'));
            $_aTxList[] = $_aTx;

        } // end foreach ($_aList['data'] as $_oTx)

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => array(
                "pages" => $_aList['pagination']['total'],
                "history" => $_aTxList,
            ),
        ));
        return $this->handleView($view);
    } // end of method postTransactionHistoryAction

    /**
     * @ApiDoc(
     *     description="Get order info to create a clone...",
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    },
     *    parameters={
     *      {"name"="lng", "dataType"="string", "required"=true, "description"="language / [a-zA-Z_]{5}"},
     *      {"name"="email", "dataType"="string", "required"="true", "description"="Customer's email / .{5,255}"},
     *      {"name"="orderid", "dataType"="string", "required"=true, "description"="Order Id / .{1,32}"}
     *    }
     * )
     *
     * @CheckParam(name="lng", regex="/[a-zA-Z_]{5}/", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="email", length="5-255", errCode="ERR_EMAIL_1")
     * @CheckParam(name="orderid", length="32", errCode="ERR_ORDER_ID_1")
     *
     * @Post("/transaction/callback")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postTransactionCallBackAction(Request $request)
    {

        // ----------------------------- load required objects
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();

        // We need init order status objects
        $rptxs = $this->getDoctrine()->getRepository('ApiBackendBundle:TransactionStatus');
        $_oStatusInit = $rptxs->findInit();
        if (!is_object($_oStatusInit)) {
            $logger->error("Transaction API - method postTransactionStatusAction - can not load order statuses");
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        } // end if

        // Todo: check that order exists and is mapped to customer...
        // load order from id
        $_oRpTx = $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction');
        $_oTx = $_oRpTx->findOneById($request->get('orderid'));

        // Case order not found
        if (is_null($_oTx) || !is_object($_oTx)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_ID_2'))), Response::HTTP_BAD_REQUEST);
        }

        // Case order does not belong to customer...
        if ($_oTx->getCustomer() == null || $_oTx->getCustomer()->getEmail() != $request->get('email')) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_ID_3'))), Response::HTTP_BAD_REQUEST);
        }

        // Case order does not belong to customer...
        if ($_oTx->getStatus() !== $_oStatusInit) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_ID_4'))), Response::HTTP_BAD_REQUEST);
        }

        // get transaction into JSON RESPONSE...
        $_aTx = $this->buildTransactionStatusArray($_oTx, $request->get('lng'));

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => $_aTx,
        ));

        return $this->handleView($view);
    } // end of method postTransactionStatusAction
    /**
     * @param $soldCountry
     * @param $lngFr
     * @param $service
     * @return array
     */
    private function transactionInitFillTmpTopUpResponseJSON($soldCountry, $lngFr, $service, array $_aProdRef=null)
    {
        $_oTmp = new \stdClass();
        $_strCountryLabel = TransactionExport::NON_DISPONIBLE;
        $_strLocalCurrency = TransactionExport::NON_DISPONIBLE;
        // get operator name
        $_strOperatorName = TransactionExport::NON_DISPONIBLE;
        $_prodOperator = null;

        if ($service == Transaction::SERVICE_AIRTIME) {
            // get country name and local currency
            if ($soldCountry->getCountry()->getCountryLabelByLang($lngFr)[0] != null) {
                $_strCountryLabel = $soldCountry->getCountry()->getCountryLabelByLang($lngFr)[0]->getWording();
                $_strLocalCurrency = $soldCountry->getCountry()->getCurrency();
            }
            if (isset($_aProdRef[2]) && !empty($_aProdRef[2])) {
                $rpop = $this->getDoctrine()->getRepository('ApiBackendBundle:Operator');
                $_oOperator = $rpop->findOneBy(array('supplierKey' => $_aProdRef[2]));
                if (!is_null($_oOperator) && is_object($_oOperator)) {
                    $_strOperatorName = $_oOperator->getWording();
                    $_prodOperator = $_oOperator;
                }
            }
        } // endif($service!=Transaction::SERVICE_TRAVELSIMCARD)
        elseif ($service == Transaction::SERVICE_TRAVELSIMCARD) {
            $_strCountryLabel = "Europe";
            $_strOperatorName = "Travel SIM Card";
            $_strLocalCurrency = "EUR";
        }
        else{
            $_strCountryLabel = "Europe";
            $_strOperatorName = "ESIM";
            $_strLocalCurrency = "EUR";
        }

        $_oTmp->country = $_strCountryLabel;
        $_oTmp->local_info_currency = $_strLocalCurrency;
        $_oTmp->operator = $_strOperatorName;

        // no local info amount at this stade
        $_oTmp->local_info_amount = TransactionExport::NON_DISPONIBLE;
        $_oTmp->local_info_value = TransactionExport::NON_DISPONIBLE;
        $_oTmp->product_requested = TransactionExport::NON_DISPONIBLE;
        return array($_oTmp, $_prodOperator);
    }

    /**
     * @param $service
     * @param $logger
     * @return array
     * @throws SendExceptionAsResponse
     */
    private function transactionInitCheckInputs(Request $request, $service, $logger)
    {
        // Prequel for airtime service
        if ($service == Transaction::SERVICE_AIRTIME) {
            // Check if phone number has been blacklisted for fraud reason !
            if ($request->get('prefix') && $request->get('phone')) {
                $_oBlackListNumber = $this->getDoctrine()->getRepository('ApiBackendBundle:BlacklistReceiver')->findOneBy(array('phoneCode' => $request->get('prefix'), 'phoneNumber' => $request->get('phone')));
                if (is_object($_oBlackListNumber)) {
                    throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_FRAUDMSISDN'))), Response::HTTP_BAD_REQUEST);
                }
            }
        }


        //0. We need suppliers objects
        $rps = $this->getDoctrine()->getRepository('ApiBackendBundle:Supplier');
        $_oT2 = $rps->findAirtime();
        $_oTrvlESIM = $rps->findOrangeLinkTrvlEsim();
        $_oOLTravelSIMCard = $rps->findOrangeLinkTravelSIMCard();
        $_oSim = null;
        $_aeSimOffer=null;

        $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
        $lng = $rpl->findOneBy(array("local" => $request->get('lng')));
        if (!is_object($lng)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        $lngFr = $rpl->findOneBy(array("local" => "en_EN"));
        if (!is_object($lngFr)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        if ($service == Transaction::SERVICE_TRAVELSIMCARD) {
            if (!$request->get('simId')) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_SIMID_1'))), Response::HTTP_BAD_REQUEST);
            }

            // Is Travelers service up ? (general parameter @ true)
            $parameterRepository = $this->getDoctrine()->getRepository('ApiBackendBundle:Parameter');
            $_bIsTravelersActivated = $parameterRepository->findTravelSIMCardOffer()->getValue();
            if ($_bIsTravelersActivated != "true") {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_SERVICE_2'))), Response::HTTP_BAD_REQUEST);
            }

            // Check if sim can be reloaded
            //SIM not provisionned ? -> ERR || SIM not found ? -> ERR || SIM not actif -> ERR
            $checkSimIdResponse = $this->getTravelSimCardService()->checkSimIdToBeReloaded($request->get('simId'));
            $httpCode = $checkSimIdResponse->code;
            if($httpCode===Response::HTTP_NOT_FOUND || $httpCode===Response::HTTP_INTERNAL_SERVER_ERROR){
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            }
            $checkSimIdResult = $checkSimIdResponse->result;
            if($checkSimIdResult->status==="OK") {
                $_oSim = $checkSimIdResult->detail;
            }else{
                throw new SendExceptionAsResponse(json_encode($checkSimIdResult->detail, JSON_THROW_ON_ERROR), Response::HTTP_BAD_REQUEST);
            }

            // check product reference
            $_aProdRef = explode('-', $request->get('productReference'));
            if (!is_array($_aProdRef) || count($_aProdRef) != 3) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PRODUCT_1'))), Response::HTTP_BAD_REQUEST);
            }

        }
        elseif ($service == Transaction::SERVICE_TRAVEL_ESIM) {

            $_aProdRef = null;
            // Contrôler la ref produit en retrouvant l'offre SIM liée
            $_aeSimOffer = $this->getSimOfferService()->getESimOffer($request->get('productReference'), $lng);
            if (!is_array($_aeSimOffer)) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PRODUCT_2'))), Response::HTTP_BAD_REQUEST);
            }

            //Controler si l'offre est digitale pour une transaction de type eSim
            if($_aeSimOffer['isDigital']==false){
                // Sécurité mais déjà géré en amont
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PRODUCT_4'))), Response::HTTP_BAD_REQUEST);
            }

            // Contrôler l'existence d'une référence mapping vers le système tiers Travel SIM Card (ESim)
            if(is_null($_aeSimOffer['eSimSupplierReference']) || empty($_aeSimOffer['eSimSupplierReference']))
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PRODUCT_4'))), Response::HTTP_BAD_REQUEST);
        }
        elseif ($service == Transaction::SERVICE_AIRTIME) {
            if (!$request->get('prefix')) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PREFTEL_1'))), Response::HTTP_BAD_REQUEST);
            }

            if (!$request->get('phone')) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_TEL_1'))), Response::HTTP_BAD_REQUEST);
            }

            if (is_null($_oT2) || !is_object($_oT2)) {
                $logger->error("Transaction API - method init - can not load suppliers objects");
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            }

            // check product reference
            $_aProdRef = explode('-', $request->get('productReference'));
            if (!is_array($_aProdRef) || count($_aProdRef) < 4 || count($_aProdRef) > 6) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PRODUCT_1'))), Response::HTTP_BAD_REQUEST);
            }

        }
        else {
            $logger->error("Transaction API - method init - unknown service");
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_SERVICE_1'))), Response::HTTP_BAD_REQUEST);
        }

        return array($_oT2, $_oOLTravelSIMCard, $_aProdRef, $lng, $lngFr, $_oSim, $_oTrvlESIM, $_aeSimOffer);

    }

    /**
     * @param $_prodOperator
     * @param $logger
     * @return array
     */
    private function transactionInitSetDiscounts(Request $request, $_prodOperator, $logger, Transaction $_oTx)
    {
        $_strPromoCode = null;
        $_strPromoStatus = null;
        $_strPromoDescription = null;
        // First, is promo code a real one and a working one...
        if (!empty($request->get('promoCode'))) {
            $_strPromoCode = $request->get('promoCode');
            $_oRpPromotion = $this->getDoctrine()->getRepository('ApiBackendBundle:Promotion');
            $_oPromotion = $_oRpPromotion->findValidPromotion($_strPromoCode, $_prodOperator);
            $logger->debug("Op promo : " . $_prodOperator->getId());
            if (!is_null($_oPromotion) && is_object($_oPromotion)) {
                $logger->debug("Code promo : " . $_oPromotion->getCode());
                // Second, calculate discount if needed and save discount datas
                $_strPromoStatus = 'VALIDATED';
                $_strPromoDescription = $_oPromotion->getDescription();
                $_oTx->setPromotionCode($_strPromoCode);
                $serializer = $this->container->get('serializer');
                $_strPromotionJson = $serializer->serialize($_oPromotion, 'json');
                $_oTx->setPromotionJSON($_strPromotionJson);
                $_oTx->setDiscount(round(($_oTx->getPrice() + $_oTx->getFee()) * $_oPromotion->getDiscount() / 100, 2));
            } else {
                $logger->debug("Pas de promo");
                $_strPromoStatus = 'REJECTED';
                $_strPromoDescription = '';
            } // end else if(!is_null($_oPromotion)&&is_object($_oPromotion))
        }
        return array($_strPromoCode, $_strPromoStatus, $_strPromoDescription); // end if(!empty($request->get('promoCode')))
    }

    /**
     * @param $_aProdRef
     * @param $supplier
     * @param $logger
     * @return array
     * @throws SendExceptionAsResponse
     */
    private function transactionInitFillCountry(Request $request, $_aProdRef, $supplier, $logger, Transaction $_oTx)
    {
        // retrieve Sold Country from Db and store country literally
        $rpsc = $this->getDoctrine()->getRepository('ApiBackendBundle:SoldCountry');
        $soldCountry = $rpsc->findOneBy(array('supplierKey' => $_aProdRef[1], 'supplier' => $supplier, 'status' => 1));

        if (is_null($soldCountry) || !is_object($soldCountry)) {
            $logger->error("Transaction API - method init - can not retrieve sold country from product reference " . $request->get('productReference'));
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PRODUCT_2'))), Response::HTTP_BAD_REQUEST);
        } // end if(is_null($soldCountry)||!is_object($soldCountry))

        if (!is_null($soldCountry) && is_object($soldCountry)) {
            $_aCountry = array(
                'id' => $soldCountry->getId(),
                'phonecode' => $soldCountry->getCountry()->getPhoneCode(),
                'isOrange' => $soldCountry->getCountry()->getIsOrange(),
                'isRisk' => $soldCountry->getCountry()->getIsRisk(),
                'iso' => $soldCountry->getCountry()->getISOCode(),
            );
            $_oTx->setReceiverCountry(json_encode($_aCountry, JSON_THROW_ON_ERROR));
        } else {
            $_oTx->setReceiverCountry(null);
        }

        return $soldCountry;
    }

    /**
     * @param $_aProdRef
     * @param $_oT2
     * @return mixed
     * @throws SendExceptionAsResponse
     */
    private function transactionInitAirtimeLogic(Request $request, Transaction $_oTx, $_aProdRef, $_oT2)
    {
        $_oTx->setReceiverPhoneCode($request->get('prefix'));
        $_oTx->setReceiverPhoneNumber($request->get('phone'));

        // If T2 check format, get product values
        // T2 product reference composition : 0=supplier;1=country;2=operator;3=price;4=fee
        if ($_aProdRef[0] == $_oT2->getCode()) {
            $supplier = $_oT2;

            $_oTx->setFee($_aProdRef[4] / 100);
            $_oTx->setPrice($_aProdRef[3] / 100);
            $_oTx->setProductReference($request->get('productReference'));
            $_oTx->setProductSupplier($_oT2);
        } // end if($_aProdRef[0]==$_oT2->getCode())

        else {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PRODUCT_2'))), Response::HTTP_BAD_REQUEST);
        }

        return $supplier;
    }

    /**
     * @param $_aProdRef
     * @param Supplier $_oOLTravelers
     * @param TrvlSim $_oSim
     * @return mixed
     * @throws SendExceptionAsResponse
     */
    private function transactionInitTravelersLogic(Request $request, Transaction $_oTx, $_aProdRef, Supplier $_oOLTravelers, $_oSim)
    {

        $_oTx->setSimId($_oSim->id);
        $_oTx->setProductSupplier($_oOLTravelers);

        $_oReload = null;
        // Load reload, check that status is still ok...
        if (!empty($_aProdRef[1]) && is_numeric($_aProdRef[1])) {
            // Load Reload from Travel SIM Card application
            $getReloadResponse = $this->getTravelSimCardService()->getSIMReloadFromReference($_aProdRef[1]);
            $httpCode = $getReloadResponse->code;
            if($httpCode===Response::HTTP_NOT_FOUND || $httpCode===Response::HTTP_INTERNAL_SERVER_ERROR){
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            }
            $getReload = $getReloadResponse->result;
            if($getReload->status==="OK") {
                $_oReload = $getReload->detail;
            }
        }

        if (is_object($_oReload)) {
            $_jsonProduct =  json_encode($_oReload, JSON_THROW_ON_ERROR);
            $_oTx->setFee(0);
            $_oTx->setPrice($_oReload->selling_price);
            $_oTx->setProductReference($request->get('productReference'));
            $_oTx->setProductJSON($_jsonProduct);
        } else {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PRODUCT_2'))), Response::HTTP_BAD_REQUEST);
        }

        return true;
    } // end     private function transactionInitTravelersLogic(Request $request, Transaction $_oTx, $_aProdRef, Supplier $_oOLTravelers, $_oSim)
    /**
     * Cette méthode initialise la partie ESIM d'une transaction
     * On calcule le prix et on sérialise l'objet offre SIM choisie pour l'achat
     * @param Supplier $_oSupplier
     * @param $_oeSimOffer
     * @return true
     * @throws SendExceptionAsResponse
     */
    private function transactionInitESIMLogic(Request $request, Transaction $_oTx, Supplier $_oSupplier, $_aeSimOffer){

        $logger = $this->get('logger');

        try{

            // On associe le fournisseur
            $_oTx->setProductSupplier($_oSupplier);

            // On calcule le prix de la commande
            // et on sérialise l'offre pour trace
            if (is_array($_aeSimOffer)) {

                // gestion du media
                if($_aeSimOffer['isDigital']==true && $_aeSimOffer['isPhysical']==true)
                    $_aeSimOffer['media'] = 'all';
                elseif($_aeSimOffer['isDigital']==true)
                    $_aeSimOffer['media'] = 'digital';
                elseif($_aeSimOffer['isPhysical']==true)
                    $_aeSimOffer['media'] = 'physical';
                unset($_aeSimOffer['isPhysical']);
                unset($_aeSimOffer['isDigital']);

                $_jsonProduct = json_encode($_aeSimOffer, JSON_THROW_ON_ERROR);
                $_oTx->setFee(0);
                $_oTx->setPrice($_aeSimOffer['price']);
                $_oTx->setProductReference($_aeSimOffer['reference']);
                $_oTx->setProductJSON($_jsonProduct);
            } // end if (is_object($_oeSimOffer))
            else {
                // Sécurité mais déjà géré en amont
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PRODUCT_2'))), Response::HTTP_BAD_REQUEST);
            } // end else

        } //end try
        catch (\Exception $_oE){
            $logger->error("Transaction API - method transactionInitESIMLogic - can not init ESIM logic : " .$_oE->getMessage());
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PRODUCT_2'))), Response::HTTP_BAD_REQUEST);
        }

        return true;
    } // end private function transactionInitESIMLogic(Request $request, Transaction $_oTx, Supplier $_oSupplier, $_oeSimOffer)

} // end of class
