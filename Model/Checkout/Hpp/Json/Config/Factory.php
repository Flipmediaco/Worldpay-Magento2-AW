<?php
/**
 * @copyright 2020 Sapient
 */
namespace Sapient\AccessWorldpay\Model\Checkout\Hpp\Json\Config;

use Sapient\AccessWorldpay\Model\Checkout\Hpp\Json\Config as Config;
use Sapient\AccessWorldpay\Model\Checkout\Hpp\Json\Url\Config as UrlConfig;
use \Sapient\AccessWorldpay\Model\Checkout\Hpp\State as HppState;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\App\RequestInterface;
use Exception;

class Factory
{
   /**  @var \Magento\Store\Model\Store*/
    private $store;

    /**
     * @param StoreManagerInterface $storeManager
     * @param \Sapient\AccessWorldpay\Model\Checkout\Hpp\State $hppstate
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Repository $assetrepo
     * @param RequestInterface $request
     * @param \Sapient\AccessWorldpay\Logger\AccessWorldpayLogger $wplogger
     * @param \Sapient\AccessWorldpay\Helper\Data $worldpayhelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Sales\Model\Order $mageOrder
     * @param array $servicesservices
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        \Sapient\AccessWorldpay\Model\Checkout\Hpp\State $hppstate,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Repository $assetrepo,
        RequestInterface $request,
        \Sapient\AccessWorldpay\Logger\AccessWorldpayLogger $wplogger,
        \Sapient\AccessWorldpay\Helper\Data $worldpayhelper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Model\Order $mageOrder,
        $servicesservices = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->assetRepo = $assetrepo;
        $this->request = $request;
        $this->wplogger = $wplogger;
        $this->worldpayhelper = $worldpayhelper;
        $this->quoteRepository = $quoteRepository;
        $this->mageorder = $mageOrder;
        if (isset($services['store']) && $services['store'] instanceof StoreManagerInterface) {
            $this->store = $services['store'];
        } else {
            $this->store = $storeManager->getStore();
        }
        if (isset($services['state'])
            && $services['state'] instanceof \Sapient\AccessWorldpay\Model\Checkout\Hpp\State) {
            $this->state = $services['state'];
        } else {
            $this->state = $hppstate;
        }
    }

    /**
     * Create method
     *
     * @param string $javascriptObjectVariable
     * @param int|string $containerId
     *
     * @return Sapient\AccessWorldpay\Model\Checkout\Hpp\Json\Config
     */
    public function create($javascriptObjectVariable, $containerId)
    {
        $parts = parse_url($this->state->getRedirectUrl());
        parse_str($parts['query'], $orderparams);
        $orderkey = $orderparams['OrderKey'];
        $magentoincrementid = $this->_extractOrderId($orderkey);
        $mageOrder = $this->mageorder->loadByIncrementId($magentoincrementid);
        $quote = $this->quoteRepository->get($mageOrder->getQuoteId());
        
        $country = $this->_getCountryForQuote($quote);
        $language = $this->_getLanguageForLocale();

        $params = ['_secure' => $this->request->isSecure()];
        $helperhtml = $this->assetRepo->getUrlWithParams('Sapient_AccessWorldpay::helper.html', $params);
        $iframeurl = 'worldpay/redirectresult/iframe';
        $urlConfig = new UrlConfig(
            $this->store->getUrl($iframeurl, ['status' => 'success']),
            $this->store->getUrl($iframeurl, ['status' => 'cancel']),
            $this->store->getUrl($iframeurl, ['status' => 'pending']),
            $this->store->getUrl($iframeurl, ['status' => 'error']),
            $this->store->getUrl($iframeurl, ['status' => 'failure'])
        );
      
        return new Config(
            $this->worldpayhelper->getRedirectIntegrationMode($this->store->getId()),
            $javascriptObjectVariable,
            $helperhtml,
            $this->store->getBaseUrl(),
            $this->state->getRedirectUrl(),
            $containerId,
            $urlConfig,
            strtolower($language),
            strtolower($country)
        );
    }

    /**
     * Get CountryForQuote
     *
     * @param string $quote
     */
    private function _getCountryForQuote($quote)
    {
        $address = $quote->getBillingAddress();
        if ($address->getId()) {
            return $address->getCountry();
        }

        return $this->worldpayhelper->getDefaultCountry();
    }

    /**
     * Get LanguageForLocale
     */
    private function _getLanguageForLocale()
    {
        $locale = $this->worldpayhelper->getLocaleDefault();
        if (substr($locale, 3, 2) == 'NO') {
            return 'no';
        }
        return substr($locale, 0, 2);
    }

    /**
     * Get ExtractOrderId
     *
     * @param string $orderKey
     */
    private static function _extractOrderId($orderKey)
    {
        $array = explode('^', $orderKey);
        $ordercode = end($array);
        $ordercodearray = explode('-', $ordercode);
        return reset($ordercodearray);
    }
}
