<?php

namespace ApiBackendBundle\Service;

use ApiBackendBundle\Entity\Country;
use ApiBackendBundle\Entity\MailTemplateLang;
use ApiBackendBundle\Entity\SimResellerApplication;
use ApiBackendBundle\Entity\SimResellerApplicationStatus;
use ApiBackendBundle\Entity\SimResellerApplicationTitle;
use ApiBackendBundle\Entity\System;
use ApiBackendBundle\Entity\Transaction;
use ApiFrontBundle\Entity\Customer;
use ApiFrontBundle\Entity\CustomerValidationToken;
use ApiFrontBundle\Entity\Lang;
use Symfony\Component\HttpKernel\Kernel;

class SendMailService
{
    protected $logFile;

    public function __construct(protected \Symfony\Component\Mailer\MailerInterface $mailer, \Monolog\Logger $logger, protected \Doctrine\ORM\EntityManager $em, protected $_mailFromEn, protected $_mailFromFr, $_mailFromNameEn, $_mailFromNameFr, protected Kernel  $kernel)
    {
        $this->logger = $logger;
        $this->_mailFromNameEn = $_mailFromNameEn;
        $this->_mailFromNameFr = $_mailFromNameFr;
    }

    public function sendMail($to, $subject, $body, $cc = null, $bcc = null, $name = null, $_strLocale = "en_EN")
    {
        $system = $this->em->getRepository('ApiBackendBundle:System')->findOneByCode('API-EMAIL');
        try {

            if ($_strLocale == 'fr_FR') {
                $_strMailFrom = $this->_mailFromFr;
                if (is_null($name) || empty($name)) {
                    $name = $this->_mailFromNameFr;
                }

            } else {
                $_strMailFrom = $this->_mailFromEn;
                if (is_null($name) || empty($name)) {
                    $name = $this->_mailFromNameEn;
                }

            }

            $message = (new \Symfony\Component\Mime\Email())->newInstance()
                ->setSubject($subject)
                ->setFrom($_strMailFrom, $name)
                ->setTo($to)
                ->setBody($body, 'text/html');
            if ($cc != null) {
                $message->setCc($cc);
            }

            if ($bcc != null) {
                $message->setBcc($bcc);
            }

            $this->mailer->send($message);
            $this->logger->info(" Email sent to : " . $to . ", cc : " . $cc . "\n");
            $system->setStatus(System::STATUS_WORKING);
            $this->em->persist($system);
            $this->em->flush();
            return true;
        } catch (\Exception $e) {
            $this->logger->error("Failure sending email : '" . $e . "'\n");
            $system->setStatus(System::STATUS_INCIDENT);
            $this->em->persist($system);
            $this->em->flush();
            return false;
        }
    }

    //($_strEmail, $_strSubject, $_strMsg, $_aAttachments, null, 'jfv@osaxis.fr', $_oCustomer->getLang()->getLocal());
    public function sendMailWithAttachment($to, $subject, $body, $attachments, $cc = null, $bcc = null, $name = null, $_strLocale = "en_EN")
    {
        $system = $this->em->getRepository('ApiBackendBundle:System')->findOneByCode('API-EMAIL');
        try {

            if ($_strLocale == 'fr_FR') {
                $_strMailFrom = $this->_mailFromFr;
                if (is_null($name) || empty($name)) {
                    $name = $this->_mailFromNameFr;
                }

            } else {
                $_strMailFrom = $this->_mailFromEn;
                if (is_null($name) || empty($name)) {
                    $name = $this->_mailFromNameEn;
                }

            }

            $message = (new \Symfony\Component\Mime\Email())->newInstance()
                ->setSubject($subject)
                ->setFrom($_strMailFrom, $name)
                ->setTo($to)
                ->setBody($body, 'text/html');

            foreach ($attachments as $attachment){
                //$attachment = \Swift_Attachment::fromPath($attachment);
                //$message->attach($attachment);
                $message->attach(\Swift_Attachment::fromPath($attachment['path'])->setFilename($attachment['name']));
            }

            if ($cc != null) {
                $message->setCc($cc);
            }

            if ($bcc != null) {
                $message->setBcc($bcc);
            }

            $this->mailer->send($message);
            $this->logger->info(" Email sent to : " . $to . ", cc : " . $cc . "\n");
            $system->setStatus(System::STATUS_WORKING);
            $this->em->persist($system);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->error("Failure sending email : '" . $e . "'\n");
            $system->setStatus(System::STATUS_INCIDENT);
            $this->em->persist($system);
            $this->em->flush();
            throw $e;
        }
    }

