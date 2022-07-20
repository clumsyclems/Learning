<?php

namespace ApiFrontBundle\Controller;

use ApiBackendBundle\Entity\SimOffer;
use ApiBackendBundle\Entity\SimOfferType;
use ApiBackendBundle\Entity\Supplier;
use ApiBackendBundle\Entity\User;
use ApiBackendBundle\Exception\SendExceptionAsResponse;
use ApiBackendBundle\Service\SimOfferService;
use ApiBackendBundle\Service\SimProfileService;
use ApiBackendBundle\Service\TransferToService;
use ApiBackendBundle\Service\TravelSimCardService;
use ApiFrontBundle\Annotation\CheckParam;
use ApiFrontBundle\Entity\Lang;
use FOS\RestBundle\Controller\Annotations\Post;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SimProfileController extends ControllerBase
{

    protected function getTravelSimCardService(): object|\TravelSimCardService
    {
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
     * @Post("/simProfiles/countries")
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postSimProfileCountriesAction(Request $request)
    {

        // Chargement de la langue
        $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
        /** @var Lang $lng */
        $lng = $rpl->findOneBy(array("local" => $request->get('lng')));
        if (!is_object($lng)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        try {
            /** @var SimProfileService $_oSimProfileService */
            $_oSimProfileService = $this->get('api_backend.sim_profile_service');

            $_aCatalog = $_oSimProfileService->getAvailableGeographicZones($lng, $this->getUser()->getRoles()[0]);

            $view = $this->view(array('status' => 'OK', 'detail' => $_aCatalog), Response::HTTP_OK);

            return $this->handleView($view);
        }// end try
        catch (\Exception $_oE){
            $this->get('logger')->error("class SimProfileController - méthode postSimProfileCountriesAction - " . $_oE->getMessage());
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        } // end catch

    } // end public function postSimProfileCountriesAction(Request $request)
    /**
     * @ApiDoc(
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
     * @Post("/simProfiles/search-criteria")
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postSimProfileSearchCriteriaAction(Request $request)
    {
        // Chargement de la langue
        $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
        $lng = $rpl->findOneBy(array("local" => $request->get('lng')));
        if (!is_object($lng)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        try {
            $_oSimProfileService = $this->get('api_backend.sim_profile_service');

            $_aValidityValues = $_oSimProfileService->getValidityList($lng);
            $_aDataValues = $_oSimProfileService->getDataBucketList();

            $_aData = array('code'=> 'data', 'name' => 'Data amount', 'values' => $this->translateDataBucketList($_aDataValues));
            $_aValidity = array('code'=> 'validity', 'name' => 'Validity period', 'values' => $_aValidityValues);

            $_aResult = array($_aData, $_aValidity);

            $view = $this->view(array('status' => 'OK', 'detail' => $_aResult), Response::HTTP_OK);

            return $this->handleView($view);
        }// end try
        catch (\Exception $_oE){
            $this->get('logger')->error("class SimProfileController - méthode postSimProfileCountriesAction - " . $_oE->getMessage());
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        } // end catch

    } // end public function postSimProfileSearchCriteriaAction(Request $request)


    private function translateDataBucketList($_aList){
        $_aResult = array();
        foreach ($_aList as $item){
            $_aResult[] = array('code' => $item['grossDataVolume'], 'name' => $item['grossDataVolume'] . ' GB');
        }
        return $_aResult;
    } // end private function translateDataBucketList($_aList)
    /**
     * @ApiDoc(
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    },
     *    parameters={
     *      {"name"="lng", "dataType"="string", "required"=true, "description"="language / [a-zA-Z_]{5}"},
     *      {"name"="geozone", "dataType"="string", "required"=false, "description"="zone géographique / [A-Z]{5}"},
     *      {"name"="type", "dataType"="string", "required"=false, "description"="type d'offre / [a-z]{4}"},
     *      {"name"="media", "dataType"="string", "required"=false, "description"="physique ou digitale / [a-z]{10}"},
     *      {"name"="data", "dataType"="string", "required"=false, "description"="volume en data / .{0,5}"},
     *      {"name"="validity", "dataType"="string", "required"=false, "description"="durée de validité / .{0,5}"},
     *      {"name"="reference", "dataType"="string", "required"=false, "description"="référence de l'offre / .{0,16}"}
     *    }
     * )
     *
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="geozone", regex="/^[A-Z_]*$/", length="5", errCode="ERR_GEOZONE_1")
     * @CheckParam(name="type", regex="/^[a-z]*$/", length="4", errCode="ERR_TYPE_1", required="false")
     * @CheckParam(name="type", regex="/(all)|(data)/", errCode="ERR_TYPE_2", required="false")
     * @CheckParam(name="media", regex="/^[a-z]*$/", length="10", errCode="ERR_MEDIA_1", required="false")
     * @CheckParam(name="media", regex="/(all)|(digital)|(physical)/", errCode="ERR_MEDIA_2", required="false")
     * @CheckParam(name="data", regex="/^[0-9]*$/", length="5", errCode="ERR_DATA_1", required="false")
     * @CheckParam(name="validity", regex="/^[0-9a-zA-Z]*$/", length="5", errCode="ERR_VALIDITY_1", required="false")
     * @CheckParam(name="reference", regex="/^[0-9a-zA-Z]*$/", length="16", errCode="ERR_REFERENCE_1", required="false")
     *
     * @Post("/simProfiles/search")
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postSimProfileSearchAction(Request $request)
    {

        // Chargement de la langue
        $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
        /** @var Lang $lng */
        $lng = $rpl->findOneBy(array("local" => $request->get('lng')));
        if (!is_object($lng)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        // Construction du tableau de paramètre
        $_aFilters = array();
        $this->putParameterInFilters($request, $_aFilters, 'type');
        $this->putParameterInFilters($request, $_aFilters, 'media');
        $this->putParameterInFilters($request, $_aFilters, 'data');
        $this->putParameterInFilters($request, $_aFilters, 'reference');
        $this->putParameterInFilters($request, $_aFilters, 'geozone');
        $this->putParameterInFilters($request, $_aFilters, 'validity');

        try {
            /** @var  SimProfileService $_oSimProfileService */
            $_oSimProfileService = $this->get('api_backend.sim_profile_service');

            $_aSIMOfferList = $_oSimProfileService->findSimOffersByCriteria($lng, $_aFilters, $this->getUser()->getRoles()[0]);

            $view = $this->view(array('status' => 'OK', 'detail' => $_aSIMOfferList), Response::HTTP_OK); // 'detail' => $_aSIMOfferList,

            return $this->handleView($view);
        }// end try
        catch (\Exception $_oE){
            $this->get('logger')->error("class SimProfileController - méthode postSimProfileCountriesAction - " . $_oE->getMessage());
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        } // end catch

        return $this->handleView($view);
    } // end public function postSimProfileCountriesAction(Request $request)

    private function putParameterInFilters(Request $request, &$_aFilters, $_strParmName){
        if(!is_null($request->get($_strParmName)) && !empty($request->get($_strParmName)))
            $_aFilters[$_strParmName]=$request->get($_strParmName);
    } // private function putParameterInFilters(&$_aFilters, $_strParmName)
    /**
     * @ApiDoc(
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    },
     *    parameters={
     *      {"name"="lng", "dataType"="string", "required"=true, "description"="language / [a-zA-Z_]{5}"},
     *      {"name"="reference", "dataType"="string", "required"=false, "description"="référence de l'offre / .{0,16}"}
     *    }
     * )
     *
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="reference", regex="/^[0-9a-zA-Z]*$/", length="16", errCode="ERR_REFERENCE_1", required="false")
     *
     * @Post("/simProfiles/offer/topup")
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postSimProfileOfferTopupAction(Request $request)
    {
        $simOfferRepository = $this->getDoctrine()->getRepository(SimOffer::class);
        $langRepository = $this->getDoctrine()->getRepository(Lang::class);
        $_aSIMTopUpList = [];
        /** @var SimOffer $_oSimOffer */
        $_oSimOffer = $simOfferRepository->findOneBy(['reference' => $request->get('reference')]);
        if (!is_object($_oSimOffer)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_REFERENCE_2'))), Response::HTTP_BAD_REQUEST);
        }

        $_oSupplierRepository = $this->getDoctrine()->getRepository(Supplier::class);

        // Load supplier T2
        $_oSupplierT2 = $_oSupplierRepository->findAirtime();
        if (!is_object($_oSupplierT2))
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);

        // Load supplier Travel SIM Card
        $_oSupplierTravelSIMCard = $_oSupplierRepository->findOrangeLinkTravelSIMCard();
        if (!is_object($_oSupplierTravelSIMCard))
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);

        $_oLang = $langRepository->findOneBy(['local' => $request->get('lng')]);
        if (!is_object($_oLang)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_LNG_2'))), Response::HTTP_BAD_REQUEST);
        }

        // recupération du role user pour le visuel/visuel mobile
        $roleUser = $this->getUser()->getRoles()[0];
        $roleUserInfo = ($roleUser === User::ROLE_FRONT_APP_TRAVELERS) ? 'mobile' : 'site';

        if ($_oSimOffer->getSupplier() == $_oSupplierTravelSIMCard) {
            $reloadsResponse = $this->getTravelSimCardService()->getReloads($request->get('lng'),$roleUserInfo);
            $codeHttp = $reloadsResponse->code;
            if ($codeHttp === Response::HTTP_OK) {
                $operators = $reloadsResponse->result->detail->operators;
                foreach( $operators as $operator){
                    $reloadsResult = $operator->products;
                    foreach ($reloadsResult as $reload) {
                        $_aSIMTopUpList[] = array(
                            'reference' => $reload->productId,
                            'name' => $reload->productName,
                            'price' => $reload->productPrice,
                            'taxAndFee' => "0",
                            'currency' => $operator->localCurrency,
                            "binaryPic" => $reload->binaryPic,
                            "mimetypePic" => $reload->mimetypePic
                        );
                    }
                }
            }
        } elseif ($_oSimOffer->getSupplier() == $_oSupplierT2) {
            $productsAndOperatosResult = $this->getTransferToService()->getProductsAndOperatorsBySoldCountry($_oSimOffer->getSoldCountry(), $_oSimOffer->getSoldCountry()->getCountry(), $_oLang, $roleUser);
            foreach ($productsAndOperatosResult as $operatorAndProduct) {
                $products = $operatorAndProduct['products'];
                if (!is_null($products)) {
                    foreach ($operatorAndProduct['products'] as $product) {
                        $_aSIMTopUpList [] = array(
                            'reference' => $product['reference'],
                            'name' => $product['productType'] . ' ' . floatval($product['productValue']) . ' ' . $operatorAndProduct['localCurrency'],
                            'price' => floatval($product['productPrice']) . '',
                            'taxAndFee' => floatval($product['taxAndFee']) . '',
                            'currency' => $operatorAndProduct['localCurrency'],
                            "productDescription" => $product['productDescription'],
                            "productType" => $product['productType'],
                            "productData" => $product['productData'],
                            "productVoice" => $product['productVoice'],
                            "productSMS" => $product['productSMS'],
                            "productValidity" => $product['productValidity'],
                            "promoOrangeValue" => $product['promoOrangeValue'],
                            "promoOrangeDesc" => $product['promoOrangeDesc'],
                            "binaryPic" => $product['binaryPic'],
                            "mimetypePic" => $product['mimetypePic']
                        );
                    }
                }
            }
        }
        else
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_REFERENCE_3'))), Response::HTTP_BAD_REQUEST);

        $view = $this->view(array('status' => 'OK', 'detail' => $_aSIMTopUpList), Response::HTTP_OK);

        return $this->handleView($view);
    } // end public function postSimProfileOfferTopupAction(Request $request)
    /**
     * @ApiDoc(
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    },
     *    parameters={
     *      {"name"="lng", "dataType"="string", "required"=true, "description"="language / [a-zA-Z_]{5}"},
     *      {"name"="reference", "dataType"="string", "required"=false, "description"="référence de l'offre / .{0,16}"}
     *    }
     * )
     *
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="reference", regex="/^[0-9a-zA-Z]*$/", length="16", errCode="ERR_REFERENCE_1", required="false")
     *
     * @Post("/simProfiles/offer/sellers")
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postSimProfileOfferSellersAction(Request $request)
    {

        $_oSimProfileService = $this->get('api_backend.sim_profile_service');

        // Récupérer l'offre SIM demandée, vérifier qu'elle existe bien
        $_oSimOffer = $_oSimProfileService->getSimOfferByReference($request->get('reference'));
        if (!is_object($_oSimOffer)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_REFERENCE_2'))), Response::HTTP_BAD_REQUEST);
        }

        // Chargement de la langue
        $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
        $lng = $rpl->findOneBy(array("local" => $request->get('lng')));
        if (!is_object($lng)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        // Récupérer la liste des pays/revendeurs pour cette offre
        // renvoyer un tableau avec en premier niveau la zone géographique et la liste des revendeurs par zone
        $_aSellersList = $_oSimProfileService->findResellerByOfferAndGeoZones($_oSimOffer, $lng);

        $view = $this->view(array('status' => 'OK', 'detail' => $_aSellersList), Response::HTTP_OK);

        return $this->handleView($view);
    } // end public function postSimProfileOfferSellersAction(Request $request)

} // end of class
