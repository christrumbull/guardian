<?php  
class Deviant_PurolatorShipping_Model_Carrier_PurolatorCalculationFactor {
    
    public function toOptionArray() {
    $options = array(
    array(
               'value' => "15",
               'label' => "Express"
            ),
    array(
               'value' => "10",
               'label' => "Ground"
            ),
         );
        return $options;
    }

  
}