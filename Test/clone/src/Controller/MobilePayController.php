<?php

namespace ApiFrontBundle\Controller;

use ApiBackendBundle\Entity\Transaction;
use ApiBackendBundle\Exception\SendExceptionAsResponse;
use ApiBackendBundle\Service\TransactionService;
use ApiFrontBundle\Entity\Customer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use ApiFrontBundle\Annotation\CheckParam;
use FOS\RestBundle\Controller\Annotations\Post;

class MobilePayController extends ControllerBase
{
    protected function getTransactionService(): object|\TransactionService
    {
        return $this->get('api_backend.transaction_service');
    }

    /**
     * @ApiDoc(
     *     description="Get a mobile paiement status",
     *    statusCodes={
     *         200="Returned when successful",
     *         400="Bad parameters",
     *    },
     *    parameters={
     *      {"name"="authToken", "dataType"="string", "required"=true, "description"="authentication token / .{1,40}"},
     *      {"name"="lng", "dataType"="string", "required"=true, "description"="Exemple: en_EN"},
     *      {"name"="orderid", "dataType"="string", "required"=true, "description"="Transaction reference"},
     *    }
     * )
     *
     * @CheckParam(name="lng", regex="/[a-zA-Z_]{5}/", errCode="ERR_LNG_1")
     * @CheckParam(name="lng", regex="/(en_EN)|(pt_PT)|(es_ES)/", errCode="ERR_LNG_2")
     * @CheckParam(name="orderid", regex="/(ORLTRVL)+([\a-zA-Z0-9]){1,}/", errCode="ERR_ORDER_ID_1")
     *
     * @Post("/mobile-pay/status")
     *
     * @return Response
     * @throws SendExceptionAsResponse
     */
    public function postMobilePayStatusAction(Request $request)
    {
        $this->checkAuthToken($request, true);

        $_oRpTx = $this->getDoctrine()->getRepository('ApiBackendBundle:Transaction');
        $_oTx = $_oRpTx->findOneBy(["id" => $request->get('orderid')]);

        if (!is_object($_oTx)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_ORDER_ID_2'))), Response::HTTP_BAD_REQUEST);
        }

        $this->checkIfTransactionBelongToCustomer($_oTx);

        $_oRpLng = $this->getDoctrine()->getRepository('ApiFrontBundle:Lang');
        $_oLngRequest = $_oRpLng->findOneBy(["local" => $request->get('lng')]);
        if (!is_object($_oLngRequest)) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_LNG_1'))), Response::HTTP_BAD_REQUEST);
        }
        $_oLng = $_oTx->getLang();

        // get transaction into JSON RESPONSE...
        $_aTx = $this->buildTransactionStatusArray($_oTx, $request->get('lng'));

        $view = $this->view(array(
            'status' => 'OK',
            'detail' => $_aTx,
        ));
        return $this->handleView($view);

    }

} // end of class
