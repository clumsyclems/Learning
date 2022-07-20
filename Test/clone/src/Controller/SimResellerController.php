<?php

namespace ApiFrontBundle\Controller;

use ApiBackendBundle\Entity\SimResellerApplication;
use ApiBackendBundle\Entity\TrvlSimIdentification;
use ApiBackendBundle\Exception\SendExceptionAsResponse;
use ApiFrontBundle\Annotation\CheckParam;
use ApiFrontBundle\Entity\Lang;
use FOS\RestBundle\Controller\Annotations\Post;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SimResellerController extends ControllerBase
{


    /**
     * @ApiDoc(
     *    description="Get the list of the countries ",
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
     * @Post("/sim-reseller/countries")
     * @return Response
     */
    public function postSimResellerCountriesAction(Request $request)
    {

        $details = [];

        $rpl = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
        $lng = $rpl->findOneBy(array("local" => $request->get('lng')));
        if (!is_object($lng)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_BAD_REQUEST);
        }

        $rpcl = $this->getDoctrine()->getRepository('ApiBackendBundle:CountryLabel');
        $countries = $rpcl->findBy(array('lang' => $lng->getId()), array('wording' => 'ASC'));

        foreach ($countries as $country) {
            $detail['iso'] = $country->getCountry()->getISOCode();
            $detail['prefix'] = $country->getCountry()->getPhoneCode();
            $detail['name'] = $country->getWording();
            array_push($details, $detail);
        }

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => $details,
        ));
        return $this->handleView($view);
    } // end of method postCatalogCountriesAction


    /**
     * @ApiDoc(
     *     description="Create a new SIM traveler identification",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *     },
     *     requirements={
     *         {"name"="title", "dataType"="string", "required"="true", "description"="Customer's title / (Mr)|(Mrs)"},
     *         {"name"="email", "dataType"="string", "required"="true", "description"="Customer's email / .{5,255}"},
     *         {"name"="firstname", "dataType"="string", "required"="true", "description"="Customer's firstname / .{1,100}"},
     *         {"name"="lastname", "dataType"="string", "required"="true", "description"="Customer's lastname / .{1,100}"},
     *         {"name"="country", "dataType"="string", "required"=true, "description"="country Iso Code / .{2}"},
     *         {"name"="prefix", "dataType"="string", "required"=false, "description"="phone number / [0-9]{2,5}"},
     *         {"name"="phone", "dataType"="string", "required"=false, "description"="phone number / [0-9]{6,15}"},
     *         {"name"="businessname", "dataType"="string", "required"="true", "description"="Customer's firstname / .{1,100}"},
     *         {"name"="website", "dataType"="string", "required"="false", "description"="Customer's firstname / .{1,100}"},
     *         {"name"="message", "dataType"="string", "required"="true", "description"="Customer's firstname"},
     *     }
     * )
     *
     * @CheckParam(name="title", regex="/^[a-zA-Z_]*$/", length="2-3", errCode="ERR_TITLE_1")
     * @CheckParam(name="title", regex="/(Mr)|(Mrs)/", errCode="ERR_TITLE_2")
     * @CheckParam(name="firstname", length="1-100", errCode="ERR_FIRSTNAME_1")
     * @CheckParam(name="lastname", length="1-100", errCode="ERR_LASTNAME_1")
     * @CheckParam(name="email", length="5-255", errCode="ERR_EMAIL_1")
     * @CheckParam(name="country", regex="/[A-Z]{2}/", errCode="ERR_COUNTRY_1")
     * @CheckParam(name="prefix", regex="/^[0-9]*$/", length="2-5", errCode="ERR_PREFTEL_1", required="false")
     * @CheckParam(name="phone", regex="/^[0-9]*$/", length="6-15", errCode="ERR_TEL_1", required="false")
     * @CheckParam(name="businessname", length="1-100", errCode="ERR_BUSINESSNAME_1")
     * @CheckParam(name="website", length="1-100", errCode="ERR_WEBSITE_1", required="false")
     * @CheckParam(name="message", errCode="ERR_MESSAGE_1")
     *
     * @Post("/sim-reseller/application")
     * @param $request
     * @return Response
     */

    public function postSimResellerApplicationAction(Request $request)
    {

        $_oLogger = $this->get('logger');

        // Check parameters
        $error = array();

        // Contrôle de la civilité passée en entrée
        $rpt = $this->getDoctrine()->getRepository('ApiBackendBundle:SimResellerApplicationTitle');
        $_oTitle = $rpt->findOneBy(['name' => $request->get('title')]);
        if (!is_object($_oTitle)) {
            $error[] = array('errorCode' => 'ERR_TITLE_2');
        }

        // Contrôle du code ISO pays passé en entrée
        $rpc = $this->getDoctrine()->getRepository('ApiBackendBundle:Country');
        $oCountry = $rpc->findOneBy(['iSOCode' => $request->get('country')]);
        if (!is_object($oCountry)) {
            $error[] = array('errorCode' => 'ERR_COUNTRY_2');
        }

        // Contrôle de l'indicatif téléphonique passé en entrée (prefix)
        if(!empty($request->get('prefix'))){
            $oCountryPhoneCode = $rpc->findOneBy(['phoneCode' => $request->get('prefix')]);
            if (!is_object($oCountryPhoneCode)) {
                $error[] = array('errorCode' => 'ERR_PREFTEL_2');
            }
        } // end if(!empty($request->get('prefix')))

        if (sizeof($error) > 0) {
            throw new SendExceptionAsResponse(json_encode($error), Response::HTTP_BAD_REQUEST);
        } else {
            try {

                // Reste à créer la candidature, au statut New
                $_oRSimRslrStatus = $this->getDoctrine()->getRepository('ApiBackendBundle:SimResellerApplicationStatus');
                $_oNewStatus = $_oRSimRslrStatus->findNew();

                $_oNewSimRslrApp = new SimResellerApplication();
                $_oNewSimRslrApp->setTitle($_oTitle);
                $_oNewSimRslrApp->setStatus($_oNewStatus);
                $_oNewSimRslrApp->setFirstName($request->get('firstname'));
                $_oNewSimRslrApp->setLastName($request->get('lastname'));
                $_oNewSimRslrApp->setCountry($oCountry);
                $_oNewSimRslrApp->setEmail($request->get('email'));
                $_oNewSimRslrApp->setPhoneCode($request->get('prefix'));
                $_oNewSimRslrApp->setPhoneNumber($request->get('phone'));
                $_oNewSimRslrApp->setBusinessName($request->get('businessname'));
                $_oNewSimRslrApp->setWebsite($request->get('website'));
                $_oNewSimRslrApp->setMessage($request->get('message'));

                $em = $this->getDoctrine()->getManager();
                $em->persist($_oNewSimRslrApp);
                $em->flush();

                $view = $this->view(array(
                    'status' => 'OK',
                    'detail' => null,
                ));
                return $this->handleView($view);

            } // end try
            catch (\Exception $e) {
                $_oLogger->info("postSimResellerApplicationAction - " . $e->getMessage());
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_INTERNAL_SERVER_ERROR);
            } // end catch
        } // end else de if (sizeof($error) > 0)

    } // end of method postCustomerTravelerRegistration


} // end of class