    public function sendMailAccount1(Customer $_oCustomer)
    {
        $this->logger->info("Mail validation required, try to send a Mail - EMAIL_ACCOUNT_1");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForAccount1($_oCustomer->getLang()->getLocal());
        $this->fillAndSendMail($_oCustomer, $_oTemplate);
    } // end public function sendSMSAccount1(Customer $_oCustomer)

    public function sendMailAccount2(Customer $_oCustomer)
    {
        $this->logger->info("Account activation, try to send a Mail - EMAIL_ACCOUNT_2");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForAccount2($_oCustomer->getLang()->getLocal());
        $this->fillAndSendMail($_oCustomer, $_oTemplate);
    } // end public function sendSMSAccount1(Customer $_oCustomer)

    public function sendMailAccount3(Customer $_oCustomer, CustomerValidationToken $_oCustValidationToken)
    {
        $this->logger->info("Email update need to confirm, try to send a Mail - EMAIL_ACCOUNT_3");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForAccount3($_oCustomer->getLang()->getLocal());
        $this->fillAndSendMail($_oCustomer, $_oTemplate, $_oCustValidationToken);
    } // end public function sendSMSAccount1(Customer $_oCustomer)

    public function sendMailAccount4(Customer $_oCustomer, CustomerValidationToken $_oCustValidationToken, $_strCallBackUrl)
    {
        $this->logger->info("Password forgotten, try to send a Mail - EMAIL_ACCOUNT_4");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForAccount4($_oCustomer->getLang()->getLocal());
        $this->fillAndSendMail($_oCustomer, $_oTemplate, $_oCustValidationToken, $_strCallBackUrl);
    } // end public function sendSMSAccount1(Customer $_oCustomer)

    public function sendMailOrder1(Customer $_oCustomer, Transaction $_oTx)
    {
        $this->logger->info("Order confirmation - EMAIL_ORDER_1");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForOrder1($_oCustomer->getLang()->getLocal());
        $this->fillAndSendMail($_oCustomer, $_oTemplate, null, null, $_oTx);
    } // end public function sendSMSAccount1(Customer $_oCustomer)

    public function sendMailEsimConfirm(Customer $_oCustomer, Transaction $_oTx, $nsce, $msisdn, $offerTxtToInclude, $_pdfPath)
    {
        $this->logger->info("Order confirmation - EMAIL_ORDER_ESIM");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForESIMOrderConfirm($_oCustomer->getLang()->getLocal());

        $_strEmail = $_oCustomer->getEmail();

        $_strSubject = $this->fillMessageWithCustomer($_oCustomer, $_oTemplate->getSubject(), null);
        $_strMsg = $this->fillMessageWithCustomer($_oCustomer, $_oTemplate->getBody(), null);
        $_strMsg = $this->fillMessageWithTransaction($_strMsg, $_oCustomer->getLang(), $_oTx);

        $_strMsg = str_replace('##esim.nsce##', $nsce, $_strMsg);
        $_strMsg = str_replace('##esim.msisdn##', $msisdn, $_strMsg);
        $_strMsg = str_replace('##esim.emailTxt##', $offerTxtToInclude, $_strMsg);

        $basDirectory = $this->kernel->getProjectDir().'/var/mail_attachments/';
        $_aAttachments = array();
        $_aAttachments[] = array('path' => $_pdfPath, 'name' => 'Welcome_Pack_and_QR_code.pdf');
        $_aAttachments[] = array('path' => $basDirectory.'210506_OF_fiche_tarifaire_EN_DEF.pdf', 'name' => 'Orange_France_price_sheet.pdf');
        $_aAttachments[] = array('path' => $basDirectory.'210510_Guide_installation_Android_GB_DEF.pdf', 'name' => 'Android_installation_guide.pdf');
        $_aAttachments[] = array('path' => $basDirectory.'210510_Guide_installation_Iphone_GB_DEF.pdf', 'name' => 'Iphone_installation_guide.pdf');

        $this->sendMailWithAttachment($_strEmail, $_strSubject, $_strMsg, $_aAttachments, null, 'jfv@osaxis.fr', null, $_oCustomer->getLang()->getLocal());

    } // end public function sendMailEsimConfirm(Customer $_oCustomer, Transaction $_oTx)

