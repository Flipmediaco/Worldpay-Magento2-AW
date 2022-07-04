<?php

namespace Sapient\AccessWorldpay\Model\System\Config\Backend;

/**
 * stores PluginVersionistory
 *
 */
class PluginVersionHistory extends \Magento\Framework\App\Config\Value
{
    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Sapient\AccessWorldpay\Logger\AccessWorldpayLogger $wplogger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Sapient\AccessWorldpay\Model\System\Config\Backend\CurrentPluginVersion $currentversionconfig
     * @param \Magento\Framework\App\Cache\Manager $cacheManager
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Sapient\AccessWorldpay\Logger\AccessWorldpayLogger $wplogger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Sapient\AccessWorldpay\Model\System\Config\Backend\CurrentPluginVersion $currentversionconfig,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->wplogger = $wplogger;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->currentversionconfig = $currentversionconfig;
        $this->cacheManager = $cacheManager;
        parent::__construct($context, $registry, $scopeConfig, $cacheTypeList, $resource, $resourceCollection, $data);
    }
    
    /**
     * Process data after load
     *
     * @return void
     */
    protected function _afterLoad()
    {

        $value = $this->getValue();
        $value = $this->getPluginVersionHistoryDetails();
        
        if (isset($value['oldData']) && !empty($value['oldData'])
                && (isset($value['newData']) && !empty($value['newData']))) {
            $data = $value['oldData'].",".$value['newData'];
            $data =(array_unique(explode(",", $data)));
            $data = implode(",", $data);
            $this->setValue($data);
            $this->configWriter->save(
                'worldpay/general_config/plugin_tracker/wopay_plugin_version_history',
                $data
            );
            $this->cacheManager->flush($this->cacheManager->getAvailableTypes());

        } elseif (isset($value['newData'])) {
            $this->setValue($value['newData']);
            $this->configWriter->save(
                'worldpay/general_config/plugin_tracker/wopay_plugin_version_history',
                $value['newData']
            );
            $this->cacheManager->flush($this->cacheManager->getAvailableTypes());

        }
    }
    
    /**
     * Prepare data before save
     *
     * @return void
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $value = $this->getPluginVersionHistoryDetails();
        if (isset($value['oldData']) && !empty($value['oldData'])
                && (isset($value['newData']) && !empty($value['newData']))) {
            $data = $value['oldData'].",".$value['newData'];
            $data =(array_unique(explode(",", $data)));
            $data = implode(",", $data);
            $this->setValue($data);
            $this->configWriter->save(
                'worldpay/general_config/plugin_tracker/wopay_plugin_version_history',
                $data
            );
        } elseif (isset($value['newData'])) {
            $this->setValue($value['newData']);
            $this->configWriter->save(
                'worldpay/general_config/plugin_tracker/wopay_plugin_version_history',
                $value['newData']
            );
        }
    }
    /**
     * Get Plugin Version History
     *
     * @return array
     */
    public function getPluginVersionHistoryDetails()
    {
        $value=[];
        //current plugin version data
        $currentPluginData = $this->scopeConfig->getValue(
            'worldpay/general_config/plugin_tracker/current_wopay_plugin_version',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $currentVersion = $this->currentversionconfig->getPluinVersionDetails();
        if ($currentPluginData &&
                (isset($currentVersion['newVersion']) && $currentVersion['newVersion'] == $currentPluginData)) {
            $value['newData'] = $currentPluginData;
        } else {
            $value['newData'] = isset($currentVersion['newVersion'])?$currentVersion['newVersion']:"";
        }
        
        //plugin versions used till date
        $currentHistoryData = $this->scopeConfig->getValue(
            'worldpay/general_config/plugin_tracker/wopay_plugin_version_history',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (isset($currentHistoryData) && !empty($currentHistoryData)) {
            $value['oldData'] = $currentHistoryData;
            if (isset($value['newData']) && $value['oldData'] == $value['newData']) {
                $value['oldData'] ="";
            }
        }
        
        return array_unique($value);
    }
}
