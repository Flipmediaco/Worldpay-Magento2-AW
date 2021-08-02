<?php
/**
 * @copyright 2017 Sapient
 */
namespace Sapient\AccessWorldpay\Model\JsonBuilder;

/**
 * Build xml for update token request
 */
class TokenExpiryUpdate
{

    /**
     * @var Mage_Customer_Model_Customer
     */
    private $customer;

    /**
     * @var Sapient_WorldPay_Model_Token
     */
    private $tokenModel;

    /**
     * @var string
     */
    protected $merchantCode;

    public function __construct(array $args = [])
    {
        if (isset($args['tokenModel']) && $args['tokenModel'] instanceof \Sapient\AccessWorldPay\Model\SavedToken) {
            $this->tokenModel = $args['tokenModel'];
        }

        if (isset($args['customer']) && $args['customer'] instanceof \Magento\Customer\Model\Customer) {
            $this->customer = $args['customer'];
        }

        if (isset($args['merchantCode'])) {
            $this->merchantCode = $args['merchantCode'];
        }
    }

    /**
     * Build xml for processing Request
     * @return SimpleXMLElement $xml
     */
    public function build()
    {
        $jsonData = [];
        $jsonData['month'] = (int)($this->tokenModel->getCardExpiryMonth());
        $jsonData['year'] = (int)($this->tokenModel->getCardExpiryYear());
        $val=json_encode($jsonData);
        return $val;
    }
}
