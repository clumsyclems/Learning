<?php

namespace ApiFrontBundle\Controller;

use ApiBackendBundle\Entity\CustomEntity;
use ApiBackendBundle\Entity\TrvlSimIdentification;
use ApiBackendBundle\Entity\User;
use ApiBackendBundle\Exception\SendExceptionAsResponse;
use ApiBackendBundle\Service\SimResellerService;
use ApiBackendBundle\Service\TravelSimCardService;
use ApiFrontBundle\Annotation\CheckParam;
use ApiFrontBundle\Entity\Customer;
use ApiFrontBundle\Entity\CustomerLimitSurvey;
use ApiFrontBundle\Entity\CustomerToken;
use ApiFrontBundle\Entity\CustomerValidationToken;
use DateTime;
use FOS\RestBundle\Controller\Annotations\Post;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends CustomerControllerBase
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

    /**
     * @ApiDoc(
     *     description="Get Countries opened for SIM client",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *          {"name"="lng", "dataType"="string", "required"=true, "description"="lang locale / (fr_FR)|(en_EN)|(it_IT)|(pt_PT)|(de_DE)|(es_ES)"},
     *     }
     * )
     *
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     *
     * @Post("/customer/travelerRegistrationCountries")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */

    public function postCustomerTravelerRegistrationCountriesAction(Request $request)
    {
        $details = [];

        $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
        $lang = $rpl->findOneBy(array('local' => $request->get('lng')));

        $rpcl = $this->getDoctrine()->getRepository('ApiBackendBundle:CountryLabel');
        $countries = $rpcl->findBy(array('lang' => $lang->getId()));

        foreach ($countries as $country) {
            $detail['iso'] = $country->getCountry()->getISOCode();
            $detail['name'] = $country->getWording();
            array_push($details, $detail);
        }

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => $details,
        ));
        return $this->handleView($view);
    } // end of method postCustomerTravelerRegistrationCountriesAction

    /**
     * @ApiDoc(
     *     description="Get Countries opened for account creation",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *          {"name"="lng", "dataType"="string", "required"=true, "description"="language / [a-zA-Z_]{5}"}
     *     }
     * )
     *
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     *
     * @Post("/customer/offers")
     * @param $request
     * @return Response
     */
    public function postCountriesAction(Request $request)
    {
        $rp = $this->getDoctrine()->getRepository('ApiBackendBundle:Country');
        $countries = $rp->findBy(array('canSMS' => true));
        $detailedCountries = array();

        $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
        $lng = $rpl->findOneBy(array("local" => $request->get('lng')));
        if (!is_object($lng)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        foreach ($countries as $country) {

            if ($country->getCountryLabelByLang($lng)[0] != null) {
                $lbl = $country->getCountryLabelByLang($lng)[0]->getWording();
            } else {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            }

            $detailedCountries[] = array(
                'iso' => $country->getISOCode(),
                'name' => $lbl,
            );
        }

        // create standard offer
        $_standard = array(
            "type" => "standard",
            "countries" => $detailedCountries,
        );

        $_aReturn = array($_standard);

        // add Travel SIM Card offer only if parameters is set to on...
        $parameterRepository = $this->getDoctrine()->getRepository('ApiBackendBundle:Parameter');
        $_bIsTravelersActivated = $parameterRepository->findTravelSIMCardOffer()->getValue();
        if ($_bIsTravelersActivated == "true") {
            // create travelers offer
            $_travelers = array(
                "type" => "travelsimcard",
            );
            $_aReturn[] = $_travelers;
        }

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => $_aReturn,
        ));
        return $this->handleView($view);

    } // end of method postCountriesAction

    /**
     * @ApiDoc(
     *     description="For fidelity program, get total top up amount from a customer id",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="id", "dataType"="string", "required"="true", "description"="Customer's email / .{1,40}"}
     *     }
     * )
     *
     * @CheckParam(name="id", length="1-40", errCode="ERR_ID_1", required="true")
     *
     * @Post("/customer/topuptotalamount")
     * @param $request
     * @return Response
     */
    public function getTopUpAmountByIdAction(Request $request)
    {
        $details = array();

        $rp = $this->getDoctrine()->getRepository('ApiFrontBundle:Customer');
        $_oCustomer = $rp->findOneById($request->get('id'));
        $error = array();
        if (!is_object($_oCustomer)) {
            $error[] = array('errorCode' => 'ERR_ID_2');
        }

        if (sizeof($error) > 0) {
            throw new SendExceptionAsResponse(json_encode($error), Response::HTTP_BAD_REQUEST);
        } else {

            $_fTotal = $rp->getTotalAmountByCustomer($_oCustomer);
            $details['id'] = $_oCustomer->getId();
            $details['email'] = $_oCustomer->getEmail();
            $details['total'] = $_fTotal;

            $view = $this->view(array(
                'status' => 'OK',
                'detail' => $details,
            ));
            return $this->handleView($view);

        } // end else

    } // end of method getTopUpAmountByIdAction

    /**
     * @ApiDoc(
     *     description="Create a new customer",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="name", "dataType"="string", "required"="false", "description"="Customer's name / .{1,100}"},
     *         {"name"="surname", "dataType"="string", "required"="false", "description"="Customer's surname / .{1,100}"},
     *         {"name"="email", "dataType"="string", "required"="true", "description"="Customer's email / .{5,255}"},
     *         {"name"="prefix", "dataType"="string", "required"=false, "description"="phone region code / [0-9]{1,5}"},
     *         {"name"="phone", "dataType"="string", "required"=false, "description"="phone number / [0-9]{6,15}"},
     *         {"name"="pwd", "dataType"="string", "required"=true, "description"="password / .{8,50}"},
     *         {"name"="deviceId", "dataType"="string", "required"=false, "description"="password / .{1,64}"},
     *         {"name"="lng", "dataType"="string", "required"=true, "description"="lang locale / (fr_FR)|(en_EN)|(it_IT)|(pt_PT)|(de_DE)"},
     *         {"name"="country", "dataType"="string", "required"=false, "description"="country Iso Code / [A-Z]{2}"},
     *         {"name"="sendingCountries", "dataType"="array", "required"=false, "description"="array of Iso Codes / array(string, string, ...)"},
     *         {"name"="newsletter", "dataType"="bool", "required"=false, "description"="password / .{8,50}"},
     *         {"name"="offer", "dataType"="string", "required"=true, "description"="customer offer / (travelers)|(standard)"},
     *         {"name"="simId", "dataType"="string", "required"=false, "description"="SIM ID / [0-9]{10,13}"}
     *     }
     * )
     *
     * @CheckParam(name="email", length="5-255", errCode="ERR_EMAIL_1")
     * @CheckParam(name="pwd", length="8-50", errCode="ERR_PWD_1")
     * @CheckParam(name="deviceId", length="64", errCode="ERR_DEVICEID_1", required="false")
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="newsletter", regex="/(0|1)/", length="1", errCode="ERR_NEWSLETTER_1", required="true")
     * @CheckParam(name="offer", regex="/^[a-zA-Z]*$/", length="15", errCode="ERR_OFFER_1")
     * @CheckParam(name="offer", regex="/(travelsimcard)|(standard)/", errCode="ERR_OFFER_2")
     *
     * @Post("/customer/create")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */

    public function postCustomerCreateAction(Request $request)
    {

        $logger = $this->get('logger');
        $rp = $this->getDoctrine()->getRepository('ApiFrontBundle:Customer');
        $rpc = $this->getDoctrine()->getRepository('ApiBackendBundle:Country');
        $rpParameter = $this->getDoctrine()->getRepository('ApiBackendBundle:Parameter');
        $em = $this->getDoctrine()->getManager();

        // Check parameters
        $error = array();
        if (is_object($rp->findOneBy(array('email' => $request->get('email'))))) {
            $error[] = array('errorCode' => 'ERR_EMAIL_2');
        }

        $_oSim = null;
        // Standard offer :
        if ($request->get('offer') == Customer::OFFER_STANDARD) {
            // country is mandatory
            if (empty($request->get('country'))) {
                $error[] = array('errorCode' => 'ERR_COUNTRY_1');
            } else {
                $cntry = $rpc->findOneBy(array('iSOCode' => $request->get('country')));
                if (!is_object($cntry)) {
                    $error[] = array('errorCode' => 'ERR_COUNTRY_2');
                }

            }

            $smsValidation = $rpParameter->findOneBy(['code' => 'CUSTOMER_SMS_VALIDATION']);
            // if sms validation mode is ON
            if ($smsValidation->getValue() === 'true') {
                // prefix + phone mandatory
                if (empty($request->get('prefix')) || !is_numeric($request->get('prefix')) || strlen($request->get('prefix')) > 5) {
                    $error[] = array('errorCode' => 'ERR_PREFTEL_1');
                }

                if (empty($request->get('phone')) || !is_numeric($request->get('phone')) || strlen($request->get('phone')) > 15) {
                    $error[] = array('errorCode' => 'ERR_TEL_1');
                }

                //Check phone code validity
                if (!empty($request->get('prefix'))) {
                    $countries = $rpc->findBy(array('phoneCode' => $request->get('prefix')));
                    if (is_null($countries) || !is_array($countries) || count($countries) == 0) {
                        $error[] = array('errCode' => 'ERR_PREFTEL_2');
                    }

                } //endif(!empty($request->get('prefix'))

                // phone needs to be unique
                if (is_object($rp->findOneBy(array('phoneCode' => $request->get('prefix'), 'phoneNumber' => $request->get('phone'))))) {
                    $error[] = array('errorCode' => 'ERR_TEL_2');
                }
            }

        } // end if($request->get('offer')==Customer::OFFER_STANDARD)
        // Travel SIM Card offer : simId mandatory
        elseif ($request->get('offer') == Customer::OFFER_TRAVELERS) {
            $parameterRepository = $this->getDoctrine()->getRepository('ApiBackendBundle:Parameter');
            $_bIsTravelersActivated = $parameterRepository->findTravelSIMCardOffer()->getValue();
            // Travelers asked for creation but travelers offer is disabled...
            if ($_bIsTravelersActivated != "true") {
                $error[] = array('errorCode' => 'ERR_OFFER_3');
            }

            // simId is mandatory

            if (empty($request->get('simId'))) {
                $error[] = array('errorCode' => 'ERR_SIMID_1');
            }
            $simMsisdn = null;
            if(!empty($request->get('simId'))){
                //SIM not provisionned ? -> ERR || SIM not found ? -> ERR || SIM not actif -> ERR
                $checkSimIdResponse = $this->getTravelSimCardService()->checkSimIdToBeLinked($request->get('simId'));
                $httpCode = $checkSimIdResponse->code;
                if($httpCode===Response::HTTP_NOT_FOUND || $httpCode===Response::HTTP_INTERNAL_SERVER_ERROR){
                    throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
                }
                $checkSimIdResult = $checkSimIdResponse->result;
                if($checkSimIdResult->status==="OK") {
                    $simMsisdn = $checkSimIdResult->detail->msisdn;
                }else{
                    $error[] = $checkSimIdResult->detail;
                }
            }

            // simId needs to be unique in customer database (already Use ?) -> ERR
            if (is_object($rp->findOneBy(array('simId' => $request->get('simId'))))) {
                $error[] = array('errorCode' => 'ERR_SIMID_3');
            }

            // country should not be filled
            if (!empty($request->get('country'))) {
                $error[] = array('errorCode' => 'ERR_COUNTRY_3');
            }

        } // end if($request->get('offer')==Customer::OFFER_TRAVELERS)
        else {
            $error[] = array('errorCode' => 'ERR_OFFER_2');
        }

        if (sizeof($error) > 0) {
            throw new SendExceptionAsResponse(json_encode($error, JSON_THROW_ON_ERROR), Response::HTTP_BAD_REQUEST);
        } else {

            if ($request->get('newsletter') == "0") {
                $_bNewsletter = false;
            } else {
                $_bNewsletter = true;
            }

            $_sPhoneNumber = null;
            $_sPhoneCode = null;
            $_sSimId = null;
            if ($request->get('offer') == Customer::OFFER_STANDARD) {
                $_sPhoneNumber = $request->get('phone');
                $_sPhoneCode = $request->get('prefix');
            } else {
                $_sSimId = $request->get('simId');
            }

            $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
            $customer = new Customer();
            $customer->populate(array(
                'firstName' => $request->get('name'),
                'lastName' => $request->get('surname'),
                'email' => $request->get('email'),
                'password' => password_hash($request->get('pwd'), PASSWORD_DEFAULT),
                'phoneCode' => $_sPhoneCode,
                'phoneNumber' => $_sPhoneNumber,
                'simId' => $_sSimId,
                'deviceId' => $request->get('deviceId'),
                'acceptNewsletter' => $_bNewsletter,
                'status' => Customer::STATUS_VALIDATION,
                'lang' => $rpl->findOneBy(array('local' => $request->get('lng'))),
                'lastLogin' => new \Datetime(),
                'totalTransaction' => 0,
                'offer' => $request->get('offer'),
                'lastEditor' => Customer::SYSTEM,
                'simMsisdn' => $simMsisdn,
            ));

            // Customer is filled only for the standard offer
            if ($request->get('offer') == Customer::OFFER_STANDARD) {
                $customer->setCountry($rpc->findOneBy(array('iSOCode' => $request->get('country'))));
            }

            if(is_array($request->get('sendingCountries'))) {
                foreach ($request->get('sendingCountries') as $country) {
                    $c = $rpc->findOneBy(array('iSOCode' => $country));
                    if (!is_object($c)) {
                        throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_SENDER_2'))), Response::HTTP_BAD_REQUEST);
                    } else {
                        $customer->addSenderCountry($c);
                    }
                } // end foreach ($request->get('sendingCountries') as $country)
            } // end if(is_array($request->get('sendingCountries')))

            // Set payment One Click Id
            $customer->setPaymentOneClickId();
            $logger->info($customer->getPaymentOneClickId());

            $em->persist($customer);

            $token = new CustomerToken($customer);
            $em->persist($token);

            $logger->info('New customer account ' . json_encode($customer, JSON_THROW_ON_ERROR));

            // Account create -> status = waiting for validation
            // create a email token
            // send the email token to validate email address
            $this->createValidationCode($customer, true, true);

            $em->flush();
            $view = $this->view(array(
                'status' => 'OK',
                'detail' => array('authToken' => $token->getToken(), "validationType" => $customer->getValidationType())
            ));
            return $this->handleView($view);
        }
    } // end of method postCustomerCreateAction

    /**
     * @ApiDoc(
     *     description="Create a new SIM traveler identification",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="lng", "dataType"="string", "required"=true, "description"="Lang locale / (fr_FR)|(en_EN)|(it_IT)|(pt_PT)|(de_DE)|(es_ES)"},
     *         {"name"="simId", "dataType"="string", "required"=true, "description"="SIM ID / .{1,13}"},
     *         {"name"="email", "dataType"="string", "required"="true", "description"="Customer's email / .{5,255}"},
     *         {"name"="title", "dataType"="string", "required"="true", "description"="Customer's title / (Mr)|(Mrs)"},
     *         {"name"="firstname", "dataType"="string", "required"="true", "description"="Customer's firstname / .{1,255}"},
     *         {"name"="lastname", "dataType"="string", "required"="true", "description"="Customer's lastname / .{1,255}"},
     *         {"name"="birthDept", "dataType"="string", "required"="true", "description"="Customer's birth department number / .[0-9]{2}"},
     *         {"name"="address", "dataType"="string", "required"=true, "description"="Customer's address / .{1,500}"},
     *         {"name"="zipCode", "dataType"="string", "required"=true, "description"="Customer's zip code / .{1,80}"},
     *         {"name"="city", "dataType"="string", "required"=true, "description"="Customer's city / .{1,255}"},
     *         {"name"="country", "dataType"="string", "required"=true, "description"="country Iso Code / .{2}"},
     *         {"name"="fileType", "dataType"="string", "required"="true", "description"="File's type / (doc)|(docx)|(pdf)|(jpg)|(png)"},
     *         {"name"="birthDate", "dataType"="string", "required"="true", "description"="Customer's birth date /  .{10}"},
     *
     *         {"name"="file", "dataType"="string", "required"="true", "description"="File"},
     *     }
     * )
     *
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="simId", regex="/^[0-9]{13}$/", length="1-13", errCode="SIMID_1")
     * @CheckParam(name="email", length="5-255", errCode="EMAIL_1")
     * @CheckParam(name="title", regex="/^[a-zA-Z_]*$/", length="2-3", errCode="TITLE_1")
     * @CheckParam(name="title", regex="/(Mr)|(Mrs)/", errCode="TITLE_2")
     * @CheckParam(name="firstname", length="1-255", errCode="FIRSTNAME_1")
     * @CheckParam(name="lastname", length="1-255", errCode="LASTNAME_1")
     * @CheckParam(name="birthDept", regex="/[0-9]{2}/", errCode="BIRTHDEPT_1")
     * @CheckParam(name="birthDate", regex="/[0-9]{8}/", errCode="BIRTHDATE_1")
     * @CheckParam(name="address", length="1-500", errCode="ADDRESS_1")
     * @CheckParam(name="zipCode", length="1-80", errCode="ZIPCODE_1")
     * @CheckParam(name="city", length="1-255", errCode="CITY_1")
     * @CheckParam(name="country", regex="/[A-Z]{2}/", errCode="COUNTRY_1")
     * @CheckParam(name="fileType", length="1-10", errCode="FILETYPE_1")
     * @CheckParam(name="fileType", regex="/(doc)|(docx)|(pdf)|(jpg)|(png)/", errCode="FILETYPE_2")
     *
     * @Post("/customer/travelerRegistration")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */

    public function postCustomerTravelerRegistrationAction(Request $request)
    {

        $params = array('lng' => $request->get('lng'),
                        'title' => $request->get('title'),
                        'simId'=>$request->get('simId'),
                        'country'=>$request->get('country'),
                        'birthDate'=>$request->get('birthDate'),
                        'file'=>$request->get('file'),
                        'fileType'=>$request->get('fileType'),
                        'firstname'=>$request->get('firstname'),
                        'lastname'=>$request->get('lastname'),
                        'birthDept'=>$request->get('birthDept'),
                        'address'=>$request->get('address'),
                        'zipCode'=>$request->get('zipCode'),
                        'city'=>$request->get('city'),
                        'email'=>$request->get('email'));
        $travelerResgistrationResponse = $this->getTravelSimCardService()->travelerRegistration($params);
        //connexion failed
        // Service travelSimCard send http 500 error
        // Service travelSimCard send http 400 error
        $httpCode = $travelerResgistrationResponse->code;
        $travelerResgistrationResult = $travelerResgistrationResponse->result;
        if($httpCode===Response::HTTP_NOT_FOUND || $httpCode===Response::HTTP_INTERNAL_SERVER_ERROR){
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        return (new JsonResponse())->create($travelerResgistrationResult, $httpCode);

    } // end of method postCustomerTravelerRegistration

    /**
     * @ApiDoc(
     *     description="Validate customer phone or email",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *         {"name"="code", "dataType"="string", "required"="true", "description"="Validation code sent by mail or sms/ .[0-9]{6}"}
     *     }
     * )
     *
     * @CheckParam(name="authToken", regex="/.{1,40}/", errCode="ERR_AUTHTOKEN_1")
     * @CheckParam(name="code", regex="/[0-9]{6}/", errCode="ERR_CODE_1")
     *
     * @Post("/customer/validate")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCustomerValidateAction(Request $request)
    {
        $logger = $this->get('logger');
        $em = $this->getDoctrine()->getManager();

        // check token and order ability
        $this->checkAuthToken($request);

        // Find customer
        $c = $this->getCustomer();

        $_oRpParam = $em->getRepository('ApiBackendBundle:Parameter');
        $_oLimitParameter = $_oRpParam->findValidationLimit();
        if (!is_object($_oLimitParameter) || !is_numeric($_oLimitParameter->getValue())) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        if ($c->getStatus() == Customer::STATUS_VALIDATION) {

            // Sécurité sur le nombre de tentative de validation
            if (is_null($c->getValidationAttempts())) {
                $c->setValidationAttempts(0);
            }

            // Check validation code
            // Update Customer status if validation is ok
            // Case no code generated
            if (is_null($c->getValidationCode())) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_CODE_5'))), Response::HTTP_BAD_REQUEST);
            }

            // Case code already validated
            elseif (!is_null($c->getValidationValidatedAt()) || $c->getStatus() != Customer::STATUS_VALIDATION) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_CODE_4'))), Response::HTTP_BAD_REQUEST);
            }

            // Case code expired
            elseif (date_add($c->getValidationCreatedAt(), date_interval_create_from_date_string('1 hour'))->getTimestamp() < time()) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_CODE_3'))), Response::HTTP_BAD_REQUEST);
            }

            // Case attempts > limit
            elseif ($c->getValidationAttempts() + 1 > (int) $_oLimitParameter->getValue()) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_CODE_7'))), Response::HTTP_BAD_REQUEST);
            }

            // Case code not valid
            elseif ($request->get('code') != $c->getValidationCode()) {

                $c->setValidationAttempts($c->getValidationAttempts() + 1);
                $em->persist($c);
                $em->flush();
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_CODE_2'))), Response::HTTP_BAD_REQUEST);

            } else {
                $c->setValidationValidatedAt(new DateTime());
                $this->validateAccount($c);
                $em->persist($c);
                $em->flush();

                $view = $this->view(array(
                    'status' => 'OK',
                    'detail' => array(),
                ));
                return $this->handleView($view);
            } // end else if(is_null($c->getValidationCode()))
        } //endif($c->getStatus()==Customer::STATUS_VALIDATION)
        elseif ($c->getStatus() == Customer::STATUS_ACTIVE) {
            // Get Parameter SMS ACCOUNT VALIDATION
            $_oRpParam = $em->getRepository('ApiBackendBundle:Parameter');
            $_oRpCVT = $em->getRepository('ApiFrontBundle:CustomerValidationToken');
            $rp = $em->getRepository('ApiFrontBundle:Customer');
            $_oSMSParameter = $_oRpParam->findCustomerSMSValidation();
            if (!is_object($_oSMSParameter)) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            }

            // if system is on validation mail -> look for a validation token mail based
            if ($_oSMSParameter->getValue() != "true") {
                $_strType = CustomerValidationToken::TYPE_MAIL;
            }

            // if system is on validation sms ->   look for a validation token sms based
            else {
                $_strType = CustomerValidationToken::TYPE_SMS;
            }

            $_oValidationToken = $_oRpCVT->findOneBy(array('customer' => $c, 'type' => $_strType));

            // Case code does not exist
            if (is_null($_oValidationToken) || !is_object($_oValidationToken)) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_CODE_5'))), Response::HTTP_BAD_REQUEST);
            }

            // Case code already validated
            elseif (!is_null($_oValidationToken->getValidatedAt())) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_CODE_4'))), Response::HTTP_BAD_REQUEST);
            }

            // Case code expired
            elseif (date_add($_oValidationToken->getCreatedAt(), date_interval_create_from_date_string('1 hour'))->getTimestamp() < time()) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_CODE_3'))), Response::HTTP_BAD_REQUEST);
            }

            // Case limit attempts reached
            elseif ($_oValidationToken->getValidationAttempts() + 1 > (int) $_oLimitParameter->getValue()) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_CODE_7'))), Response::HTTP_BAD_REQUEST);
            }

            // Case code not valid
            elseif ($request->get('code') != $_oValidationToken->getToken()) {

                $_oValidationToken->setValidationAttempts($_oValidationToken->getValidationAttempts() + 1);
                $em->persist($_oValidationToken);
                $em->flush();

                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_CODE_2'))), Response::HTTP_BAD_REQUEST);
            }

            // Case success
            else {
                if ($_oSMSParameter->getValue() == "true") {
                    // Check not to change phone if already exists in the database
                    if (is_object($rp->findOneBy(array('phoneCode' => $_oValidationToken->getPhoneCode(), 'phoneNumber' => $_oValidationToken->getPhoneNumber())))) {
                        throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_CODE_6'))), Response::HTTP_BAD_REQUEST);
                    }

                    $c->setPhoneCode($_oValidationToken->getPhoneCode());
                    $c->setPhoneNumber($_oValidationToken->getPhoneNumber());
                } else {
                    // Check not to change mail If mail already exists in the database
                    if (is_object($rp->findOneBy(array('email' => $_oValidationToken->getEmail())))) {
                        throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_CODE_6'))), Response::HTTP_BAD_REQUEST);
                    }

                    $c->setEmail($_oValidationToken->getEmail());
                }
                $_oValidationToken->setValidatedAt(new DateTime());
                $_oValidationToken->setIsValidated(true);
                $em->persist($c);
                $em->persist($_oValidationToken);
                $em->flush();
                $view = $this->view(array(
                    'status' => 'OK',
                    'detail' => array(),
                ));
                return $this->handleView($view);

            } // end else if(is_null($_oValidationToken)||!is_object($_oValidationToken))
        } // end elseif($c->getStatus()==Customer::STATUS_ACTIVE)
        else {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_AUTHTOKEN_4'))), Response::HTTP_BAD_REQUEST);
        }

    } // end of method postCustomerValidateAction

    /**
     * @ApiDoc(
     *     description="Edit customer informations",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *         {"name"="name", "dataType"="string", "required"="true", "description"="Customer's name / .{1,100}"},
     *         {"name"="surname", "dataType"="string", "required"="true", "description"="Customer's surname / .{1,100}"},
     *         {"name"="deviceId", "dataType"="string", "required"=false, "description"="password / .{1,64}"},
     *         {"name"="pwd", "dataType"="string", "required"=false, "description"="authentication token / .{8,50}"},
     *         {"name"="photo", "dataType"="string", "required"=false, "description"="path for user's profile photo / .{1,255}"}
     *     }
     * )
     *
     * @CheckParam(name="authToken", length="40", errCode="ERR_AUTHTOKEN_1")
     * @CheckParam(name="name", length="100", errCode="ERR_NAME_1", required="false")
     * @CheckParam(name="surname", length="100}", errCode="ERR_SURNAME_1", required="false")
     * @CheckParam(name="pwd", length="8-50", errCode="ERR_PWD_1", required="false")
     * @CheckParam(name="photo", errCode="ERR_PHOTO_1", required="false")
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1", required="false")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2", required="false")
     * @CheckParam(name="deviceId", length="64", errCode="ERR_DEVICEID_1", required="false")
     * @CheckParam(name="country", regex="/^[A-Z]*$/", length="2-2", errCode="ERR_COUNTRY_1", required="false")
     * @CheckParam(name="newsletter", regex="/(0|1)/", length="1", errCode="ERR_NEWSLETTER_1", required="false")
     * @CheckParam(name="email", length="5-255", errCode="ERR_EMAIL_1", required="false")
     * @CheckParam(name="prefix", regex="/^[0-9]*$/", length="5", errCode="ERR_PREFTEL_1", required="false")
     * @CheckParam(name="phone", regex="/^[0-9]*$/", length="6-15", errCode="ERR_TEL_1", required="false")
     *
     * @Post("/customer/edit")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCustomerEditAction(Request $request)
    {

        // check token and order ability
        $this->checkAuthToken($request);

        $error = array();
        $_aReturnValues = array();
        // can not have a prefix without a phone and vice versa
        if (!empty($request->get('prefix')) && empty($request->get('phone'))) {
            $error[] = array('errCode' => 'ERR_TEL_1');
        }

        if (empty($request->get('prefix')) && !empty($request->get('phone'))) {
            $error[] = array('errCode' => 'ERR_PREFTEL_1');
        }

        if ((!empty($request->get('pwd')) && empty($request->get('oldPwd')))) {
            $error[] = array('errCode' => 'ERR_PWD_3');
        }
        if (!empty($request->get('oldPwd')) && empty($request->get('pwd'))) {
            $error[] = array('errCode' => 'ERR_PWD_3');
        }
        //Check phone code validity
        if (!empty($request->get('prefix'))) {
            $rp = $this->getDoctrine()->getRepository('ApiBackendBundle:Country');
            $countries = $rp->findBy(array('phoneCode' => $request->get('prefix')));
            if (is_null($countries) || !is_array($countries) || count($countries) == 0) {
                $error[] = array('errCode' => 'ERR_PREFTEL_2');
            }

        } //endif(!empty($request->get('prefix'))

        if (sizeof($error) > 0) {
            throw new SendExceptionAsResponse(json_encode($error), Response::HTTP_BAD_REQUEST);
        } else {
            $rpc = $this->getDoctrine()->getRepository('ApiBackendBundle:Country');
            $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
            $rp = $this->getDoctrine()->getRepository('ApiFrontBundle:Customer');
            $em = $this->getDoctrine()->getManager();
            $factory = $this->get('security.encoder_factory');
            $_oCust = $this->getCustomer();
            $encoder = $factory->getEncoder($_oCust);

            if ($_oCust->getStatus() !== Customer::STATUS_ACTIVE) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_AUTHTOKEN_2'))), Response::HTTP_BAD_REQUEST);
            }

            if ($request->get('newsletter') == "0") {
                $_bNewsletter = false;
            } else {
                $_bNewsletter = true;
            }

            if (!empty($request->get('pwd')) && !empty($request->get('oldPwd')) && !$encoder->isPasswordValid($_oCust->getPassword(), $request->get('oldPwd'), '')) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PWD_2'))), Response::HTTP_BAD_REQUEST);
            }
            // Get Parameter SMS ACCOUNT VALIDATION
            $_oRpParam = $em->getRepository('ApiBackendBundle:Parameter');
            $_oSMSParameter = $_oRpParam->findCustomerSMSValidation();
            if (!is_object($_oSMSParameter)) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
            }

            // If customer offer = travelers, customer can not change his phone number
            if ($_oCust->getOffer() == Customer::OFFER_TRAVELERS && !empty($request->get('country'))) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_COUNTRY_3'))), Response::HTTP_BAD_REQUEST);
            } // end if($_oCust->getOffer()==Customer::OFFER_TRAVELERS)

            // Prepare array for changing values
            $params = array(
                'lastName' => is_null($request->get('surname')) ? $_oCust->getLastName() : $request->get('surname'),
                'firstName' => is_null($request->get('name')) ? $_oCust->getFirstName() : $request->get('name'),
                'deviceId' => is_null($request->get('deviceId')) ? $_oCust->getDeviceId() : $request->get('deviceId'),
                'acceptNewsletter' => is_null($request->get('newsletter')) ? $_oCust->getAcceptNewsletter() : $_bNewsletter,
                'password' => is_null($request->get('pwd')) ? $_oCust->getPassword() : password_hash($request->get('pwd'), PASSWORD_DEFAULT),
                'profilePicture' => is_null($request->get('photo')) ? $_oCust->getProfilePicture() : $request->get('photo'),
                'country' => is_null($request->get('country')) ? $_oCust->getCountry() : $rpc->findOneBy(array('iSOCode' => $request->get('country'))),
                'lang' => is_null($request->get('lng')) ? $_oCust->getLang() : $rpl->findOneBy(array('local' => $request->get('lng'))),
            );

            // Mail and phone update require special control and actions depending on the $_oSMSParameter value
            // If parameter is on and phone change is asked for -> need a validation token
            if (!empty($request->get('prefix')) && !empty($request->get('phone'))) {
                // If customer offer = travelers, customer can not change his phone number
                if ($_oCust->getOffer() == Customer::OFFER_TRAVELERS) {
                    throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_TEL_4'))), Response::HTTP_BAD_REQUEST);
                } // end if($_oCust->getOffer()==Customer::OFFER_TRAVELERS)

                // If phone changed is same than actual -> error
                if ($_oCust->getPhoneCode() == $request->get('prefix') && $_oCust->getPhoneNumber() == $request->get('phone')) {
                    throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_TEL_3'))), Response::HTTP_BAD_REQUEST);
                }

                // If phone already exists in the database
                if (is_object($rp->findOneBy(array('phoneCode' => $request->get('prefix'), 'phoneNumber' => $request->get('phone'))))) {
                    throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_TEL_2'))), Response::HTTP_BAD_REQUEST);
                }

                if ($_oSMSParameter->getValue() == 'true') {
                    // create validation token, store new phone to validate
                    $this->purgeValidationTokensForACustomer($_oCust);
                    $_oCustValidationToken = new CustomerValidationToken($_oCust, CustomerValidationToken::TYPE_SMS);
                    $_oCustValidationToken->setPhoneCode($request->get('prefix'));
                    $_oCustValidationToken->setPhoneNumber($request->get('phone'));
                    $em->persist($_oCustValidationToken);

                    // Send SMS for validation
                    $this->sendValidationToken($_oCust, $_oCustValidationToken);
                    $_aReturnValues = array('validationRequired' => CustomerValidationToken::TYPE_SMS);

                } // endif($_oSMSParameter->getValue()=='on' && !empty($request->get('prefix')) && !empty($request->get('phone')))
                // no need to validate phone -> change prefix and phone directly
                else {
                    $params['phoneCode'] = $request->get('prefix');
                    $params['phoneNumber'] = $request->get('phone');
                } // end else if($_oSMSParameter->getValue()=='on')
            } // end if(!empty($request->get('prefix')) && !empty($request->get('phone')))

            // If parameter is not on and mail change is asked for -> need a validation token
            if (!empty($request->get('email'))) {
                // If mail changed is same than actual -> error
                if ($_oCust->getEmail() == $request->get('email')) {
                    throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_EMAIL_3'))), Response::HTTP_BAD_REQUEST);
                }

                // If mail already exists in the database
                if (is_object($rp->findOneBy(array('email' => $request->get('email'))))) {
                    throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_EMAIL_2'))), Response::HTTP_BAD_REQUEST);
                }

                if ($_oSMSParameter->getValue() != 'true') {
                    // create validation token, store new phone to validate
                    $this->purgeValidationTokensForACustomer($_oCust);
                    $_oCustValidationToken = new CustomerValidationToken($_oCust, CustomerValidationToken::TYPE_MAIL);
                    $_oCustValidationToken->setEmail($request->get('email'));
                    $em->persist($_oCustValidationToken);

                    // Send token for validation
                    $this->sendValidationToken($_oCust, $_oCustValidationToken);
                    $_aReturnValues = array('validationRequired' => CustomerValidationToken::TYPE_MAIL);
                } // endif($_oSMSParameter->getValue()!='on' && !empty($request->get('email')))
                // no need to validate mail -> change mail directly
                elseif (!empty($request->get('email'))) {
                    $params['email'] = $request->get('email');
                }

            } // end if(!empty($request->get('email')))

            $_oCust->populate($params);
            if (!is_null($request->get('sendingCountries'))) {
                $_oCust->clearSenderCountries();
                foreach ($request->get('sendingCountries') as $country) {
                    $c = $rpc->findOneBy(array('iSOCode' => $country));
                    if (!is_object($c)) {
                        throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_SENDER_1'))), Response::HTTP_BAD_REQUEST);
                    } else {
                        $_oCust->addSenderCountry($c);
                    }
                }
            } // end if(!is_null($request->get('sendingCountries')))
            $_oCust->setLastEditor(CustomEntity::SYSTEM);
            $em->persist($_oCust);
            $em->flush();
            $view = $this->view(array(
                'status' => 'OK',
                'detail' => $_aReturnValues,
            ));
            return $this->handleView($view);
        }

    } // end of method postCustomerEditAction

    /**
     * @ApiDoc(
     *     description="Delete customer account",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *         {"name"="pwd", "dataType"="string", "required"=false, "description"="authentication token / .{8,50}"}
     *     }
     * )
     *
     * @CheckParam(name="authToken", length="40", errCode="ERR_AUTHTOKEN_1")
     * @CheckParam(name="pwd", length="8-50", errCode="ERR_PWD_1")
     *
     * @Post("/customer/remove")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */

    public function postCustomerRemoveAction(Request $request)
    {

        // check token
        $this->checkAuthToken($request);

        $error = array();
        $customer = $this->getCustomer();
        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($customer);
        if (!$encoder->isPasswordValid($customer->getPassword(), $request->get('pwd'), '')) {
            $error[] = array('errCode' => 'ERR_PWD_2');
        }
        if (sizeof($error) > 0) {
            throw new SendExceptionAsResponse(json_encode($error), Response::HTTP_BAD_REQUEST);
        } else {
            $em = $this->getDoctrine()->getManager();
            //$customer->setStatus(Customer::STATUS_INACTIVE);
            //$customer->setLastEditor(CustomEntity::SYSTEM);
            //$em->persist($customer);

            // delete previous mail tokens
            $this->purgeValidationTokensForACustomer($customer);
            // delete previous auth tokens
            $this->purgeAuthTokensForACustomer($customer);

            // delete customer id of transactions
            $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction')->removeCustomerFromTransaction($customer->getId());

            // delete customer limit surveys
            $customerLimitSurveys = $this->getDoctrine()->getRepository('ApiFrontBundle:CustomerLimitSurvey')->findBy(array('customer' => $customer));
            foreach ($customerLimitSurveys as $customerLimitSurvey) {
                $em->remove($customerLimitSurvey);
            }

            // delete customer
            $em->remove($customer);

            $em->flush();
            $view = $this->view(array(
                'status' => 'OK',
                'detail' => array(),
            ));
            return $this->handleView($view);
        }
    } // end of method postCustomerRemoveAction

    /**
     * @ApiDoc(
     *     description="Authenticate a customer",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="email", "dataType"="string", "required"="true", "description"="Customer's email / .{5,255}"},
     *         {"name"="prefix", "dataType"="string", "required"=true, "description"="phone region code / [0-9]{1,5}"},
     *         {"name"="phone", "dataType"="string", "required"=true, "description"="phone number / [0-9]{6,15}"},
     *         {"name"="simId", "dataType"="string", "required"=false, "description"="SIM ID / [0-9]{10,13}"},
     *         {"name"="pwd", "dataType"="string", "required"=false, "description"="password / .{8,50}"}
     *     }
     * )
     *
     * @CheckParam(name="email", length="5-255", errCode="ERR_EMAIL_1", required="false")
     * @CheckParam(name="prefix", regex="/^[0-9]*$/", length="5", errCode="ERR_PREFTEL_1", required="false")
     * @CheckParam(name="phone", regex="/^[0-9]*$/", length="6-15", errCode="ERR_TEL_1", required="false")
     * @CheckParam(name="simId", regex="/^[0-9]*$/", length="13", errCode="ERR_SIMID_1", required="false")
     * @CheckParam(name="pwd", length="8-50", errCode="ERR_PWD_1")
     *
     * @Post("/customer/authenticate")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCustomerAuthenticateAction(Request $request)
    {
        $view = $this->customerAuthenticate($request);
        return $this->handleView($view);

    } // end of method postCustomerAuthenticateAction

    /**
     * @ApiDoc(
     *     description="Allows a customer to receive limit expiration limit",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="lng", "dataType"="string", "required"="true", "description"="lang locale / (fr_FR)|(en_EN)|(it_IT)|(pt_PT)|(de_DE)"},
     *         {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *         {"name"="limitType", "dataType"="string", "required"="true", "description"="Limit's Type / (global)|(produit)"},
     *         {"name"="productId", "dataType"="string", "required"="false", "description"="Product Limited - Mandatory if Limit Type is produit"}
     *     }
     * )
     *
     * @CheckParam(name="authToken", regex="/.{1,40}/", errCode="ERR_AUTHTOKEN_1")
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="limitType", regex="/(global)|(produit)/", errCode="ERR_LIMIT_TYPE_1")
     *
     * @Post("/customer/notify-limit-expiration")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postNotificationLimitExpirationAction(Request $request)
    {
        // check token and order ability
        $this->checkAuthToken($request);

        $rpcls = $this->getDoctrine()->getRepository('ApiFrontBundle:CustomerLimitSurvey');
        $rpp = $this->getDoctrine()->getRepository('ApiBackendBundle:Product');
        $em = $this->getDoctrine()->getManager();
        $product = null;

        $error = array();

        if ($request->get('limitType') == CustomerLimitSurvey::TYPE_PRODUIT && empty($request->get('productId'))) {
            $error[] = array('errCode' => 'ERR_PRODUCT_1');
        }

        if (!empty($request->get('productId'))) {
            $product = $rpp->findOneBy(array('id' => $request->get('productId')));
            if (empty($product)) {
                $error[] = array('errCode' => 'ERR_PRODUCT_2');
            }
        }
        if (sizeof($error) > 0) {
            throw new SendExceptionAsResponse(json_encode($error), Response::HTTP_BAD_REQUEST);
        } else {
            $_oCust = $this->getCustomer();

            if ($_oCust->getStatus() !== Customer::STATUS_ACTIVE) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_AUTHTOKEN_2'))), Response::HTTP_BAD_REQUEST);
            }

            if ($rpcls->findOneBy(array('customer' => $_oCust, 'limitType' => CustomerLimitSurvey::TYPE_GLOBAL)) && $request->get('limitType') == CustomerLimitSurvey::TYPE_GLOBAL) {
                $customerLimitSurvey = $rpcls->findOneBy(array('customer' => $_oCust, 'limitType' => CustomerLimitSurvey::TYPE_GLOBAL));
                $params = array('requestCreatedAt' => new \Datetime());
            } else if ($rpcls->findOneBy(array('customer' => $_oCust, 'limitType' => CustomerLimitSurvey::TYPE_PRODUIT, 'product' => $product)) && $request->get('limitType') == CustomerLimitSurvey::TYPE_PRODUIT) {
                $customerLimitSurvey = $rpcls->findOneBy(array('customer' => $_oCust, 'limitType' => CustomerLimitSurvey::TYPE_PRODUIT, 'product' => $product));
                $params = array('requestCreatedAt' => new \Datetime());
            } else {
                $customerLimitSurvey = new CustomerLimitSurvey();
                $params = array(
                    'customer' => $_oCust,
                    'limitType' => $request->get('limitType'),
                    'requestCreatedAt' => new \Datetime(),
                );
                if ($request->get('limitType') == CustomerLimitSurvey::TYPE_PRODUIT) {
                    $params['product'] = $product;
                }
            }
            $customerLimitSurvey->populate($params);
            $em->persist($customerLimitSurvey);
            $em->flush();
            $view = $this->view(array(
                'status' => 'OK',
                'detail' => array(),
            ));
            return $this->handleView($view);

        } // end else

    } // end of method postNotificationLimitExpirationAction

    /**
     * @ApiDoc(
     *     description="Reset password - request for",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="email", "dataType"="string", "required"="true", "description"="Customer's email / .{5,255}"}
     *     }
     * )
     *
     * @CheckParam(name="email", regex="/.{5,255}/", errCode="ERR_EMAIL_1")
     * @CheckParam(name="url4CallBack", regex="/.{5,255}/", errCode="ERR_CALLBACK_1")
     *
     * @Post("/customer/password/reset/init")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCustomerPasswordResetinitAction(Request $request)
    {
        // check mail to find customer
        $_oRpCustomer = $this->getDoctrine()->getRepository('ApiFrontBundle:Customer');
        $_strEmail = $request->get('email');
        $_strUrl4CallBack = $request->get('url4CallBack');
        $_oCustomer = $_oRpCustomer->findOneBy(array('email' => $_strEmail));
        if (is_null($_oCustomer) || !is_object($_oCustomer)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_EMAIL_3'))), Response::HTTP_BAD_REQUEST);
        }

        // Control account status to reset password
        // account must be activated or in validation mode to be able to reset his password
        if ($_oCustomer->getStatus() != Customer::STATUS_ACTIVE && $_oCustomer->getStatus() != Customer::STATUS_VALIDATION) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_EMAIL_3'))), Response::HTTP_BAD_REQUEST);
        }

        // create a validation token type password
        // create validation token, store new phone to validate
        $this->purgeValidationTokensForACustomer($_oCustomer);
        $_oCustValidationToken = new CustomerValidationToken($_oCustomer, CustomerValidationToken::TYPE_PWD);
        $_oCustValidationToken->setEmail($_oCustomer->getEmail());
        $this->getDoctrine()->getManager()->persist($_oCustValidationToken);
        $this->getDoctrine()->getManager()->flush();

        // Send mail for validation
        $this->sendValidationToken($_oCustomer, $_oCustValidationToken, $_strUrl4CallBack);
        $_aReturnValues = array('validationRequired' => 'mail');

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => array($_aReturnValues),
        ));
        return $this->handleView($view);

    } // end of method postCustomerPasswordResetinitAction

    /**
     * @ApiDoc(
     *     description="Reset password - record new password",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="email", "dataType"="string", "required"="true", "description"="Customer's email / .{5,255}"},
     *         {"name"="pwdToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *         {"name"="pwd", "dataType"="string", "required"=false, "description"="authentication token / .{8,50}"}
     *     }
     * )
     *
     * @CheckParam(name="email", regex="/.{5,255}/", errCode="ERR_EMAIL_1")
     * @CheckParam(name="pwdToken", regex="/.{1,40}/", errCode="ERR_PWDTOKEN_1")
     * @CheckParam(name="pwd", regex="/.{8,50}/", errCode="ERR_PWD_1")
     *
     * @Post("/customer/password/reset/save")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */

    public function postCustomerPasswordResetsaveAction(Request $request)
    {

        $_strEmail = $request->get('email');
        $_strToken = $request->get('pwdToken');
        $_strPwd = $request->get('pwd');

        // Check token and email to find ValidationToken
        $_oRpValidationToken = $this->getDoctrine()->getRepository('ApiFrontBundle:CustomerValidationToken');
        $_oToken = $_oRpValidationToken->findOneBy(array('email' => $_strEmail, 'token' => $_strToken));

        // Token not found -> error
        if (is_null($_oToken) || !is_object($_oToken)) {

            // Security addon
            // To avoid brut force, delete all password change tokens linked to this email
            // First look if customer if found

            $_oRpCustomer = $this->getDoctrine()->getRepository('ApiFrontBundle:Customer');
            $_oCustomer = $_oRpCustomer->findOneBy(array('email' => $_strEmail));
            if (!is_null($_oCustomer) && is_object($_oCustomer)) {
                $this->purgeValidationTokensForACustomer($_oCustomer);
                $this->getDoctrine()->getManager()->flush();
            }// end if (!is_null($_oCustomer) && is_object($_oCustomer)) {

            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PWDTOKEN_2'))), Response::HTTP_BAD_REQUEST);
        }

        // Case token already validated
        elseif (!is_null($_oToken->getValidatedAt())) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PWDTOKEN_3'))), Response::HTTP_BAD_REQUEST);
        }

        // Case pwd expired
        elseif (date_add($_oToken->getCreatedAt(), date_interval_create_from_date_string('1 hour'))->getTimestamp() < time()) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PWDTOKEN_4'))), Response::HTTP_BAD_REQUEST);
        }

        // Check customer object related is ok
        $_oCustomer = $_oToken->getCustomer();
        if (is_null($_oCustomer) || !is_object($_oCustomer) || $_oCustomer->getStatus() == Customer::STATUS_INACTIVE || $_oCustomer->getStatus() == Customer::STATUS_FRAUD) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PWDTOKEN_5'))), Response::HTTP_BAD_REQUEST);
        }

        // Everything is ok, set Password
        $_oCustomer->setPassword(password_hash($_strPwd, PASSWORD_DEFAULT));
        $_oToken->setIsValidated(true);
        $_oToken->setValidatedAt(new DateTime());
        $this->getDoctrine()->getManager()->persist($_oCustomer);
        $this->getDoctrine()->getManager()->persist($_oToken);
        $this->getDoctrine()->getManager()->flush();

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => array(),
        ));
        return $this->handleView($view);

    } // end of method postCustomerPasswordResetsaveAction

    /**
     * @ApiDoc(
     *     description="Get information details for a customer",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"}
     *     }
     * )
     *
     * @CheckParam(name="authToken", length="40", errCode="ERR_AUTHTOKEN_1")
     *
     * @Post("/customer/informations")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCustomerInformationsAction(Request $request)
    {
        // check token and order ability
        $this->checkAuthToken($request);
        $this->initLimitValues();

        $customer = $this->getCustomer();

        // Get Limit parameter for account validation by SMS or Mail
        $_oRpParam = $this->getDoctrine()->getRepository('ApiBackendBundle:Parameter');
        $_oLimitParameter = $_oRpParam->findValidationLimit();
        if (!is_object($_oLimitParameter) || !is_numeric($_oLimitParameter->getValue())) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        // Limit for validation attempts reached -> send info
        if (!is_null($customer->getValidationAttempts()) && ($customer->getValidationAttempts() + 1 > (int) $_oLimitParameter->getValue())) {
            $_strValidationStatus = "exceeded";
        } else {
            $_strValidationStatus = "ok";
        }

        if ($customer->getAcceptNewsletter() == true) {
            $_iNewsletter = 1;
        } else {
            $_iNewsletter = 0;
        }

        if ($customer->getOffer() == Customer::OFFER_STANDARD) {
            $strCountryCode = $customer->getCountry()->getISOCode();
        } else {
            $strCountryCode = "";
        }

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => array(
                "id" => $customer->getId(),
                "offer" => $customer->getOffer(),
                "name" => $customer->getFirstName(),
                "surname" => $customer->getLastName(),
                "email" => $customer->getEmail(),
                "prefix" => $customer->getPhoneCode(),
                "phone" => $customer->getPhoneNumber(),
                "simId" => $customer->getSimId(),
                "countryISOCode" => $strCountryCode,
                "photo" => $customer->getProfilePicture(),
                "creationDate" => $customer->getCreatedAt()->getTimestamp(),
                "lastConnection" => $customer->getLastLogin()->getTimestamp(),
                "status" => $customer->getStatus(),
                "lng" => $customer->getLang()->getLocal(),
                "validationType" => $customer->getValidationType(),
                "validationLimit" => $_strValidationStatus,
                "newsletter" => $_iNewsletter,
                "sendingCountries" => $customer->getSenderCountries(),
                "TopUpAllowedByNumberAndDay" => $this->getDayNumberAvailable(),
                "TopUpAllowedByNumberAndMonth" => $this->getMonthNumberAvailable(),
                "TopUpAllowedByAmountAndDay" => $this->getDayAmountAvailable(),
                "TopUpAllowedByAmountAndMonth" => $this->getMonthAmountAvailable(),
                "TopUpMaxAllowedByNumberAndDay" => $this->getDayNumberLimit(),
                "TopUpMaxAllowedByNumberAndMonth" => $this->getMonthNumberLimit(),
                "TopUpMaxAllowedByAmountAndDay" => $this->getDayAmountLimit(),
                "TopUpMaxAllowedByAmountAndMonth" => $this->getMonthAmountLimit(),
            ),
        ));
        return $this->handleView($view);

    } // end of method postCustomerInformationsAction

    /**
     *
     * @ApiDoc(
     *     description="Send again validation code for mail or phone",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"}
     *     }
     * )
     *
     * @CheckParam(name="authToken", length="40", errCode="ERR_AUTHTOKEN_1")
     *
     * @Post("/customer/validate/again")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCustomerSendAgainValidationAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();
        // check token
        $this->checkAuthToken($request);
        $c = $this->getCustomer();
        $this->createValidationCode($c, false, false);
        $em->flush();

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => array("validationType" => $c->getValidationType()),
        ));
        return $this->handleView($view);

    } // end of method postCustomerSendAgainValidationAction

    /**
     * @ApiDoc(
     *     description="Refer a friend",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *         {"name"="friendEmail", "dataType"="string", "required"="true", "description"="Customer's email / .{5,255}"},
     *         {"name"="friendName", "dataType"="string", "required"="true", "description"="Customer's name / .{1,100}"},
     *         {"name"="lng", "dataType"="string", "required"=true, "description"="lang locale / (fr_FR)|(en_EN)|(it_IT)|(pt_PT)|(de_DE)"}
     *     }
     * )
     *
     * @CheckParam(name="authToken", length="40", errCode="ERR_AUTHTOKEN_1")
     * @CheckParam(name="friendEmail", regex="/.{5,255}/", errCode="ERR_FRIEND_EMAIL_1")
     * @CheckParam(name="friendName", length="100", errCode="ERR_FRIEND_NAME_1")
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")

     *
     * @Post("/customer/refer-a-friend")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCustomerReferAFriendAction(Request $request)
    {

        // Load Parameters
        $_strFriendEmail = $request->get('friendEmail');
        $_strFriendName = $request->get('friendName');
        $_strLng = $request->get('lng');

        // check token
        $this->checkAuthToken($request);
        $_oCustomer = $this->getCustomer();

        $ms = $this->get('api_backend.send_mail_service');
        $ms->sendMailReferAFriend($_oCustomer, $_strFriendEmail, $_strFriendName, $_strLng);

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => array(),
        ));
        return $this->handleView($view);

    } // end of method postCustomerReferAFriendAction

    /**
     * @ApiDoc(
     *     description="Request a top up",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="senderEmail", "dataType"="string", "required"="true", "description"="Customer's email / .{5,255}"},
     *         {"name"="senderName", "dataType"="string", "required"="true", "description"="Customer's name / .{1,100}"},
     *         {"name"="receiverEmail", "dataType"="string", "required"="true", "description"="Customer's email / .{5,255}"},
     *         {"name"="receiverName", "dataType"="string", "required"="true", "description"="Customer's name / .{1,100}"},
     *         {"name"="lng", "dataType"="string", "required"=true, "description"="lang locale / (fr_FR)|(en_EN)|(it_IT)|(pt_PT)|(de_DE)"},
     *         {"name"="phone", "dataType"="string", "required"=true, "description"="phone number / [0-9]{6,15}"},
     *         {"name"="pwd", "dataType"="string", "required"=false, "description"="password / .{8,50}"}
     *     }
     * )
     *
     * @CheckParam(name="senderEmail", regex="/.{5,255}/", errCode="ERR_SENDER_EMAIL_1")
     * @CheckParam(name="senderName", length="100", errCode="ERR_SENDER_NAME_1")
     * @CheckParam(name="receiverEmail", regex="/.{5,255}/", errCode="ERR_RECEIVER_EMAIL_1")
     * @CheckParam(name="receiverName", length="100", errCode="ERR_RECEIVER_NAME_1")
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="emailTxt", length="255", errCode="ERR_EMAILTXT_1")
     * @CheckParam(name="prefix", regex="/^[0-9]*$/", length="5", errCode="ERR_PREFTEL_1")
     * @CheckParam(name="phone", regex="/^[0-9]*$/", length="6-15", errCode="ERR_TEL_1")

     *
     * @Post("/customer/request-a-topup")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCustomerRequestATopupAction(Request $request)
    {

        // Load Parameters
        $_strSenderEmail = $request->get('senderEmail');
        $_strSenderName = $request->get('senderName');
        $_strReceiverEmail = $request->get('receiverEmail');
        $_strReceiverName = $request->get('receiverName');
        $_strPrefix = $request->get('prefix');
        $_strPhone = $request->get('phone');
        $_strLng = $request->get('lng');
        $_strMessage = $request->get('emailTxt');

        $ms = $this->get('api_backend.send_mail_service');
        $ms->sendMailRequestATopUp($_strSenderEmail, $_strSenderName, $_strReceiverEmail, $_strReceiverName, $_strPrefix, $_strPhone, $_strLng, $_strMessage);

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => array(),
        ));
        return $this->handleView($view);

    } // end of method postCustomerRequestATopupAction

    /**
     *  This method is the mutualized one to create a validation code
     *  and to send it to the customer by email or SMS depending of the backend configuration
     *
     * @param Customer $c : customer object
     * @param $_bFirstCall Est-ce le premier appel ?
     * @param $_bRequiresValidationStatus Faut-il vérifier que le compte est au statut "Validation en cours" ?
     * @return bool
     * @throws SendExceptionAsResponse
     */
    private function createValidationCode(\ApiFrontBundle\Entity\Customer $c, $_bFirstCall, $_bRequiresValidationStatus)
    {

        $logger = $this->get('logger');
        $logger->info('Generate validation code for Customer ' . $c->getId());

        $em = $this->getDoctrine()->getManager();

        // If Parameter SMS ACCOUNT VALIDATION is set to On, validation code is sent by SMS, else by mail
        $_oRpParam = $em->getRepository('ApiBackendBundle:Parameter');
        $_oSMSParameter = $_oRpParam->findCustomerSMSValidation();
        if (!is_object($_oSMSParameter)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        $_oLimitParameter = $_oRpParam->findValidationLimit();
        if (!is_object($_oLimitParameter) || !is_numeric($_oLimitParameter->getValue())) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        // Account not waiting for validation -> error
        if ($_bRequiresValidationStatus == true && $c->getStatus() != Customer::STATUS_VALIDATION) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_VALIDATION_1'))), Response::HTTP_BAD_REQUEST);
        } // end if ($_bRequiresValidationStatus == true && $c->getStatus() != Customer::STATUS_VALIDATION) {
        // Cas où le compte est actif, -> changement de mail
        elseif($c->getStatus() === Customer::STATUS_ACTIVE){

            // Vérifier qu'on a bien un token de validation de mail en attente

            // if system is on validation mail -> look for a validation token mail based
            if ($_oSMSParameter->getValue() != "true") {
                $_strType = CustomerValidationToken::TYPE_MAIL;
            }
            // if system is on validation sms ->   look for a validation token sms based
            else {
                $_strType = CustomerValidationToken::TYPE_SMS;
            }

            $_oRpCVT = $em->getRepository('ApiFrontBundle:CustomerValidationToken');
            $_oValidationToken = $_oRpCVT->findOneBy(array('customer' => $c, 'type' => $_strType));
            if(is_null($_oValidationToken))
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_VALIDATION_1'))), Response::HTTP_BAD_REQUEST);

            // Récupérer le mail ou le tel à changer
            $_strEmailForChange = $_oValidationToken->getEmail();
            $_strPhoneCodeForChange = $_oValidationToken->getPhoneCode();
            $_strPhoneNumberForChange = $_oValidationToken->getPhoneNumber();

            // Flinguer l'ancien token
            $this->purgeValidationTokensForACustomer($c);

            // Générer le nouveau
            $_oCustValidationToken = new CustomerValidationToken($c, $_strType);
            $_oCustValidationToken->setEmail($_strEmailForChange);
            $_oCustValidationToken->setPhoneCode($_strPhoneCodeForChange);
            $_oCustValidationToken->setPhoneNumber($_strPhoneNumberForChange);
            $em->persist($_oCustValidationToken);

            // Envoyer la notif
            // Send token for validation
            $this->sendValidationToken($c, $_oCustValidationToken);

        } // end elseif($c->getStatus() === Customer::STATUS_ACTIVE){
        // Cas où le compte est STATUS_VALIDATION -> code de creation de compte
        else{
            // Limit for validation attempts reached -> error
            if (is_null($c->getValidationAttempts())) {
                $c->setValidationAttempts(0);
            }

            if ($c->getValidationAttempts() + 1 > (int) $_oLimitParameter->getValue()) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_VALIDATION_2'))), Response::HTTP_BAD_REQUEST);
            }

            $code = random_int(100000, 999999);
            $c->setValidationCode($code);
            $c->setValidationCreatedAt(new DateTime());

            // A la création du compte, on initialise le nombre de tentatives de validation du code à 0
            if($_bFirstCall===true)
                $c->setValidationAttempts(0);
            else
                $c->setValidationAttempts($c->getValidationAttempts() + 1);

            if ($_oSMSParameter->getValue() == 'true') {
                $c->setValidationType(Customer::VALIDATION_TYPE_SMS);
                $em->persist($c);
                $ss = $this->get('api_backend.send_sms_service');
                $ss->sendSMSAccount1($c);
            } // end if($_oSMSParameter->getValue()=='on')
            else {
                // check si on envoie une notif à l'app mobile
                $deviceId = $c->getDeviceId();
                if ($this->getUser()->getRoles()[0] === User::ROLE_FRONT_APP_TRAVELERS && $deviceId !== null && !empty($deviceId)) {
                    $esimMobileService = $this->get('api_backend.esim_mobile_service');
                    $esimMobileService->sendPushNotification($c, 'register');
                }
                $c->setValidationType(Customer::VALIDATION_TYPE_MAIL);
                $em->persist($c);
                $ms = $this->get('api_backend.send_mail_service');
                $ms->sendMailAccount1($c);
            } //end else if($_oSMSParameter->getValue()=='on')

        } //end else

        return true;

    } // end method createAndSendCustomerEmailToken

    private function sendValidationToken(\ApiFrontBundle\Entity\Customer $_oCust, CustomerValidationToken $_oCustValidationToken, $_strCallBack=false)
    {
        if ($_oCustValidationToken->getType() == CustomerValidationToken::TYPE_SMS) {
            $ss = $this->get('api_backend.send_sms_service');
            $ss->sendSMSAccount2($_oCust, $_oCustValidationToken);
        } // end  if($_strType==Customer::VALIDATION_TYPE_SMS)
        elseif ($_oCustValidationToken->getType() == CustomerValidationToken::TYPE_MAIL) {
            $ms = $this->get('api_backend.send_mail_service');
            $ms->sendMailAccount3($_oCust, $_oCustValidationToken);

            // check si on envoie une notif à l'app mobile
            $deviceId = $_oCust->getDeviceId();
            if ($this->getUser()->getRoles()[0] === User::ROLE_FRONT_APP_TRAVELERS && $deviceId !== null && !empty($deviceId)) {
                $esimMobileService = $this->get('api_backend.esim_mobile_service');
                $esimMobileService->sendPushNotification($_oCust, 'reset_mail', $_oCustValidationToken);
            }
        } //end elseif($_strType==Customer::VALIDATION_TYPE_MAIL)
        elseif ($_oCustValidationToken->getType() == CustomerValidationToken::TYPE_PWD) {
            $ms = $this->get('api_backend.send_mail_service');
            $ms->sendMailAccount4($_oCust, $_oCustValidationToken, $_strCallBack);

            // check si on envoie une notif à l'app mobile
            /* $deviceId = $_oCust->getDeviceId();
            if ($this->getUser()->getRoles()[0] === User::ROLE_FRONT_APP_TRAVELERS && $deviceId !== null && !empty($deviceId)) {
                $esimMobileService = $this->get('api_backend.esim_mobile_service');
                $esimMobileService->sendPushNotification($_oCust, 'reset_pwd', $_oCustValidationToken);
            } */
        } //end elseif($_strType==Customer::VALIDATION_TYPE_PWD)
    } // end private function sendValidationToken()

    private function purgeValidationTokensForACustomer(\ApiFrontBundle\Entity\Customer $c)
    {
        $em = $this->getDoctrine()->getManager();
        // delete previous mail tokens
        $rpcvt = $em->getRepository('ApiFrontBundle:CustomerValidationToken');
        $tokens = $rpcvt->findBy(array('customer' => $c));
        foreach ($tokens as $token) {
            $em->remove($token);
        }
    }

    private function purgeAuthTokensForACustomer(\ApiFrontBundle\Entity\Customer $c)
    {
        $em = $this->getDoctrine()->getManager();
        // delete previous mail tokens
        $rpcet = $em->getRepository('ApiFrontBundle:CustomerToken');
        $tokens = $rpcet->findBy(array('customer' => $c));
        foreach ($tokens as $token) {
            $em->remove($token);
        }
    }

    private function validateAccount(Customer $c)
    {
        if ($c->getStatus() == Customer::STATUS_VALIDATION && !is_null($c->getValidationValidatedAt())) {
            $logger = $this->get('logger');
            $c->setStatus(Customer::STATUS_ACTIVE);
            $logger->info('Customer ' . $c->getId() . ' status to activate');
            $ms = $this->get('api_backend.send_mail_service');
            $ms->sendMailAccount2($c);
        } // end if($c->getStatus()==Customer::STATUS_VALIDATION && !is_null($c->getEmailValidation()) && !is_null($c->getSmsValidation()))
    } // end function validateAccount($c)

    /**
     * @ApiDoc(
     *     description="Get information details for a traveler customer",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *         {"name"="lng", "dataType"="string", "required"=true, "description"="lang locale / (fr_FR)|(en_EN)|(it_IT)|(pt_PT)|(de_DE)"}
     *     }
     * )
     *
     * @CheckParam(name="authToken", length="40", errCode="ERR_AUTHTOKEN_1")
     * @CheckParam(name="lng", regex="/^[a-zA-Z_]*$/", length="5", errCode="ERR_LNG_1")
     *
     * @Post("/customer/travelerInformations")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCustomerTravelerInformationsAction(Request $request)
    {

        // check token and order ability
        $this->checkAuthToken($request);
        $customer = $this->getCustomer();
        $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
        $_oLng = $rpl->findOneBy(array("local" => $request->get('lng')));
        if (!is_object($_oLng)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }
        if ($customer->getOffer() != Customer::OFFER_TRAVELERS) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_AUTHTOKEN_2-1'))), Response::HTTP_BAD_REQUEST);
        }
        $detailInformationsResponse = $this->getTravelSimCardService()->getDetailedInformations($customer->getSimId(),$request->get('lng'));
        $detailInformationsResult = $detailInformationsResponse->result;
        $httpCode = $detailInformationsResponse->code;

        //connexion failed to connect
        if($httpCode===Response::HTTP_NOT_FOUND || $httpCode===Response::HTTP_INTERNAL_SERVER_ERROR){
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        if( $detailInformationsResult->status === "OK"){
            $detailInformationsResult->detail->id = $customer->getId();
        }
       return (new JsonResponse())->create($detailInformationsResult, $httpCode);
    } // end function


    private function checkPostCustomerConsentsParams(Request $request) {
        if (!is_bool($request->get("documentId")))
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_DOC_ID'))), Response::HTTP_BAD_REQUEST);

        if (!is_bool($request->get("portrait")))
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PORTRAIT'))), Response::HTTP_BAD_REQUEST);

        if (!$request->get("portrait") || !$request->get("documentId"))
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_CONSENTS'))), Response::HTTP_BAD_REQUEST);

        $_oRpTx = $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction');
        $_oTx = $_oRpTx->findOneById($request->get('orderId'));

        if (is_null($_oTx) || !is_object($_oTx)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_ID_2'))), Response::HTTP_BAD_REQUEST);
        }

        if ($_oTx->getCustomer() == null || $_oTx->getCustomer()->getId() != $this->getCustomer()->getId()) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_ID_3'))), Response::HTTP_BAD_REQUEST);
        }

        // Pour pouvoir appeler cette méthode il faut que la transaction soit dans un des états suivants :
        // KYC requis
        $rptxs = $this->getDoctrine()->getRepository('ApiBackendBundle:TransactionStatus');
        $_oStatusKYCNeeded = $rptxs->findKYCNeeded();

        if($_oTx->getStatus()!==$_oStatusKYCNeeded){
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_ID_4'))), Response::HTTP_BAD_REQUEST);
        } // end if($_oTx->getStatus()!==$_oStatusKYCNeeded || $_oTx->getStatus()!==$_oStatusKYCNeeded){

    } // end private function checkPostCustomerConsentsParams(Request $request)

    /**
     * @ApiDoc(
     *     description="Init KYC consents",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *         {"name"="documentId", "dataType"="boolean", "required"=true, "description"="DocumentId needed : true or false"},
     *         {"name"="portrait", "dataType"="boolean", "required"=true, "description"="Portait needed : true or false"},
     *         {"name"="expiration", "dataType"="integer", "required"=true, "description"="Expiration des documents "},
     *         {"name"="orderId", "dataType"="string", "required"=true, "description"="Référence de la transaction"}
     *     }
     * )
     *
     * @CheckParam(name="authToken", length="40", errCode="ERR_AUTHTOKEN_1")
     * @CheckParam(name="expiration", regex="/[0-9]/", errCode="ERR_EXP")
     * @CheckParam(name="orderId", regex="/(ORLTRVL)+([\a-zA-Z0-9]){1,}/", length="32", errCode="ERR_ORDER_ID_1")
     *
     * @Post("/customer/kyc/consents")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCustomerKYCConsentsAction(Request $request)
    {
        // check token
        $this->checkAuthToken($request, true);

        // check params value
        $this->checkPostCustomerConsentsParams($request);

        // load service & variables
        $iDemiaService = $this->get('api_backend.idemia_service');
        $em = $this->getDoctrine()->getManager();
        $expiration = $request->get("expiration");

        if ($this->getUser()->getRoles()[0] === User::ROLE_FRONT_APP_TRAVELERS)
            $consentMode = 'nativeSDK';
        elseif($this->getUser()->getRoles()[0] === User::ROLE_FRONT_SITE_TRAVELERS)
            $consentMode = 'webSDK';
        else
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ROLE'))), Response::HTTP_BAD_REQUEST);


        $oRpTx = $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction');
        $oTx = $oRpTx->findOneById($request->get('orderId'));

        // STEP 1 : Init KYC session (get identityId - Session available for 30 min)
        $infoSession = $iDemiaService->initKYCSession();
        if (is_null($infoSession))
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_INTERNAL_SERVER_ERROR);

        // STEP 2 : Sends user consents
        $infoUserConsents = $iDemiaService->sendUserConsents($infoSession['identityId'], $expiration);
        if (is_null($infoUserConsents))
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_INTERNAL_SERVER_ERROR);
        $infoUserConsents = json_decode($infoUserConsents, true, 512, JSON_THROW_ON_ERROR);

        // STEP 2-BIS : Need doc session creation to manage capture - Only webSDK
        if ($consentMode === "webSDK") {
            $docSessionInfo = $iDemiaService->createDocSession($infoSession['identityId']);
            if (is_null($docSessionInfo))
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_INTERNAL_SERVER_ERROR);
            $docSessionInfo = json_decode($docSessionInfo, true, 512, JSON_THROW_ON_ERROR);

        }

        // STEP 3 - Selfie capture session need to know the SDK used by the device
        $selfieSessionInfo = $iDemiaService->createSelfieCaptureSession($infoSession['identityId'], $consentMode);
        if (is_null($selfieSessionInfo))
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_INTERNAL_SERVER_ERROR);
        $selfieSessionInfo = json_decode($selfieSessionInfo, true, 512, JSON_THROW_ON_ERROR);

        // STEP 4 - Set KYC transaction status && save IdentityId
        $oTx->setKYCStatus("KYC_CONSENT_GIVEN");
        $oTx->setIdemiaSDKMode($consentMode);
        $oTx->setIdemiaIdentityId($infoSession['identityId']);
        $em->persist($oTx);
        $em->flush();

        $details = array(
            'identityId' => $infoSession['identityId'],
            'bioSessionId' => $selfieSessionInfo,
            'apiKey' => $consentMode === "webSDK" ? $this->getParameter('idemia_web_sdk_key') : $this->getParameter('idemia_ua_key'),
            'status' => 'KYC_CONSENT_GIVEN',
            'kycServerUrl' => $this->getParameter('idemia_api_url'),
        );

        if ($consentMode === "webSDK") {
            $details += array('docSessionId' => $docSessionInfo['sessionId']);
        }

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => $details
            )
        );
        return $this->handleView($view);
    } // end function

    /**
     * @ApiDoc(
     *     description="Get KYC status",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *         {"name"="orderId", "dataType"="string", "required"=true, "description"="Référence de la transaction"}
     *     }
     * )
     *
     * @CheckParam(name="authToken", length="40", errCode="ERR_AUTHTOKEN_1")
     * @CheckParam(name="orderId", regex="/(ORLTRVL)+([\a-zA-Z0-9]){1,}/", length="32", errCode="ERR_ORDER_ID_1")
     *
     * @Post("/customer/kyc/status")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCustomerKYCStatusAction(Request $request) {
        // check token
        $this->checkAuthToken($request, true);

        // check orderId
        $_oRpTx = $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction');
        $_oTx = $_oRpTx->findOneById($request->get('orderId'));
        if (is_null($_oTx) || !is_object($_oTx)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_ID_2'))), Response::HTTP_BAD_REQUEST);
        }

        if ($_oTx->getCustomer() == null || $_oTx->getCustomer()->getId() != $this->getCustomer()->getId()) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_ID_3'))), Response::HTTP_BAD_REQUEST);
        }

        // Pour pouvoir appeler cette méthode il faut que la transaction soit dans un des états suivants :
        // KYC requis + success ou failed si KYC est requis sur l'offre
        $rptxs = $this->getDoctrine()->getRepository('ApiBackendBundle:TransactionStatus');

        $_oStatusCaptureKo = $rptxs->findCaptureKO();
        $_oStatusClosed = $rptxs->findClosed();
        $_oStatusKYCNeeded = $rptxs->findKYCNeeded();
        $_oStatusSuccess = $rptxs->findSuccess();
        $_oStatusCaptureKO = $rptxs->findCaptureKO();
        $_oStatusKYCKo = $rptxs->findKYCKO();

        $_bIsAllowed = false;
        if($_oTx->getStatus()===$_oStatusKYCNeeded ||
            ($_oTx->getKYCStatus() !== null &&
                ($_oTx->getStatus()===$_oStatusKYCNeeded) ||
                ($_oTx->getStatus()===$_oStatusCaptureKo) ||
                ($_oTx->getStatus()===$_oStatusKYCKo) ||
                ($_oTx->getStatus()===$_oStatusSuccess) ||
                ($_oTx->getStatus()===$_oStatusCaptureKO) ||
                ($_oTx->getStatus()===$_oStatusClosed)
            )
        )
            $_bIsAllowed=true;

        if($_bIsAllowed===false){
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_ID_4'))), Response::HTTP_BAD_REQUEST);
        } // end if($_oTx->getStatus()!==$_oStatusKYCNeeded || $_oTx->getStatus()!==$_oStatusKYCNeeded){


        $details = array(
            'status' => $_oTx->getKYCStatus() ?? 'KYC_NOT_STARTED',
            'identityId' => $_oTx->getIdemiaIdentityId()
        );

        $mrz = $request->query->get("mrz");
        if (!is_null($mrz) && $mrz === 'true') {
            $identityDetails = $_oTx->getIdemiaIdentityDetails();
            $identityDetails = json_decode($identityDetails, true, 512, JSON_THROW_ON_ERROR);
            $idDocumentData = $identityDetails['idDocuments'][0]['idDocumentData'];
            $details += array('IdDocumentData' => $idDocumentData ?? "NOT_RECEIVED");
        }

        $view = $this->view(array(
                'status' => 'OK',
                'detail' => $details
            )
        );
        return $this->handleView($view);
    }
} // end of class
