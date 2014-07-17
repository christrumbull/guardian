<?php

class vividfront_Checkout_Model_Observer
{
   public function redirect(Varien_Event_Observer $observer)
   {
        $url = Mage::getBaseUrl();
        $store_id = Mage::app()->getStore()->getId();
        if($store_id==1){
                  $observer->getRequest()->setParam('return_url',$url.'checkout/onepage');
            }
                
        }                
    }

}