    public function sendMailOrderTravelers1(Customer $_oCustomer, Transaction $_oTx)
    {
        $this->logger->info("Order confirmation - EMAIL_TRAVELERS_ORDER_1");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForTravelersOrder1($_oCustomer->getLang()->getLocal());
        $this->fillAndSendMail($_oCustomer, $_oTemplate, null, null, $_oTx);
    } // end public function sendSMSAccount1(Customer $_oCustomer)

    public function sendMailOrderTravelers2(Customer $_oCustomer, Transaction $_oTx)
    {
        $this->logger->info("Order confirmation - EMAIL_TRAVELERS_ORDER_2");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForTravelersOrder2($_oCustomer->getLang()->getLocal());
        $this->fillAndSendMail($_oCustomer, $_oTemplate, null, null, $_oTx);
    } // end public function sendSMSAccount1(Customer $_oCustomer)

    public function sendMailOrder2(Customer $_oCustomer, Transaction $_oTx)
    {
        $this->logger->info("Order cancellation, try to send a Mail - EMAIL_ORDER_2");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForOrder2($_oCustomer->getLang()->getLocal());
        $this->fillAndSendMail($_oCustomer, $_oTemplate, null, null, $_oTx);
    } // end public function sendSMSAccount1(Customer $_oCustomer)

    public function sendMailEsimPaymentFailure(Customer $_oCustomer, Transaction $_oTx)
    {
        $this->logger->info("Order cancellation, try to send a Mail - EMAIL_ESIM_PAYMENT_FAILURE");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForEsimPaymentFailure($_oCustomer->getLang()->getLocal());
        $this->fillAndSendMail($_oCustomer, $_oTemplate, null, null, $_oTx);
    } // end public function sendMailEsimPaymentFailure(Customer $_oCustomer)

    public function sendMailOrder3(Customer $_oCustomer, Transaction $_oTx)
    {
        $this->logger->info("Order callback, try to send a Mail - EMAIL_ORDER_3");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForOrder3($_oCustomer->getLang()->getLocal());
        $this->fillAndSendMail($_oCustomer, $_oTemplate, null, null, $_oTx);
    } // end public function sendSMSAccount1(Customer $_oCustomer)

    public function sendMailOrder4(Customer $_oCustomer, Transaction $_oTx)
    {
        $_strReceiverMail = $_oTx->getReceiverEmail();
        $_strEmailTxt = $_oTx->getEmailTxtForReceiver();
        $_strTemplate = $_oTx->getTmplTxtForReceiver();

        // send mail to receiver
        $this->logger->info("Top up confirmation for receiver - EMAIL_ORDER_4");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForOrder4($_oCustomer->getLang()->getLocal(), $_strTemplate);
        $_strSubject = $this->fillMessageWithCustomer($_oCustomer, $_oTemplate->getSubject());
        $_strMsg = $this->fillMessageWithCustomer($_oCustomer, $_oTemplate->getBody());
        $_strMsg = str_replace('##message##', $_strEmailTxt, $_strMsg);
        $this->sendMail($_strReceiverMail, $_strSubject, $_strMsg, null, 'jfv@osaxis.fr', null, $_oCustomer->getLang()->getLocal());
    } // end public function sendSMSAccount1(Customer $_oCustomer)

    public function sendMailOrder5(Customer $_oCustomer, Transaction $_oTx = null)
    {
        $this->logger->info("Order callback, try to send a Mail - EMAIL_ORDER_5");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForOrder5($_oCustomer->getLang()->getLocal());
        $this->fillAndSendMail($_oCustomer, $_oTemplate, null, null, $_oTx);
    }

