<?php

namespace LCB\ShippingMethodFilter\Plugin;

/**
 * @category    LCB
 * @package     LCB_ShippingMethodFilter
 * @author      Tomasz Silpion Gregorczyk <tomasz@silpion.com.pl>
 **/
class Result
{

    /**
     * @var \Magento\Customer\Model\Session $customerSession
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(\Magento\Customer\Model\Session $customerSession, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->customerSession = $customerSession;
        $this->scopeConfig     = $scopeConfig;
    }

    /**
     * Filter shipping methods
     * 
     * @param \Magento\Shipping\Model\Rate\Result $subject
     * @param array $result
     * @return array
     */
    public function afterGetAllRates(\Magento\Shipping\Model\Rate\Result $subject, $result)
    {

        $filteredRates   = array();
        $customerGroupId = $this->customerSession->getCustomer()->getGroupId();

        foreach ($result as $rate) {
            
            $isAllowed = true;

            $carrierCode = $rate->getCarrier();
            $methodCode  = $rate->getMethod();

            $dissalowedCustomerGroups = $this->scopeConfig->getValue('shipping_restriction/' . $carrierCode . '_' . $methodCode . '/customer_groups', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            if (
                    ($dissalowedCustomerGroups && $customerGroupId == \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID) || 
                    ($dissalowedCustomerGroups && !in_array($customerGroupId, explode(',', $dissalowedCustomerGroups)))
               ) {
                $isAllowed = false;
            }

            if ($isAllowed) {
                $filteredRates[] = $rate;
            }

        }

        return $filteredRates;

    }

} 