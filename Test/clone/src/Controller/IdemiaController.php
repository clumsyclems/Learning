<?php

namespace ApiFrontBundle\Controller;

use ApiBackendBundle\Entity\User;
use ApiBackendBundle\Exception\SendExceptionAsResponse;
use FOS\RestBundle\Controller\Annotations\Post;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IdemiaController extends CustomerControllerBase
{
    /**
     * @Post("idemia/callback")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCallbackIdemiaAction(Request $request)
    {
        $apiKeyProvided = $request->headers->get('apikey');

        // Check API KEY
        if (is_null($apiKeyProvided) || $this->getParameter('idemia_callback_api_key') !== $apiKeyProvided)
            return $this->handleView($this->view(array('status' => 'ERR', 'detail' => array(array('errCode' => 'ERR_API_KEY')))));

        // Load params && check variables
        $em = $this->getDoctrine()->getManager();
        $idemiaService = $this->get('api_backend.idemia_service');
        $evidence = $request->get("evidence");
        $identityId = $request->get('identityId');
        if (is_null($evidence) || is_null($identityId)) {
            return $this->handleView($this->view(array('status' => 'ERR', 'detail' => array(array('errCode' => 'ERR_PARAMS')))));
        }
        $oRpTx = $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction');
        $oTx = $oRpTx->findOneBy(["idemiaIdentityId" => $identityId]);
        if (!is_object($oTx))
            return $this->handleView($this->view(array('status' => 'ERR', 'detail' => array(array('errCode' => 'ERR_IDENTITY_ID')))));

        // STEP 1 : Check doc status (Passport or Portrait)
        $docInfo = $idemiaService->getDocumentStatus($identityId, $evidence['evidenceId']);
        if (is_null($docInfo))
            return $this->handleView($this->view(array('status' => 'ERR', 'detail' => array(array('errCode' => 'ERR_UNAVAILABLE')))));
        $docInfo = json_decode($docInfo, true, 512, JSON_THROW_ON_ERROR);

        // If status !== VERIFIED -> KYC KO
        if ($docInfo['status'] !== 'VERIFIED') {
            $oRpTxStatus = $this->getDoctrine()->getRepository('ApiBackendBundle:TransactionStatus');
            $oTxKycKoStatus = $oRpTxStatus->findKYCKO();
            $evidence['evidenceType'] === "PORTRAIT" ? $oTx->setKYCStatus("KYC_PORTRAIT_NOT_VERIFIED") : $oTx->setKYCStatus("KYC_DOC_ID_NOT_VERIFIED");
            $oTx->setStatus($oTxKycKoStatus);
            $em->persist($oTx);
            $em->flush();
            return $this->handleView($this->view(array('status' => 'ERR', 'detail' => array(array('errCode' => 'ERR_KYC_KO')))));
        }

        // STEP 2 : Retrieve doc proof (Only if evidenceType === Portrait -> This is the last step in the verification process)
        if ($evidence['evidenceType'] === 'PORTRAIT') {
            $oTx->setKYCStatus("KYC_PORTRAIT_VERIFIED");
            $em->persist($oTx);
            $em->flush();

            // Get indentityInfo
            $identityInfo = $idemiaService->getIdentityStatus($identityId);
            if (is_null($identityInfo))
                return $this->handleView($this->view(array('status' => 'ERR', 'detail' => array(array('errCode' => 'ERR_UNAVAILABLE')))));
            $identityInfo = json_decode($identityInfo, true, 512, JSON_THROW_ON_ERROR);
            $lvlOfAssurance = $identityInfo['globalStatus']['levelOfAssurance'];

            // Get document proof
            $userProof = $idemiaService->getDocumentProof($identityId);
            if (is_null($userProof)) {
                $oTx->setKYCStatus("KYC_ERR_PROOF");
                $em->persist($oTx);
                $em->flush();
                return $this->handleView($this->view(array('status' => 'ERR', 'detail' => array(array('errCode' => 'ERR_UNAVAILABLE')))));
            }

            // Store proof in ZIP file && send to SFTP server
            $identityDetails = $idemiaService->saveDocumentProofAndGetIdentityDetails($identityId, $lvlOfAssurance, $userProof);
            if (is_null($identityDetails))
                return $this->handleView($this->view(array('status' => 'ERR', 'detail' => array(array('errCode' => 'ERR_UNAVAILABLE')))));

            // Send push notification if nativeSDK (mobile)
            $_cust = $oTx->getCustomer();
            if (!empty($_cust) && $oTx->getIdemiaSDKMode() === "nativeSDK") {
                $esimMobileService = $this->get('api_backend.esim_mobile_service');
                $esimMobileService->sendPushNotification($_cust, 'kyc_ok');
            }

            // Update KYC STATUS
            $oTx->setIdemiaIdentityDetails($identityDetails);
            $oTx->setKYCStatus("KYC_OK");

            // Deliver esim
            $_oTxService = $this->get('api_backend.transaction_service');
            $_oTxService->deliver($oTx);

            $em->persist($oTx);
            $em->flush();
        } else {
            // Update KYC STATUS
            $oTx->setKYCStatus("KYC_DOC_ID_VERIFIED");
            $em->persist($oTx);
            $em->flush();
        }

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => array(),
        ));
        return $this->handleView($view);
    } // end of method postCallbackIdemiaAction

    /**
     * @Post("idemia/doc-capture-results/{identityId}")
     * @param $request
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postCallbackDocCaptureResultAction($identityId, Request $request) {
        $apiKeyProvided = $request->headers->get('x-header-name');

        // Check API KEY
        if (is_null($apiKeyProvided) || $this->getParameter('idemia_callback_api_key') !== $apiKeyProvided)
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_API_KEY'))), Response::HTTP_BAD_REQUEST);

        // Get transaction
        $oRpTx = $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction');
        $oTx = $oRpTx->findOneBy(["idemiaIdentityId" => $identityId]);
        if (!is_object($oTx))
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_IDENTITY_ID'))), Response::HTTP_BAD_REQUEST);

        // Get status of document
        $eventStatus = $request->get("event");
        if (is_null($eventStatus))
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PARAMS'))), Response::HTTP_BAD_REQUEST);

        // If status === "session_complete" -> we can retrieves the captured document information
        if ($eventStatus === "session_complete") {
            $idemiaService = $this->get('api_backend.idemia_service');
            $documentId = $request->get("documentId");
            $sessionId = $request->get("sessionId");
            $documentInfo = $idemiaService->getDocumentInformation($sessionId, $documentId);
            if (is_null($documentInfo))
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_INTERNAL_SERVER_ERROR);

            // Upload passport picture
            $imgFront = null;
            $documentInfo = json_decode($documentInfo, true, 512, JSON_THROW_ON_ERROR);
            foreach ($documentInfo['captures'] as $capture) {
                if ($capture['side']['name'] === "FRONT") {
                    $imgFront = $capture['imageStorage']['key'];
                }
            }
            $submitStatus = $idemiaService->submitIdentityPicture($identityId, $imgFront);
            if (is_null($submitStatus))
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_UNAVAILABLE'))), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => array(),
        ));
        return $this->handleView($view);
    }
} // end of class
