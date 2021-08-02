<?php


namespace Sapient\AccessWorldpay\Model\Authorisation;

use Exception;

class ThreeDSecureChallenge extends \Magento\Framework\DataObject
{
    protected $updateWorldPayPayment;

    const CART_URL = 'checkout/cart';

    /**
     * Constructor
     * @param \Sapient\AccessWorldpay\Model\Request\PaymentServiceRequest $paymentservicerequest
     * @param \Sapient\AccessWorldpay\Logger\AccessWorldpayLogger $wplogger
     * @param \Sapient\AccessWorldpay\Model\Response\DirectResponse $directResponse
     * @param \Sapient\AccessWorldpay\Model\Payment\Service $paymentservice
     * @param \Sapient\AccessWorldpay\Model\Session $checkoutSession
     * @param \Sapient\AccessWorldpay\UrlInterface $urlBuilder
     * @param \Sapient\AccessWorldpay\Model\Order\Service $orderservice
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Sapient\AccessWorldpay\Model\Payment\UpdateAccessWorldpaymentFactory $updateWorldPayPayment
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Sapient\AccessWorldpay\Helper\Data $worldpayHelper
     */
    public function __construct(
        \Sapient\AccessWorldpay\Model\Request\PaymentServiceRequest $paymentservicerequest,
        \Sapient\AccessWorldpay\Logger\AccessWorldpayLogger $wplogger,
        \Sapient\AccessWorldpay\Model\Response\DirectResponse $directResponse,
        \Sapient\AccessWorldpay\Model\Payment\Service $paymentservice,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Sapient\AccessWorldpay\Model\Order\Service $orderservice,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Sapient\AccessWorldpay\Model\Payment\UpdateAccessWorldpaymentFactory $updateWorldPayPayment,
        \Magento\Customer\Model\Session $customerSession,
        //\Sapient\AccessWorldpay\Model\Token\WorldpayToken $worldpaytoken,
        \Sapient\AccessWorldpay\Helper\Data $worldpayHelper
    ) {
        $this->paymentservicerequest = $paymentservicerequest;
        $this->wplogger = $wplogger;
        $this->directResponse = $directResponse;
        $this->paymentservice = $paymentservice;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilders    = $urlBuilder;
        $this->orderservice = $orderservice;
        $this->_messageManager = $messageManager;
        $this->updateWorldPayPayment = $updateWorldPayPayment;
        $this->customerSession = $customerSession;
        //$this->worldpaytoken = $worldpaytoken;
        $this->worldpayHelper = $worldpayHelper;
    }
    public function continuePost3dSecure2AuthorizationProcess($directOrderParams, $threeDSecureParams)
    {
        
        // @setIs3DSRequest flag set to ensure whether it is 3DS request or not.
        // To add cookie for 3DS second request.
        $this->checkoutSession->setIs3DS2Request(true);
        $this->checkoutSession->unsDirectOrderParams();
        try {
            if (isset($threeDSecureParams)) {
                if ($threeDSecureParams['outcome'] !='authenticationFailed') {
                    $response = $this->paymentservicerequest->order3Ds2Secure(
                        $directOrderParams,
                        $threeDSecureParams
                    );
                    $this->response = $this->directResponse->setResponse($response);
                // @setIs3DSRequest flag is unset from checkout session.
                    $this->checkoutSession->setIs3DS2Request();
                    $orderIncrementId = current(explode('-', $directOrderParams['orderCode']));
                    $this->_order = $this->orderservice->getByIncrementId($orderIncrementId);
                    $paymentUpdate = $this->paymentservice->createPaymentUpdateFromWorldPayXml(
                        $this->response->getXml()
                    );
                    $paymentUpdate->apply($this->_order->getPayment(), $this->_order);
                    $this->_abortIfPaymentError($paymentUpdate);
                    $this->saveTokenData($this->_order->getPayment());
                    $this->customerSession->unsUsedSavedCard();
                } else {
                    $this->wplogger->info($this->worldpayHelper->getCreditCardSpecificException('CCAM19'));
                    $this->_messageManager->addErrorMessage(__(
                        $this->worldpayHelper->getCreditCardSpecificException('CCAM19')
                    ));
                    if ($this->checkoutSession->getInstantPurchaseOrder()) {
                        $this->wplogger->info("Authentication failed.");
                        $this->_messageManager->addErrorMessage(__(
                            $this->worldpayHelper->getCreditCardSpecificException('CCAM19')
                        ));
                        $redirectUrl = $this->checkoutSession->getInstantPurchaseRedirectUrl();
                        $this->checkoutSession->unsInstantPurchaseRedirectUrl();
                        $this->checkoutSession->unsInstantPurchaseOrder();
                        $this->checkoutSession->setWpResponseForwardUrl($redirectUrl);
                    } else {
                        $this->wplogger->info("Authentication failed.");
                        $this->_messageManager->addErrorMessage(__(
                            $this->worldpayHelper->getCreditCardSpecificException('CCAM19')
                        ));
                        $this->checkoutSession->setWpResponseForwardUrl(
                            $this->urlBuilders->getUrl(self::CART_URL, ['_secure' => true])
                        );
                    }
                }
            } else {
                $this->wplogger->info($this->worldpayHelper->getCreditCardSpecificException('CCAM6'));
                $this->_messageManager->addErrorMessage(__(
                    $this->worldpayHelper->getCreditCardSpecificException('CCAM6')
                ));
                if ($this->checkoutSession->getInstantPurchaseOrder()) {
                    $this->wplogger->info($this->worldpayHelper->getCreditCardSpecificException('CCAM6'));
                    $this->_messageManager->addErrorMessage(__(
                        $this->worldpayHelper->getCreditCardSpecificException('CCAM6')
                    ));
                    $redirectUrl = $this->checkoutSession->getInstantPurchaseRedirectUrl();
                    $this->checkoutSession->unsInstantPurchaseRedirectUrl();
                    $this->checkoutSession->unsInstantPurchaseOrder();
                    $this->checkoutSession->setWpResponseForwardUrl($redirectUrl);
                } else {
                    $this->wplogger->info($this->worldpayHelper->getCreditCardSpecificException('CCAM6'));
                    $this->_messageManager->addErrorMessage(__(
                        $this->worldpayHelper->getCreditCardSpecificException('CCAM6')
                    ));
                    $this->checkoutSession->setWpResponseForwardUrl(
                        $this->urlBuilders->getUrl(self::CART_URL, ['_secure' => true])
                    );
                }
            }
        } catch (Exception $e) {
            $this->checkoutSession->setInstantPurchaseMessage('');
            $this->wplogger->info($e->getMessage());
            if ($e->getMessage() === 'Asymmetric transaction rollback.') {
                $this->_messageManager->addError(__($this->worldpayHelper->
                        getMyAccountSpecificexception('MCAM3')));
            } else {
                $this->_messageManager->addError(__($e->getMessage()));
            }
            $this->checkoutSession->setWpResponseForwardUrl(
                $this->urlBuilders->getUrl(self::CART_URL, ['_secure' => true])
            );
            return;
        }
    }
    