    public function sendMailOrder6(Customer $_oCustomer)
    {
        $this->logger->info("Order callback, try to send a Mail - EMAIL_ORDER_6");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForOrder6($_oCustomer->getLang()->getLocal());
        $this->fillAndSendMail($_oCustomer, $_oTemplate, null, null, null);
    }

    public function sendMailReferAFriend(Customer $_oCustomer, $_strFriendEmail, $_strFriendName, $_strLng)
    {
        // Two mails to send. One for the sender, one for the receiver

        // First send mail to friend
        $this->logger->info("Refer a friend, mail for friend, try to send a Mail - EMAIL_REFER_1");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForRefer1($_strLng);
        $_strSubject = $this->fillMessageWithCustomer($_oCustomer, $_oTemplate->getSubject());
        $_strMsg = $this->fillMessageWithCustomer($_oCustomer, $_oTemplate->getBody());
        $_strMsg = str_replace('##friend.name##', $_strFriendName, $_strMsg);
        $this->sendMail($_strFriendEmail, $_strSubject, $_strMsg, null, 'jfv@osaxis.fr', $_oCustomer->getFirstName() . ' ' . $_oCustomer->getLastName(), $_strLng);

        // Second, send confirmation mail to customer
        $this->logger->info("Refer a friend, mail for customer, try to send a Mail - EMAIL_REFER_2");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForRefer2($_oCustomer->getLang()->getLocal());
        $_strSubject = $this->fillMessageWithCustomer($_oCustomer, $_oTemplate->getSubject());
        $_strMsg = $this->fillMessageWithCustomer($_oCustomer, $_oTemplate->getBody());
        $_strMsg = str_replace('##friend.name##', $_strFriendName, $_strMsg);
        $this->sendMail($_oCustomer->getEmail(), $_strSubject, $_strMsg, null, 'jfv@osaxis.fr', null, $_strLng);

    } // end public function sendSMSAccount1(Customer $_oCustomer)

    public function sendMailRequestATopUp($_strSenderEmail, $_strSenderName, $_strReceiverEmail, $_strReceiverName, $_strPrefix, $_strPhone, $_strLng, $_strMessage)
    {
        // Two mails to send. One for the sender, one for the receiver

        // First send mail to sender
        $this->logger->info("Refer a friend, mail for sender, try to send a Mail - EMAIL_REQUEST_TOPUP_1");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForRequestTopUp1($_strLng);
        $_strSubject = $_oTemplate->getSubject();
        $_strMsg = $_oTemplate->getBody();
        $_strMsg = str_replace('##sender.name##', $_strSenderName, $_strMsg);
        $_strMsg = str_replace('##receiver.name##', $_strReceiverName, $_strMsg);
        $_strMsg = str_replace('##receiver.phone##', $_strPrefix . $_strPhone, $_strMsg);
        $_strMsg = str_replace('##message##', $_strMessage, $_strMsg);
        $this->sendMail($_strSenderEmail, $_strSubject, $_strMsg, null, 'jfv@osaxis.fr', null, $_strLng);

        // Second, send confirmation mail to receiver
        $this->logger->info("Refer a friend, mail for requester, try to send a Mail - EMAIL_REQUEST_TOPUP_2");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForRequestTopUp2($_strLng);
        $_strSubject = $_oTemplate->getSubject();
        $_strMsg = $_oTemplate->getBody();
        $_strMsg = str_replace('##sender.name##', $_strSenderName, $_strMsg);
        $_strMsg = str_replace('##receiver.name##', $_strReceiverName, $_strMsg);
        $_strMsg = str_replace('##receiver.phone##', $_strPrefix . $_strPhone, $_strMsg);
        $this->sendMail($_strReceiverEmail, $_strSubject, $_strMsg, null, 'jfv@osaxis.fr', null, $_strLng);

    } // end public function sendSMSAccount1(Customer $_oCustomer)

    public function sendMailLimitExpired1(Customer $_oCustomer)
    {
        $this->logger->info("Transaction limitation expired - EMAIL_LIMIT_EXPIRED_1");
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForLimitExpired1($_oCustomer->getLang()->getLocal());
        $this->fillAndSendMail($_oCustomer, $_oTemplate);
    }

