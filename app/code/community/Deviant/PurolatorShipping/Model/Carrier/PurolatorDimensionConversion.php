<?php  
class Deviant_PurolatorShipping_Model_Carrier_PurolatorDimensionConversion {
    
    public function toOptionArray() {
    $options = array(
    array(
               'value' => "0.393700787",
               'label' => "Product Dimensions In CM"
            ),
    array(
               'value' => "1",
               'label' => "Product Dimensions In Inches"
            ),
         );
        return $options;
    }

  
}