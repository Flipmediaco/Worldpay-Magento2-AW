<?php
/**
 * @copyright 2020 Sapient
 */
namespace Sapient\AccessWorldpay\Controller\Savedcard;

use Magento\Framework\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;
use \Sapient\AccessWorldpay\Model\SavedTokenFactory;
use \Magento\Customer\Model\Session;

/**
 *  Display Saved card form
 */
class Edit extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;
    
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param SavedTokenFactory $savecard
     * @param \Sapient\AccessWorldpay\Helper\Data $worldpayHelper
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        SavedTokenFactory $savecard,
        \Sapient\AccessWorldpay\Helper\Data $worldpayHelper,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
        $this->savecard = $savecard;
        $this->customerSession = $customerSession;
        $this->worldpayHelper = $worldpayHelper;
    }

    /**
     * Ececute for edit
     */
    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $this->_redirect('customer/account/login');
            return;
        }
        $resultPage = $this->_resultPageFactory->create();
        $id = $this->getRequest()->getParam('id');
        $customerId = $this->customerSession->getCustomer()->getId();
        if ($id) {
            $cardDetails = $this->savecard->create()->load($id);
            if ($cardDetails->getCustomerId() != $customerId) {
                $this->_redirect('404notfound');
                return;
            }
            $resultPage->getConfig()->getTitle()->set($this->worldpayHelper->getAccountLabelbyCode('AC7'));
            return $resultPage;
        } else {
            $this->_redirect('404notfound');
            return;
        }
    }
}