    private function fillAndSendMail(Customer $_oCustomer, MailTemplateLang $_oTemplate, CustomerValidationToken $_oCustValidationToken = null, $_strCallBackUrl = null, $_oTx = null)
    {
        if (is_null($_oCustValidationToken)) {
            $_strEmail = $_oCustomer->getEmail();
        } else {
            $_strEmail = $_oCustValidationToken->getEmail();
        }

        $_strSubject = $this->fillMessageWithCustomer($_oCustomer, $_oTemplate->getSubject(), $_oCustValidationToken);
        $_strMsg = $this->fillMessageWithCustomer($_oCustomer, $_oTemplate->getBody(), $_oCustValidationToken);
        $_strMsg = $this->fillMessageWithToken($_strMsg, $_strCallBackUrl, $_oCustValidationToken);
        $_strMsg = $this->fillMessageWithTransaction($_strMsg, $_oCustomer->getLang(), $_oTx);
        $this->sendMail($_strEmail, $_strSubject, $_strMsg, null, 'jfv@osaxis.fr', null, $_oCustomer->getLang()->getLocal());
    }

    private function fillMessageWithCustomer(Customer $_oCustomer, $_strMsg, CustomerValidationToken $_oCustValidationToken = null)
    {
        if (is_null($_oCustValidationToken)) {
            $_strCode = $_oCustomer->getValidationCode();
        } else {
            $_strCode = $_oCustValidationToken->getToken();
        }

        $_strMsg = str_replace('##validation.code##', $_strCode, $_strMsg);
        $_strMsg = str_replace('##customer.firstname##', $_oCustomer->getFirstName(), $_strMsg);
        $_strMsg = str_replace('##customer.lastname##', $_oCustomer->getLastName(), $_strMsg);
        $_strMsg = str_replace('##customer.email##', $_oCustomer->getEmail(), $_strMsg);
        $_strMsg = str_replace('##customer.phone##', $_oCustomer->getPhoneCode() . ' ' . $_oCustomer->getPhoneNumber(), $_strMsg);
        return $_strMsg;
    } // end private function fillMessageWithCustomer(Customer $_oCustomer)

    private function fillMessageWithToken($_strMsg, $_strCallBackUrl, CustomerValidationToken $_oCustValidationToken = null)
    {
        if (!is_null($_oCustValidationToken)) {
            $_strMsg = str_replace('##callback.link##', $_strCallBackUrl, $_strMsg);
            $_strMsg = str_replace('##callback.email##', $_oCustValidationToken->getEmail(), $_strMsg);
            $_strMsg = str_replace('##callback.token##', $_oCustValidationToken->getToken(), $_strMsg);
        }
        return $_strMsg;
    } // end private function fillMessageWithCustomer(Customer $_oCustomer)

    private function fillMessageWithTransaction($_strMsg, Lang $_lang, Transaction $_oTx = null)
    {
        if (!is_null($_oTx)) {
            $_strMsg = str_replace('##order.currency##', "EUR", $_strMsg);
            $_strMsg = str_replace('##order.id##', $_oTx->getId(), $_strMsg);
            $_strMsg = str_replace('##order.amount##', $_oTx->getTotal(), $_strMsg);
            $_strMsg = str_replace('##order.localAmount##', $_oTx->getPromoOrangeValue() ?: $_oTx->getTopUpLocalInfoAmount(), $_strMsg);
            $_strMsg = str_replace('##order.localCurrency##', $_oTx->getTopUpLocalInfoCurrency(), $_strMsg);
            $_strMsg = str_replace('##order.localInfoValue##', $_oTx->getTopUpLocalInfoValue(), $_strMsg);
            $_strProductRequested = $_oTx->getTopUpProductRequested();
            $_strMsg = str_replace('##order.productRequested##', $_strProductRequested, $_strMsg);
            $_strMsg = str_replace('##order.amount##', $_oTx->getTotal(), $_strMsg);

            if ($_lang->getLocal() == 'fr_FR') {
                $_strMsg = str_replace('##order.date##', gmdate('d/m/Y H:i:s', $_oTx->getUpdatedAt()->getTimestamp()) . ' (heure UTC)', $_strMsg);
            } else {
                $_strMsg = str_replace('##order.date##', gmdate('m/d/Y H:i:s', $_oTx->getUpdatedAt()->getTimestamp()) . ' (UTC Time)', $_strMsg);
            }

            if ($_oTx->getService() == Transaction::SERVICE_TRAVELSIMCARD) {
                $_strMsg = str_replace('##order.receiver##', $_oTx->getSimId(), $_strMsg);
            } else {
                $_strMsg = str_replace('##order.receiver##', $_oTx->getReceiverPhoneCode() . " " . $_oTx->getReceiverPhoneNumber(), $_strMsg);
            }

        }
        return $_strMsg;
    } // end private function fillMessageWithCustomer(Customer $_oCustomer)


