<?php

namespace ApiFrontBundle\Controller;

use ApiBackendBundle\Exception\SendExceptionAsResponse;
use ApiFrontBundle\Entity\Customer;
use ApiFrontBundle\Entity\CustomerToken;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerControllerBase extends ControllerBase
{
    public function customerAuthenticate(Request $request)
    {
        $rp = $this->getDoctrine()->getRepository('ApiFrontBundle:Customer');
        $email = $request->get('email');
        $phone_code = $request->get('prefix');
        $phone_number = $request->get('phone');
        $simId = $request->get('simId');

        $factory = $this->get('security.encoder_factory');
        if (!is_null($email) && !is_object(($customer = $rp->findOneBy(array('email' => $email))))) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_EMAIL_2'))), Response::HTTP_BAD_REQUEST);
        } else if (!is_null($phone_code) && !is_null($phone_number)
            && !is_object(($customer = $rp->findOneBy(array('phoneCode' => $phone_code, 'phoneNumber' => $phone_number))))) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_TEL_2'))), Response::HTTP_BAD_REQUEST);
        } else if (!is_null($simId)
            && !is_object(($customer = $rp->findOneBy(array('simId' => $simId))))) {
            throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_SIMID_2'))), Response::HTTP_BAD_REQUEST);
        } else if (isset($customer)) {
            $encoder = $factory->getEncoder($customer);
            if ($customer->getStatus() == Customer::STATUS_INACTIVE) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_DISABLED_1'))), Response::HTTP_BAD_REQUEST);
            } elseif ($customer->getStatus() == Customer::STATUS_FRAUD) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_DISABLED_1'))), Response::HTTP_BAD_REQUEST);
            } elseif (!$encoder->isPasswordValid($customer->getPassword(), $request->get('pwd'), '')) {
                throw new SendExceptionAsResponse(json_encode(array(array('errCode' => 'ERR_PWD_2'))), Response::HTTP_BAD_REQUEST);
            } else {

                $em = $this->getDoctrine()->getManager();
                $token = new CustomerToken($customer);
                $em->persist($token);

                $customer->setLastLogin(new DateTime());
                $em->persist($customer);

                $em->flush();

                $view = $this->view(array(
                    'status' => 'OK',
                    'detail' => array(
                        "authToken" => $token->getToken(),
                    ),
                ));
                return $view;
            }
        } else {
            throw new SendExceptionAsResponse(json_encode(array(
                array('errCode' => 'ERR_EMAIL_1'),
                array('errCode' => 'ERR_PREFTEL_1'),
                array('errCode' => 'ERR_TEL_1'),
            )), Response::HTTP_BAD_REQUEST);
        }
    } // end of method customerAuthenticate
}