    public function saveTokenData($payment)
    {
         $isInstantPurchaseOrder = $this->checkoutSession->getInstantPurchaseOrder();
        if ($this->customerSession->isLoggedIn() && !$isInstantPurchaseOrder) {
            if ($this->customerSession->getIsSavedCardRequested()) {
                $tokenDetailResponseToArray = $this->customerSession->getDetailedToken();
                $this->updateWorldPayPayment->create()->
                        saveVerifiedToken($tokenDetailResponseToArray, $payment);
            
                //unset the session variables
                $this->customerSession->unsIsSavedCardRequested();
                $this->customerSession->unsDetailedToken();
        
            } elseif (!empty($this->customerSession->getVerifiedDetailedToken())
                && $this->worldpayHelper->checkIfTokenExists($this->customerSession->
                        getVerifiedDetailedToken())) {
                $this->wplogger->info(" User already has this card saved....");
                $this->customerSession->unsVerifiedDetailedToken();
            } elseif (empty($this->customerSession->getUsedSavedCard())) {
                //delete verified token for registered user when save_card=0
                $verifiedToken = $this->customerSession->getVerifiedDetailedToken();
                $customerId = $this->customerSession->getCustomer()->getId();
                $this->customerSession->unsVerifiedDetailedToken();
                $this->wplogger->info(
                    " Inititating Delete Token for Registered customer with customerID="
                    .$customerId." ...."
                );
                $this->paymentservicerequest->getTokenDelete($verifiedToken);
            }
        } elseif (!empty($this->customerSession->getVerifiedDetailedToken())) {
            //delete verified token for guest user
            $verifiedToken = $this->customerSession->getVerifiedDetailedToken();
            $this->customerSession->unsVerifiedDetailedToken();
            $this->wplogger->info(" Inititating Delete Token for Guest User....");
               $this->paymentservicerequest->getTokenDelete($verifiedToken);
            
        }
    }
    
    /**
     * help to build url if payment is success
     */
    private function _handleAuthoriseSuccess()
    {
        $this->checkoutSession->setWpResponseForwardUrl(
            $this->urlBuilders->getUrl('checkout/onepage/success', ['_secure' => true])
        );
    }

    /**
     * it handles if payment is refused or cancelled
     * @param  Object $paymentUpdate
     */
    private function _abortIfPaymentError($paymentUpdate)
    {
        if ($paymentUpdate instanceof \Sapient\AccessWorldpay\Model\Payment\Update\Refused) {
            $this->_messageManager->addError(__($this->worldpayHelper->getCreditCardSpecificException('CCAM10')));
                $this->checkoutSession->setWpResponseForwardUrl(
                    $this->urlBuilders->getUrl(self::CART_URL, ['_secure' => true])
                );
                $this->checkoutSession->setInstantPurchaseMessage('');
        } elseif ($paymentUpdate instanceof \Sapient\AccessWorldpay\Model\Payment\Update\Cancelled) {
            $this->_messageManager->addError(__($this->worldpayHelper->getCreditCardSpecificException('CCAM10')));
                $this->checkoutSession->setWpResponseForwardUrl(
                    $this->urlBuilders->getUrl(self::CART_URL, ['_secure' => true])
                );
                $this->checkoutSession->setInstantPurchaseMessage('');
        } else {
            //$this->orderservice->redirectOrderSuccess();
            $this->orderservice->removeAuthorisedOrder();
            $this->_handleAuthoriseSuccess();
           // $this->_updateTokenData($this->response->getXml());
        }
    }
}