    public function sendMailLoginAuthCode($to, $authenticationCode)
    {
        $this->logger->info("Login 2nd step authentication code - EMAIL_ACCOUNT_5");
        /** @var MailTemplateLang $_oTemplate */
        $_oTemplate = $this->em->getRepository('ApiBackendBundle:MailTemplateLang')->findTemplateForLoginAuthCode();
        $_strMsg = str_replace('##validation.code##', $authenticationCode, $_oTemplate->getBody());
        $this->sendMail($to, $_oTemplate->getSubject(), $_strMsg);
    } // end public function sendSMSAccount1(Customer $_oCustomer)


    /**
     * @param $to
     * @param SimResellerApplication $simResellerApplication
     * @param string $countryFr
     * @return bool
     */
    public function sendMailNewReseller($to, $simResellerApplication, $countryFr){
        $this->logger->info("Orange Link Travelers - Souscription revendeur");
        $subject = "Orange Link Travelers - Souscription revendeur";
        //creation du template
        $intro = "Une nouvelle demande pour devenir revendeur a été saisie sur le site Travelers : "."<br>";
        $_srtMsg =
            "<label style='font-weight: bold; margin-top: 10px; display: inline-block; width: 100px;'>"."Business name  " ."</label><span>". $simResellerApplication->getBusinessName() ."</span>"."<br>".
            "<label style='font-weight: bold; margin-top: 10px; display: inline-block; width: 100px;'>"."Status "."</label><span>"."New"."</span>"."<br>".
            "<label style='font-weight: bold; margin-top: 10px; display: inline-block; width: 100px;'>"."Title  " ."</label><span>". $simResellerApplication->getTitle()->getName()  ."</span>"."<br>".
            "<label style='font-weight: bold; margin-top: 10px; display: inline-block; width: 100px;'>"."Last name  "."</label><span>" . $simResellerApplication->getLastName() ."</span>"."<br>".
            "<label style='font-weight: bold; margin-top: 10px; display: inline-block; width: 100px;'>"."First name  "."</label><span>" . $simResellerApplication->getFirstName() ."</span>"."<br>".
            "<label style='font-weight: bold; margin-top: 10px; display: inline-block; width: 100px;'>". "Email  " ."</label><span>". $simResellerApplication->getEmail()  ."</span>"."<br>".
            "<label style='font-weight: bold; margin-top: 10px; display: inline-block; width: 100px;'>". "Phone code "."</label><span>" . $simResellerApplication->getPhoneCode() ."</span>"."<br>".
            "<label style='font-weight: bold; margin-top: 10px; display: inline-block; width: 100px;'>"."Phone number  "."</label><span>" .$simResellerApplication->getPhoneNumber() ."</span>"."<br>".
            "<label style='font-weight: bold; margin-top: 10px; display: inline-block; width: 100px;'>". "Website  " ."</label><span>". $simResellerApplication->getWebsite()  ."</span>"."<br>".
            "<label style='font-weight: bold; margin-top: 10px; display: inline-block; width: 100px;'>"."Country  " ."</label><span>". $countryFr."</span>"."<br>".
            "<label style='font-weight: bold; margin-top: 10px; display: inline-block; width: 100px;'>"."Message  " ."</label><span>". $simResellerApplication->getMessage()."</span>"."<br>";
            return $this->sendMail($to, $subject, $intro.$_srtMsg);
    }

} // end class
