<?php

namespace ApiFrontBundle\Controller;

use ApiBackendBundle\Entity\ProductVisuel;
use ApiBackendBundle\Entity\SoldCountry;
use ApiBackendBundle\Entity\Supplier;
use ApiBackendBundle\Entity\Transaction;
use ApiBackendBundle\Exception\SendExceptionAsResponse;
use ApiBackendBundle\Repository\ProductVisuelRepository;
use ApiBackendBundle\Service\TransferToService;
use ApiBackendBundle\Service\TravelSimCardService;
use ApiFrontBundle\Annotation\CheckParam;
use FOS\RestBundle\Controller\Annotations\Post;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CatalogController extends ControllerBase
{

    /** @var TravelSimCardService */
    protected $travelSimCardService;

    protected function getTravelSimCardService(): object|\TravelSimCardService
    {
        if ($this->travelSimCardService !== null) {
            return $this->travelSimCardService;
        }

        return $this->get('api_backend.travel_sim_card_service');
    }

    /** @var TransferToService */
    protected $transferToService;

    public function getTransferToService(): object|\TransferToService
    {
        if($this->transferToService != null){
            return $this->transferToService;
        }

        return $this->get('api_backend.transfer_to_service');
    }


    /**
     * @ApiDoc(
     *    description="Get the list of the countries open for credit",
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    },
     *    parameters={
     *      {"name"="lng", "dataType"="string", "required"=true, "description"="language / [a-zA-Z_]{5}"}
     *    }
     * )
     *
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     *
     * @Post("/catalog/countries")
     * @return Response
     */
    public function postCatalogCountriesAction(Request $request)
    {

        $rp = $this->getDoctrine()->getRepository('ApiBackendBundle:SoldCountry');
        $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
        $lng = $rpl->findOneBy(array("local" => $request->get('lng')));
        if (!is_object($lng)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        $soldCountries = $rp->findAirtimeCountries();
        if (is_null($soldCountries) || !is_array($soldCountries) || count($soldCountries) == 0) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_EMPTY'))), Response::HTTP_BAD_REQUEST);
        }

        $details = array();
        foreach ($soldCountries as $soldCountry) {
            $c = $soldCountry->getCountry();
            $cPhoneCode = $c->getPhoneCode();
            if ($c->getCountryLabelByLang($lng)[0] != null) {
                $lbl = $c->getCountryLabelByLang($lng)[0]->getWording();
            } else {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            }

            $details[] = array(
                'id' => $soldCountry->getId(),
                'name' => $lbl,
                'phonecode' => $cPhoneCode,
                'iso' => $soldCountry->getCountry()->getISOCode(),
                'isOrange' => $soldCountry->getCountry()->getIsOrange(),
            );
        }

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => $details,
        ));
        return $this->handleView($view);
    } // end of method postCatalogCountriesAction
    /**
     * @ApiDoc(
     *    description="Get the list of the products and operators opened by country",
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    },
     *    parameters={
     *      {"name"="lng", "dataType"="string", "required"=true, "description"="language / [a-zA-Z_]{5}"},
     *      {"name"="country", "dataType"="string", "required"=true, "description"="country Iso Code / [A-Z]{2}"},
     *    }
     * )
     *
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="country", regex="/^[A-Z]*$/", length="2-2", errCode="ERR_COUNTRY_1")
     *
     * @Post("/catalog/productsbycountry")
     * @return Response
     */
    public function postCatalogProductsByCountryAction(Request $request)
    {

        // get lang
        $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
        $lng = $rpl->findOneBy(array("local" => $request->get('lng')));
        if (!is_object($lng)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        // get T2 Supplier, the actual catalog product is only the T2 one for top up products
        $rps = $this->getDoctrine()->getRepository('ApiBackendBundle:Supplier');
        $_oT2 = $rps->findAirtime();

        if (is_null($_oT2) || !is_object($_oT2)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        // get country, check if sold country is available and sold for it
        $rpc = $this->getDoctrine()->getRepository('ApiBackendBundle:Country');
        $_oCountry = $rpc->findOneBy(array("iSOCode" => $request->get('country')));
        if (!is_object($_oCountry)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_COUNTRY_2'))), Response::HTTP_BAD_REQUEST);
        }

        $rpsc = $this->getDoctrine()->getRepository('ApiBackendBundle:SoldCountry');
        /** @var SoldCountry $_oSoldCountry */
        $_oSoldCountry = $rpsc->findOneAirTimeSoldCountry($request->get('country'));
        if (!is_object($_oSoldCountry)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_COUNTRY_3'))), Response::HTTP_BAD_REQUEST);
        }

        // try to get country label
        if ($_oCountry->getCountryLabelByLang($lng)[0] != null) {
            $_sCountryLabel = $_oCountry->getCountryLabelByLang($lng)[0]->getWording();
        } else {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        $_aOperatorsForResponse = $this->getTransferToService()->getProductsAndOperatorsBySoldCountry($_oSoldCountry,$_oCountry,$lng);

        $_aForResponse =
        array(
            'status' => 'OK',
            'detail' => array(
                "country" => $_sCountryLabel,
                "countryId" => $_oSoldCountry->getSupplierKey(),
                "operators" => $_aOperatorsForResponse,
            ),
        );

        $view = $this->view($_aForResponse);
        return $this->handleView($view);

    } // end function postCatalogProductsByCountryAction(Request $request)
    /**
     * @ApiDoc(
     *     description="Get the list of the products available for a msisdn",
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    },
     *    parameters={
     *      {"name"="lng", "dataType"="string", "required"=true, "description"="language / [a-zA-Z_]{5}"},
     *      {"name"="prefix", "dataType"="string", "required"=true, "description"="phone region code / [0-9]{1,5}"},
     *      {"name"="phone", "dataType"="string", "required"=true, "description"="phone number / [0-9]{6,15}"},
     *      {"name"="authToken", "dataType"="string", "required"=false, "description"="authentication token / .{0,40}"},
     *      {"name"="service", "dataType"="string", "required"=true, "description"="service / (travelsimcard)|(airtime)"},
     *      {"name"="simId", "dataType"="string", "required"=false, "description"="SIM ID / [0-9]{10,13}"}
     *    }
     * )
     *
     * @CheckParam(name="lng", regex="/[a-zA-Z_]{5}/", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="prefix", regex="/[0-9]{1,5}/", errCode="ERR_PREFTEL_1", required="false")
     * @CheckParam(name="phone", regex="/[0-9]{6,15}/", errCode="ERR_TEL_1", required="false")
     * @CheckParam(name="authToken", regex="/.{0,40}/", errCode="ERR_AUTHTOKEN_1", required="false")
     * @CheckParam(name="simId", regex="/[0-9]{13}/", errCode="ERR_SIMID_1", required="false")
     * @CheckParam(name="service", regex="/(travelsimcard)|(airtime)/", errCode="ERR_SERVICE_1")
     *
     * @Post("/catalog/products")
     * @return Response
     */
    public function postCatalogProductsAction(Request $request)
    {

        // check token and order ability
        $this->checkAuthToken($request);

        // check order ability only if token is given
        if (is_object($this->getCustomer())) {
            $this->checkOrderAbility();
        }

        //0. We need Supplier objects
        $rps = $this->getDoctrine()->getRepository('ApiBackendBundle:Supplier');
        /** @var Supplier $_oT2 */
        $_oT2 = $rps->findAirtime();
        /** @var Supplier $_oOLTravelSIMCard */
        $_oOLTravelSIMCard = $rps->findOrangeLinkTravelSIMCard();

        //0 again. Check lang parameter
        $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
        $lng = $rpl->findOneBy(array("local" => $request->get('lng')));
        if (!is_object($lng)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        // Travel SIM Card entry point...
        // If service is travelers, we check travel sim card sim Id
        if ($request->get('service') == Transaction::SERVICE_TRAVELSIMCARD) {

            if (is_null($_oOLTravelSIMCard) || !is_object($_oOLTravelSIMCard)) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            }

            // is SIM ID given
            if (empty($request->get('simId'))) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_SIMID_1'))), Response::HTTP_BAD_REQUEST);
            }

            // Is Travelers service up ? (general parameter @ true)
            $parameterRepository = $this->getDoctrine()->getRepository('ApiBackendBundle:Parameter');
            $_bIsTravelersActivated = $parameterRepository->findTravelSIMCardOffer()->getValue();
            if ($_bIsTravelersActivated != "true") {
                throw new SendExceptionAsResponse(json_encode(array(array('errorCode' => 'ERR_SERVICE_2'))), Response::HTTP_BAD_REQUEST);
            }

            // Get most popular product for that supplier...
            $rptx = $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction');
            $_StrMostPopular = $rptx->getMostPopularByOperator($_oOLTravelSIMCard->getCode() . "-");

            $simReloadResponse = $this->getTravelSimCardService()->getSimReloads($request->get('simId'),$request->get('lng'), $_StrMostPopular, $this->getDayAmountAvailable(), $this->getMonthAmountAvailable());
            $httpCode = $simReloadResponse->code;
            if($httpCode === Response::HTTP_NOT_FOUND || $httpCode === Response::HTTP_INTERNAL_SERVER_ERROR){
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            }

            $_aForResponse = $simReloadResponse->result;
            return (new JsonResponse())->create($_aForResponse, Response::HTTP_OK);

        }
        // Airtime standard case
        else {

            // are phone code and phone number given and not empty ?
            if (empty($request->get('prefix'))) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PREFTEL_1'))), Response::HTTP_BAD_REQUEST);
            }

            if (empty($request->get('phone'))) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_TEL_1'))), Response::HTTP_BAD_REQUEST);
            }

            if (is_null($_oT2) || !is_object($_oT2)) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            }

            // Prequel :
            // Check if phone number has been blacklisted for fraud reason !
            $_oBlackListNumber = $this->getDoctrine()->getRepository('ApiBackendBundle:BlacklistReceiver')->findOneBy(array('phoneCode' => $request->get('prefix'), 'phoneNumber' => $request->get('phone')));
            if (is_object($_oBlackListNumber)) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_FRAUDMSISDN'))), Response::HTTP_BAD_REQUEST);
            }

            //1. Get Country from phone code
            $rp = $this->getDoctrine()->getRepository('ApiBackendBundle:Country');
            $countries = $rp->findBy(array('phoneCode' => $request->get('prefix')));
            if (is_null($countries) || !is_array($countries) || count($countries) == 0) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PREFTEL_2'))), Response::HTTP_BAD_REQUEST);
            }

            //2. Get Sold countries just to check that we can sell
            $rpsc = $this->getDoctrine()->getRepository('ApiBackendBundle:SoldCountry');
            $soldCountriesByT2 = $rpsc->findAirtimeCountries();

            // request and send T2
            if (!is_null($soldCountriesByT2) && is_array($soldCountriesByT2) && count($soldCountriesByT2) > 0) {
                $_aForResponse = $this->getT2CatalogByMSISDN($request->get('prefix') . $request->get('phone'), $lng, $_oT2);
            } else {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PREFTEL_3'))), Response::HTTP_BAD_REQUEST);
            }

        }

        $view = $this->view($_aForResponse);
        return $this->handleView($view);
    } // end of method postCatalogProducts
    /**
     * @ApiDoc(
     *    description="Get the list of current and future promotions",
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    }
     * )
     *
     * @Post("/catalog/promotionsOrange")
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCatalogPromotionsOrangeAction(Request $request)
    {

        $rp = $this->getDoctrine()->getRepository('ApiBackendBundle:PromotionOrange');

        $promotions = $rp->findCurrentAndFuture();

        if (is_null($promotions) || !is_array($promotions) || count($promotions) == 0) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_EMPTY'))), Response::HTTP_BAD_REQUEST);
        }

        $details = array();
        foreach ($promotions as $promotion) {
            $details[] = array(
                'id' => $promotion->getId(),
                'product' => $promotion->getProduct()->getId(),
                'description' => $promotion->getDescription(),
                'startDate' => $promotion->getStartDate()->format(DATE_ATOM),
                'endDate' => $promotion->getEndDate()->format(DATE_ATOM),
                'amount' => $promotion->getAmount(),
            );
        }

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => $details,
        ));
        return $this->handleView($view);
    }


    private function getT2CatalogByMSISDN($_sMsisdn, $lng, Supplier $_oT2)
    {
        $_oT2Service = $this->get('api_backend.transfer_to_service');
        $_sT2Response = $_oT2Service->getCatalogByMSISDN($_sMsisdn);

        $em = $this->getDoctrine()->getManager();
        $TxSystem = $this->getDoctrine()->getRepository('ApiBackendBundle:System')->findOneByCode($_oT2Service->getSystemCode());
        $TxSystem->setStatus($_oT2Service->getStatus());
        $em->persist($TxSystem);
        $em->flush();

        if (is_null($_sT2Response)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        $logger = $this->get('logger');
        $logger->info("Result from T2 msisdn request : " . $_sT2Response);

        $_aT2Response = json_decode($_sT2Response, null, 512, JSON_THROW_ON_ERROR);

        if (isset($_aT2Response->error_code) && ($_aT2Response->error_code == "101" || $_aT2Response->error_code == "104" || $_aT2Response->error_code == "216" || $_aT2Response->error_code == "217" || $_aT2Response->error_code == "224")) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_BADMSISDN'))), Response::HTTP_BAD_REQUEST);
        } elseif (isset($_aT2Response->error_code)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        // try to get countrysold from TransferTo countryid
        $rpsc = $this->getDoctrine()->getRepository('ApiBackendBundle:SoldCountry');
        /** @var SoldCountry $soldCountry */
        $soldCountry = $rpsc->findOneAirTimeSoldCountryById($_aT2Response->countryid);
        if (is_null($soldCountry)) {
            $logger->error('Error getting Transfer To MSISDN request - countryid not recognize in sold country base : ' . $_aT2Response->countryid);
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        // try to get country label
        $_oCountry = $soldCountry->getCountry();
        if ($_oCountry->getCountryLabelByLang($lng)[0] != null) {
            $_sCountryLabel = $_oCountry->getCountryLabelByLang($lng)[0]->getWording();
        } else {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        // Do we have operators brothers Bundles or Internet ?
        // operator id = $_aT2Response->operatorid
        // First get the name, second, look for the brother operators...
        $_oRpOp = $this->getDoctrine()->getRepository('ApiBackendBundle:Operator');
        $_aChildrenOperatorKeys = $_oRpOp->findByPseudoChilds($_aT2Response->operatorid);

        // Look for the most popular product
        $_aChildrenOperatorKeysForMostPopular = array($_oT2->getCode() . '-' . $soldCountry->getSupplierKey() . '-' . $_aT2Response->operatorid);
        $_StrMostPopular = '';
        $rptx = $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction');
        if (is_array($_aChildrenOperatorKeys) && count($_aChildrenOperatorKeys) > 0) {
            foreach ($_aChildrenOperatorKeys as $_strItem) {
                $_aChildrenOperatorKeysForMostPopular[] = $_oT2->getCode() . '-' . $soldCountry->getSupplierKey() . '-' . $_strItem;
            }
            $_StrMostPopular = $rptx->getMostPopularByOperator($_aChildrenOperatorKeysForMostPopular);
        } else {
            $_StrMostPopular = $rptx->getMostPopularByOperator($_oT2->getCode() . '-' . $soldCountry->getSupplierKey() . '-' . $_aT2Response->operatorid);
        }

        $logger->debug('Most popular product : ' . $_StrMostPopular);

        $rppromo = $this->getDoctrine()->getRepository('ApiBackendBundle:PromotionOrange');

        // build products array for main operator
        $_aProductsForResponse = $this->buildT2ProductList($lng, $_oT2, $_aT2Response, $soldCountry, $_StrMostPopular, $rppromo);

        // build products array for optional children operators...
        // if we have children operators, we need to call Transfer To as many times as we have operators...
        if (is_array($_aChildrenOperatorKeys) && count($_aChildrenOperatorKeys) > 0) {
            foreach ($_aChildrenOperatorKeys as $_strOperatorChildKey) {
                $_sT2ResponseTmp = $_oT2Service->getCatalogByMSISDN($_sMsisdn, $_strOperatorChildKey);
                if (!is_null($_sT2ResponseTmp)) {
                    $_aT2ResponseTmp = json_decode($_sT2ResponseTmp, null, 512, JSON_THROW_ON_ERROR);
                    $_aProductsForResponseTmp = $this->buildT2ProductList($lng, $_oT2, $_aT2ResponseTmp, $soldCountry, $_StrMostPopular, $rppromo);
                    $_aProductsForResponse = array_merge($_aProductsForResponse, $_aProductsForResponseTmp);
                } //end if(!is_null($_sT2ResponseTmp))
            } // end foreach ($_aChildrenOperatorKeys as $_strOperatorChildKey)
        }

        // build operator array
        $_aOperatorsForResponse = array();
        $_aOperatorsForResponse[] = array(
            "operator" => $_aT2Response->operator,
            "operatorId" => $_oT2->getCode() . '-' . $soldCountry->getSupplierKey() . '-' . $_aT2Response->operatorid,
            "operatorLogo" => 'https://operator-logo.transferto.com/logo-' . $_aT2Response->operatorid . '-1.png',
            "localCurrency" => $_aT2Response->local_info_currency,
            "products" => $_aProductsForResponse,
        );

        $_aForResponse =
        array(
            'status' => 'OK',
            'detail' => array(
                "country" => $_sCountryLabel,
                "countryId" => $soldCountry->getSupplierKey(),
                "operators" => $_aOperatorsForResponse,
            ),
        );

        return $_aForResponse;

    } // end private function getT2CatalogByMSISDN($_sMsisdn)

    private function getProductType($_strProductLabelId, $_oLng)
    {
        $rppl = $this->getDoctrine()->getRepository('ApiBackendBundle:ProductLabel');
        $_oProductLabel = $rppl->findOneBy(array('id' => $_strProductLabelId, 'lang' => $_oLng));
        if (!is_null($_oProductLabel)) {
            return $_oProductLabel->getType();
        } else {
            return "";
        }

    } // end private function getProductType($_strProductLabelId, $_oLng)

    private function getProductData($_strProductLabelId, $_oLng)
    {
        $rppl = $this->getDoctrine()->getRepository('ApiBackendBundle:ProductLabel');
        $_oProductLabel = $rppl->findOneBy(array('id' => $_strProductLabelId, 'lang' => $_oLng));
        if (!is_null($_oProductLabel)) {
            return $_oProductLabel->getData();
        } else {
            return "";
        }

    } // end private function getProductData($_strProductLabelId, $_oLng)

    private function getProductVoice($_strProductLabelId, $_oLng)
    {
        $rppl = $this->getDoctrine()->getRepository('ApiBackendBundle:ProductLabel');
        $_oProductLabel = $rppl->findOneBy(array('id' => $_strProductLabelId, 'lang' => $_oLng));
        if (!is_null($_oProductLabel)) {
            return $_oProductLabel->getVoice();
        } else {
            return "";
        }

    } // end private function getProductVoice($_strProductLabelId, $_oLng)

    private function getProductSMS($_strProductLabelId, $_oLng)
    {
        $rppl = $this->getDoctrine()->getRepository('ApiBackendBundle:ProductLabel');
        $_oProductLabel = $rppl->findOneBy(array('id' => $_strProductLabelId, 'lang' => $_oLng));
        if (!is_null($_oProductLabel)) {
            return $_oProductLabel->getSms();
        } else {
            return "";
        }

    } // end private function getProductSMS($_strProductLabelId, $_oLng)

    private function getProductValidity($_strProductLabelId, $_oLng)
    {
        $rppl = $this->getDoctrine()->getRepository('ApiBackendBundle:ProductLabel');
        $_oProductLabel = $rppl->findOneBy(array('id' => $_strProductLabelId, 'lang' => $_oLng));
        if (!is_null($_oProductLabel)) {
            return $_oProductLabel->getValidity();
        } else {
            return "";
        }

    } // end private function getProductValidity($_strProductLabelId, $_oLng)

    private function getProductDescriptionV2($_strProductLabelId, $_oLng)
    {
        $rppl = $this->getDoctrine()->getRepository('ApiBackendBundle:ProductLabel');
        $_oProductLabel = $rppl->findOneBy(array('id' => $_strProductLabelId, 'lang' => $_oLng));
        if (!is_null($_oProductLabel)) {
            return $_oProductLabel->getWording();
        } else {
            return "";
        }

    } // end private function getProductDescriptionV2($_strProductLabelId, $_oLng)
    /**
     * @param $lng
     * @param $_oT2
     * @param $_aT2Response
     * @param $soldCountry
     * @param $_StrMostPopular
     * @param array $_aProductsForResponse
     * @return array
     */
    private function buildT2ProductList($lng, $_oT2, $_aT2Response, $soldCountry, $_StrMostPopular, \Doctrine\Common\Persistence\ObjectRepository $rppromo)
    {

        $visuel = [];
        $langPromo = null;
        // First check operator is activated to sell products
        $_oRpOp = $this->getDoctrine()->getRepository('ApiBackendBundle:Operator');
        /** @var ProductVisuelRepository $productVisuelRepository */
        $productVisuelRepository = $this->getDoctrine()->getRepository(ProductVisuel::class);
        $_oOperator = $_oRpOp->findOneAirtimeOperatorsById($_aT2Response->operatorid);

        $_aProductsForResponse = array();
        if(is_object($_oOperator)){
            for ($i = 0; $i < (is_countable($_aT2Response->product_list) ? count($_aT2Response->product_list) : 0); $i++) {
                // if customer orders this product, will he reach the limits by amount ?
                $_bCanTopUp = true;
                if (is_object($this->getCustomer())) {
                    if ($this->getDayAmountAvailable() < ($_aT2Response->retail_price_list[$i] + $_aT2Response->service_fee_list[$i])) {
                        $_bCanTopUp = false;
                    }

                    if ($this->getMonthAmountAvailable() < ($_aT2Response->retail_price_list[$i] + $_aT2Response->service_fee_list[$i])) {
                        $_bCanTopUp = false;
                    }

                }

                $_strProductRef = $_oT2->getCode() . '-' . $soldCountry->getSupplierKey() . '-' . $_aT2Response->operatorid . '-' . ((float) $_aT2Response->retail_price_list[$i] * 100) . "-" . ((float) $_aT2Response->service_fee_list[$i] * 100) . "-" . ($_aT2Response->product_list[$i]);

                // Check if current product could be the most popular for the operator
                $_isMostPopular = false;
                if (isset($_StrMostPopular) && !empty($_StrMostPopular) && $_StrMostPopular == $_strProductRef) {
                    $_isMostPopular = true;
                }

                $promo = $rppromo->findProductCurrentPromo($_strProductRef);
                if ($promo != null) {
                    $langPromo = $promo->getPromoDescription($lng);
                }

                $_strRefForDescription = $_oT2->getCode() . '-' . $soldCountry->getSupplierKey() . '-' . $_aT2Response->operatorid . "-" . ($_aT2Response->product_list[$i]);

                // ajout du visuel au produit
                /** @var ProductVisuel $_oProductVisuel */
                $productId= $_oT2->getCode().'-'.$soldCountry->getSupplierKey().'-'.$_aT2Response->operatorid.'-'.($_aT2Response->product_list[$i]);
                $_oProductVisuel = $productVisuelRepository->findOneBy(['product'=>$productId]);
                //ajout du visuel
                $visuel['binaryPic']=null;
                $visuel['mimetypePic']=null;
                if(isset($_oProductVisuel)){
                    $visuelConent = stream_get_contents($_oProductVisuel->getVisuel());
                    $visuelBase64 = explode(',',$visuelConent)[1];
                    $visuel['binaryPic']=$visuelBase64;
                    $dataMineType = explode(';',$visuelConent)[0];
                    $visuel['mimetypePic']=explode(':', $dataMineType)[1];
                }
                $_aProductsForResponse[] = array(
                    "productId" => $_strProductRef,
                    "productValue" => $_aT2Response->product_list[$i],
                    "productPrice" => $_aT2Response->retail_price_list[$i],
                    "taxAndFee" => $_aT2Response->service_fee_list[$i],
                    "localAmount" => $promo ? $promo->getAmount() : $_aT2Response->local_amount_list[$i],
                    "customerCanTopUp" => $_bCanTopUp,
                    //"productDescription" => $this->getProductDescription($_aT2Response->operator, $_aT2Response->product_list[$i], $lng)
                    "productDescription" => $this->getProductDescriptionV2($_strRefForDescription, $lng),
                    "productType" => $this->getProductType($_strRefForDescription, $lng),
                    "productData" => $this->getProductData($_strRefForDescription, $lng),
                    "productVoice" => $this->getProductVoice($_strRefForDescription, $lng),
                    "productSMS" => $this->getProductSMS($_strRefForDescription, $lng),
                    "productValidity" => $this->getProductValidity($_strRefForDescription, $lng),
                    "isMostPopular" => $_isMostPopular,
                    "promoOrangeDesc" => $langPromo ? $langPromo->getWording() : null,
                    "binaryPic" => $visuel['binaryPic'],
                    "mimetypePic" => $visuel['mimetypePic']
                );
            } // end for
        } // end if(is_object($_oOperator)){

        return $_aProductsForResponse; // end foreach products
    }

    private function startswith($haystack, $needle)
    {
        return $haystack[0] === $needle[0]
        ? str_starts_with($haystack, $needle)
        : false;
    } // end private function startswith($haystack, $needle)


    /**
     * Use to get label for volume parameters in travel sim card catalog
     * @param String $_lng
     * @return string
     */
    private function getLabelForVolume($_lng, $_unit)
    {
        if ($_unit == 'G') {
            return match ($_lng) {
                "fr_FR" => "GO",
                default => "GB",
            };
        } else {
            return match ($_lng) {
                "fr_FR" => "MO",
                default => "MB",
            };
        }
    } // end private function getLabelForDuration(String $_lng)

} // end of class
