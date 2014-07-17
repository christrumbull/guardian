<?php  
class Deviant_PurolatorShipping_Model_Carrier_PurolatorWeightConversion {
    
    public function toOptionArray() {
    $options = array(
    array(
               'value' => "2.20462262",
               'label' => "Product Weights In KG"
            ),
    array(
               'value' => "1",
               'label' => "Product Weights In LB"
            ),
         );
        return $options;
    }

  
}