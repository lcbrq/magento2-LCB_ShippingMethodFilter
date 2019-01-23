<?php

namespace LCB\ShippingMethodFilter\Plugin;

/**
 * @category    LCB
 * @package     LCB_ShippingMethodFilter
 * @author      Tomasz Silpion Gregorczyk <tomasz@silpion.com.pl>
 **/
class Section
{

    const SECTION_ID = 'shipping_restriction';
    
    /**
     * @var \Magento\Shipping\Model\Config\Source\Allmethods
     */
    protected $shippingMethods;
    
    /**
     * @var \Magento\Customer\Model\Config\Source\Group
     */
    protected $customerGroups;

    public function __construct(
        \Magento\Shipping\Model\Config\Source\Allmethods $shippingMethods,
        \Magento\Customer\Model\Config\Source\Group $customerGroups
    )
    {
        $this->shippingMethods = $shippingMethods;
        $this->customerGroups = $customerGroups;
    }

    /**
     * @return array
     */
    protected function getShippingMethodFilterGroups() : array {

        $shippingRestrictionGroups = [];
        foreach($this->shippingMethods->toOptionArray(true) as $carrierCode => $carrierData) {
            
            if(!$carrierCode || !isset($carrierData['value'])) {
                continue;
            }
            
            foreach($carrierData['value'] as $index => $method) {
             
            $shippingMethodCode = $method['value'];

            $groupsArray = $this->customerGroups->toOptionArray();
            
            if(isset($groupsArray[0]['label']) && isset($groupsArray[0]['label']) && isset($groupsArray[0]['value']) && !$groupsArray[0]['value']) {
                $groupsArray[0]['label'] = __('No');
            }

            $shippingRestrictionFields = [];
            $shippingRestrictionFields[$shippingMethodCode . '_' . 'customer_groups'] = [
                    'id' => 'customer_groups',
                    'type' => 'multiselect',
                    'sortOrder' => ($index * 10),
                    'showInDefault' => '1',
                    'showInWebsite' => '1',
                    'showInStore' => '1',
                    'label' => __('Restrict to following customer groups'),
                    'options' => [
                        'option' => $groupsArray
                    ],
                    'comment' => __(
                        'Allow %1 only for customer groups above',
                        $method['label']
                    ),
                    '_elementType' => 'field',
                    'path' => implode(
                        '/',
                        [
                            self::SECTION_ID,
                            $shippingMethodCode
                        ]
                    )
            ];

            $shippingRestrictionFields[$shippingMethodCode . '_' . 'max_weight'] = [
                    'id' => 'max_weight',
                    'type' => 'text',
                    'sortOrder' => ($index * 10),
                    'showInDefault' => '1',
                    'showInWebsite' => '1',
                    'showInStore' => '1',
                    'label' => __('Restrict to maximum item weight'),
                    'validate' => 'validate-digits',
                    'comment' => __(
                        'Restrict %1 to maximum product weigth calculated from %2 method',
                        $method['label'],
                        '$product->getWeight()'
                    ),
                    '_elementType' => 'field',
                    'path' => implode(
                        '/',
                        [
                            self::SECTION_ID,
                            $shippingMethodCode
                        ]
                    )
            ];

            $shippingRestrictionFields[$shippingMethodCode . '_' . 'max_heigth'] = [
                    'id' => 'max_height',
                    'type' => 'text',
                    'sortOrder' => ($index * 10),
                    'showInDefault' => '1',
                    'showInWebsite' => '1',
                    'showInStore' => '1',
                    'label' => __('Restrict to maximum item height'),
                    'validate' => 'validate-digits',
                    'comment' => __(
                        'Restrict %1 to maximum product height calculated from %2 method',
                        $method['label'],
                        '$product->getHeight()'
                    ),
                    '_elementType' => 'field',
                    'path' => implode(
                        '/',
                        [
                            self::SECTION_ID,
                            $shippingMethodCode
                        ]
                    )
            ];

            $shippingRestrictionFields[$shippingMethodCode . '_' . 'max_length'] = [
                    'id' => 'max_length',
                    'type' => 'text',
                    'sortOrder' => ($index * 10),
                    'showInDefault' => '1',
                    'showInWebsite' => '1',
                    'showInStore' => '1',
                    'label' => __('Restrict to maximum item length'),
                    'validate' => 'validate-digits',
                    'comment' => __(
                        'Restrict %1 to maximum product length calculated from %2 method',
                        $method['label'],
                        '$product->getLength()'
                    ),
                    '_elementType' => 'field',
                    'path' => implode(
                        '/',
                        [
                            self::SECTION_ID,
                            $shippingMethodCode
                        ]
                    )
            ];

            $shippingRestrictionFields[$shippingMethodCode . '_' . 'max_width'] = [
                    'id' => 'max_width',
                    'type' => 'text',
                    'sortOrder' => ($index * 10),
                    'showInDefault' => '1',
                    'showInWebsite' => '1',
                    'showInStore' => '1',
                    'label' => __('Restrict to maximum item width'),
                    'validate' => 'validate-digits',
                    'comment' => __(
                        'Restrict %1 to maximum product width calculated from %2 method',
                        $method['label'],
                        '$product->getWidth()'
                    ),
                    '_elementType' => 'field',
                    'path' => implode(
                        '/',
                        [
                            self::SECTION_ID,
                            $shippingMethodCode
                        ]
                    )
            ];

            $shippingRestrictionGroups[$shippingMethodCode] = [
                'id' => $shippingMethodCode,
                'label' =>  $carrierData['label'],
                'showInDefault' => '1',
                'showInWebsite' => '0',
                'showInStore' => '0',
                'sortOrder' => $index * 10,
                'children' => $shippingRestrictionFields
            ];
        }
        }

        return $shippingRestrictionGroups;
    }

    /**
     * Apply dynamic section configuration
     *
     * @param \Magento\Config\Model\Config\Structure\Element\Section $subject
     * @param callable $proceed
     * @param array $data
     * @param $scope
     * @return mixed
     */
    public function aroundSetData(\Magento\Config\Model\Config\Structure\Element\Section $subject, callable $proceed, array $data, $scope) {
        
        if($data['id'] == self::SECTION_ID) {
            $shippingMethodFilterGroups = $this->getShippingMethodFilterGroups();

            if(!empty($shippingMethodFilterGroups)) {
                $data['children'] += $shippingMethodFilterGroups;
            }
        }

        return $proceed($data, $scope);
    }
}