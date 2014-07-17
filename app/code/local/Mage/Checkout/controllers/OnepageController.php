<?php
/**
* Magento
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@magentocommerce.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade Magento to newer
* versions in the future. If you wish to customize Magento for your
* needs please refer to http://www.magentocommerce.com for more information.
*
* @category    Mage
* @package     Mage_Checkout
* @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
* @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

include_once('Mage/Checkout/controllers/MultishippingController.php');
class vividfront_Checkout_Checkout_OnepageController extends Mage_Checkout_Controller_Action
{
    protected $_sectionUpdateFunctions = array(
        'payment-method'  => '_getPaymentMethodsHtml',
        'shipping-method' => '_getShippingMethodsHtml',
        'review'          => '_getReviewHtml',
    );

    /**
     * @return Mage_Checkout_OnepageController
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $this->_preDispatchValidateCustomer();

        $checkoutSessionQuote = Mage::getSingleton('checkout/session')->getQuote();
        if ($checkoutSessionQuote->getIsMultiShipping()) {
            $checkoutSessionQuote->setIsMultiShipping(false);
            $checkoutSessionQuote->removeAllAddresses();
        }

        return $this;
    }

    protected function _ajaxRedirectResponse()
    {
        $this->getResponse()
            ->setHeader('HTTP/1.1', '403 Session Expired')
            ->setHeader('Login-Required', 'true')
            ->sendResponse();
        return $this;
    }

    /**
     * Validate ajax request and redirect on failure
     *
     * @return bool
     */
    protected function _expireAjax()
    {
        if (!$this->getOnepage()->getQuote()->hasItems()
            || $this->getOnepage()->getQuote()->getHasError()
            || $this->getOnepage()->getQuote()->getIsMultiShipping()) {
            $this->_ajaxRedirectResponse();
            return true;
        }
        $action = $this->getRequest()->getActionName();
        if (Mage::getSingleton('checkout/session')->getCartWasUpdated(true)
            && !in_array($action, array('index', 'progress'))) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        return false;
    }

    /**
     * Get shipping method step html
     *
     * @return string
     */
    protected function _getShippingMethodsHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_shippingmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    /**
     * Get payment method step html
     *
     * @return string
     */
    protected function _getPaymentMethodsHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_paymentmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    protected function _getAdditionalHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('checkout_onepage_additional');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    /**
     * Get order review step html
     *
     * @return string
     */
    protected function _getReviewHtml()
    {
        return $this->getLayout()->getBlock('root')->toHtml();
    }

    /**
     * Get one page checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    /**
     * Checkout page
     */
    public function indexAction()
    {
         $_SESSION['loginsteps']=0;
        if (!Mage::helper('checkout')->canOnepageCheckout()) {
            Mage::getSingleton('checkout/session')->addError($this->__('The onepage checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
        }
        $quote = $this->getOnepage()->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->_redirect('checkout/cart');
            return;
        }
        if (!$quote->validateMinimumAmount()) {
            $error = Mage::getStoreConfig('sales/minimum_order/error_message');
            Mage::getSingleton('checkout/session')->addError($error);
            $this->_redirect('checkout/cart');
            return;
        }
        Mage::getSingleton('checkout/session')->setCartWasUpdated(false);
        Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('*/*/*', array('_secure'=>true)));
        $this->getOnepage()->initCheckout();
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->getLayout()->getBlock('head')->setTitle($this->__('Checkout'));
        $this->renderLayout();
    }

    /**
     * Checkout status block
     */
    public function progressAction()
    {

           //  regional mod tony sodano - 07.06.2012
           //  stop redirect on empty cart after rest_message*
               $store_id = Mage::app()->getStore()->getId();  // get store id from object

            if($store_id !== '1') { // if store_id isn't 1, then get progress 
               if ($this->_expireAjax()) {
                 return;
               }
            }
           // end regional mod

        $this->loadLayout(false);
        $this->renderLayout();
    }

    public function shippingMethodAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        $this->loadLayout(false);
        $this->renderLayout();
    }

    public function reviewAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Order success action
     */
    public function successAction()
    {
        $session = $this->getOnepage()->getCheckout();
        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $lastQuoteId = $session->getLastQuoteId();
        $lastOrderId = $session->getLastOrderId();
        $lastRecurringProfiles = $session->getLastRecurringProfileIds();
        if (!$lastQuoteId || (!$lastOrderId && empty($lastRecurringProfiles))) {
            $this->_redirect('checkout/cart');
            return;
        }

        $session->clear();
        $this->loadLayout();
        $this->_initLayoutMessages('checkout/session');
        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));
        $this->renderLayout();
    }

    public function failureAction()
    {
        $lastQuoteId = $this->getOnepage()->getCheckout()->getLastQuoteId();
        $lastOrderId = $this->getOnepage()->getCheckout()->getLastOrderId();

        if (!$lastQuoteId || !$lastOrderId) {
            $this->_redirect('checkout/cart');
            return;
        }

        $this->loadLayout();
        $this->renderLayout();
    }


    public function getAdditionalAction()
    {
        $this->getResponse()->setBody($this->_getAdditionalHtml());
    }

    /**
     * Address JSON
     */
    public function getAddressAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        $addressId = $this->getRequest()->getParam('address', false);
        if ($addressId) {
            $address = $this->getOnepage()->getAddress($addressId);

            if (Mage::getSingleton('customer/session')->getCustomer()->getId() == $address->getCustomerId()) {
                $this->getResponse()->setHeader('Content-type', 'application/x-json');
                $this->getResponse()->setBody($address->toJson());
            } else {
                $this->getResponse()->setHeader('HTTP/1.1','403 Forbidden');
            }
        }
    }

    /**
     * Save checkout method
     */
    public function saveMethodAction()
    {
        $_SESSION['loginsteps']=1;
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $method = $this->getRequest()->getPost('method');
            $result = $this->getOnepage()->saveCheckoutMethod($method);
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    /**
     * save checkout billing address
     */
     public function saveBillingAction()
    {
        $prductsName='';
        $this->_expireAjax();
        if ($this->_expireAjax()) {
            return;
        }

        $store_id = Mage::app()->getStore()->getId();  // get store id from object 
        $store_url = Mage::getBaseUrl();
        
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('billing', array());
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }
            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

            
            /* 
                region product ban mod - tony sodano 07.06.2012
                    -->
             */    
             
             if(isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                 $cart_pid = Mage::getModel('checkout/cart')->getProductIds();
                if($store_id == '1') {
                   $products_removeids=array();
                   $products_remove_info=array();
                   $product_removed ='';
                    $prductsName1=array();
                     $productsSku = array();
                }
                foreach($cart_pid as $pid)
                {

                    
                     $values = Mage::getModel('catalog/product')->load($pid)->getData();

                     if ($customerAddressId){
                          $customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
                          $data['country_id'] = $customerAddress['country_id'];
                     }
                     
                     if($store_id == '1') {
                     if(isset($values['countries'])) 
                     { 
                       if((($values['countries'] =='USA') && ($data['country_id'] =='US')) || (($values['countries'] =='Canada') && ($data['country_id'] =='CA')))
                       {    
                    
                          $checkFlag = 1;
                          $prductsName .= $values['name'].',';
                          array_push($prductsName1,$values['name'].':'.$values['sku']);
                          array_push($products_removeids, $pid);
                          array_push($productsSku, $values['sku']);
                          

                          $product_removed .= '<div style="text-align:left; color:#ff0000;"><span style="color:#ff0000; font-weight:bold;">'.$values['name'].'</span> is removed from your cart</div>';
                          
                       } // end if banned_in/region* operator 
                     } // end if(isset))
                } // end foreach
             }
                if(!empty($products_removeids))
                {
                    $cartHelper = Mage::helper('checkout/cart');
                    $items = $cartHelper->getCart()->getItems();
                    foreach ($items as $item) {
                        foreach ($products_removeids as $products_removeid) {
                            if ($item->getProduct()->getId() == $products_removeid) {
                                $itemId = $item->getItemId();
                                
                                //product_removed from cart
                                $cartHelper->getCart()->removeItem($itemId)->save();
                                
                                
                                
                                $cart = Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal();
                            }
                        } // end if store_id
                        }
                    }
                if($checkFlag==1){
                        $prductsName1 = implode(',',$prductsName1);
                        $productsSku = implode(',',$productsSku);
                        //$errormessage = $this->__(' The following products (%s) were removed from your cart. At this time we are not able to ship these items to your state.', $prductsName1); 
                        $errormessage = $this->__('Restricted Country %s', $prductsName1); 
                        $result['product_removed'] = "yes";
                        if($store_id == '5')
                        {
                             $result['cart_url'] = $store_url.'sorry.html';
                        }
                        else
                        {
                            $result['cart_url'] = $store_url.'checkout/cart/';
                        }
                        Mage::getSingleton('checkout/session')->addError($errormessage);
        
                    }

             }                           
            $result['country_id_temp'] = $data['country_id'];

            /* 
               <-- end region product ban mod 
            */
            
            if (!isset($result['error'])) {
                /* check quote for virtual */
                if ($this->getOnepage()->getQuote()->isVirtual()) {
                    $result['goto_section'] = 'payment';
                    $result['update_section'] = array(
                        'name' => 'payment-method',
                        'html' => $this->_getPaymentMethodsHtml()
                    );
                } elseif (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                    if($store_id == '5' || $store_id == '6')
                    {
                       /*  $method = 'flatrate_flatrate';
                         $result = $this->getOnepage()->saveShippingMethod($method);      */
                         $shippingAddress = $this->getOnepage()->getQuote()->getShippingAddress();
                         $shippingAddress->setCountryId('US')->setShippingMethod('flatrate_flatrate')->save();
                         $shippingAddress->save();
                        $result['goto_section'] = 'payment';
                        $result['update_section'] = array(
                            'name' => 'payment-method',
                            'html' => $this->_getPaymentMethodsHtml() 
                        );
                        $result['allow_sections'] = array('shipping');
                        $result['duplicateBillingInfo'] = 'true';
                    }
                    else
                    {
                             $result['goto_section'] = 'shipping_method';
                        $result['update_section'] = array(
                            'name' => 'shipping-method',
                            'html' => $this->_getShippingMethodsHtml()
                        );

                        $result['allow_sections'] = array('shipping');
                        $result['duplicateBillingInfo'] = 'true';     
                    }
                   
                } else {
                    $result['goto_section'] = 'shipping';
                }
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }
    
    public function saveBilling2Action()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
//            $postData = $this->getRequest()->getPost('billing', array());
//            $data = $this->_filterPostData($postData);
            $data = $this->getRequest()->getPost('billing', array());
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }
            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

            if (!isset($result['error'])) {
                /* check quote for virtual */
                if ($this->getOnepage()->getQuote()->isVirtual()) {
                    $result['goto_section'] = 'payment';
                    $result['update_section'] = array(
                        'name' => 'payment-method',
                        'html' => $this->_getPaymentMethodsHtml()
                    );
                } elseif (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                    $result['goto_section'] = 'shipping_method';
                    $result['update_section'] = array(
                        'name' => 'shipping-method',
                        'html' => $this->_getShippingMethodsHtml()
                    );

                    $result['allow_sections'] = array('shipping');
                    $result['duplicateBillingInfo'] = 'true';
                } else {
                    $result['goto_section'] = 'shipping';
                }
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    /**
     * Shipping address save action
     */
    public function saveShippingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping', array());
            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
            $result = $this->getOnepage()->saveShipping($data, $customerAddressId);



             /************************************************
                start region mod - tony sodano 07.06.2012 ->
             ************************************************/    
             
           /* if ($customerAddressId){
              $customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
              $data['region_id'] = $customerAddress['region_id'];
            }*/
    


            $cart_pid = Mage::getModel('checkout/cart')->getProductIds();
            $store_id = Mage::app()->getStore()->getId();  // get store id from object
            $store_url = Mage::getBaseUrl();

             if($store_id == '1') {
               $products_removeids=array();
               $products_remove_info=array();
               $product_removed ='';
                $prductsName1=array();
                 $productsSku = array();
            }
            foreach($cart_pid as $pid)
            {

                
                 $values = Mage::getModel('catalog/product')->load($pid)->getData();

                 if ($customerAddressId){
                      $customerAddress = Mage::getModel('customer/address')->load($customerAddressId);
                      $data['country_id'] = $customerAddress['country_id'];
                 }
                 
                 if($store_id == '1') {
                 if(isset($values['countries'])) 
                 { 
                   if((($values['countries'] =='USA') && ($data['country_id'] =='US')) || (($values['countries'] =='Canada') && ($data['country_id'] =='CA')))
                   {    
                
                      $checkFlag = 1;
                      $prductsName .= $values['name'].',';
                      array_push($prductsName1,$values['name'].':'.$values['sku']);
                      array_push($products_removeids, $pid);
                      array_push($productsSku, $values['sku']);
                      

                      $product_removed .= '<div style="text-align:left; color:#ff0000;"><span style="color:#ff0000; font-weight:bold;">'.$values['name'].'</span> is removed from your cart</div>';
                      
                   } // end if banned_in/region* operator 
                 } // end if(isset))
            } // end foreach
         } 
            
            $result['rest_message1'] = ' ';
            
            if(!empty($products_removeids))
            {
                $cartHelper = Mage::helper('checkout/cart');
                $items = $cartHelper->getCart()->getItems();
                foreach ($items as $item) {
                    foreach ($products_removeids as $products_removeid) {
                        if ($item->getProduct()->getId() == $products_removeid) {
                            $itemId = $item->getItemId();
                            
                            //product_removed from cart
                            $cartHelper->getCart()->removeItem($itemId)->save();
                            
                            
                            
                            $cart = Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal();
                        }
                    } // end if store_id
                    }
                }
                if($checkFlag==1){
                    $prductsName1 = implode(',',$prductsName1);
                    $productsSku = implode(',',$productsSku);
                     $errormessage = $this->__('Restricted State %s', $prductsName1); 
                    $result['product_removed'] = "yes";
                    if($store_id == '5')
                    {
                        $result['cart_url'] = $store_url.'sorry.html';
                    }
                    else
                    {
                        $result['cart_url'] = $store_url.'checkout/cart/';
                    }
                    Mage::getSingleton('checkout/session')->addError($errormessage);
    
                }

        if($store_id == '1' || $store_id == '5'){ 
            $cart = Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal();
            if($cart < 50)
            {
                $cartHelper = Mage::helper('checkout/cart');
                    $items = $cartHelper->getCart()->getItems();
                    foreach ($items as $item) {
                    if ($item->getProduct()->getId() == 308) {
                    $itemId = $item->getItemId();
                    $cartHelper->getCart()->removeItem($itemId)->save();
                    }
                }
            }
        }

        $result['country_id_temp'] = $data['country_id']; 
        
        
        /************************************************
            end region mod - tony sodano 07.06.2012 <-
        ************************************************/    
         
         
         
            if (!isset($result['error'])) {
                if($store_id == '5')
                {
                         /*$method = 'flatrate_flatrate';
                         $result = $this->getOnepage()->saveShippingMethod($method);
*/                         $shippingAddress = $this->getOnepage()->getQuote()->getShippingAddress();
                         $shippingAddress->setCountryId('US')->setShippingMethod('flatrate_flatrate')->save();
                         $shippingAddress->save();
                           $result['goto_section'] = 'payment';
                           $result['update_section'] = array(
                            'name' => 'payment-method',
                            'html' => $this->_getPaymentMethodsHtml()
                           );
                }
                else
                {
                    $result['goto_section'] = 'shipping_method';
                        $result['update_section'] = array(
                            'name' => 'shipping-method',
                            'html' => $this->_getShippingMethodsHtml()
                        );    
                }
                
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    /**
     * Shipping method save action
     */
    public function saveShippingMethodAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping_method', '');
            $result = $this->getOnepage()->saveShippingMethod($data);
            /*
            $result will have erro data if shipping method is empty
            */
            if(!$result) {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method',
                        array('request'=>$this->getRequest(),
                            'quote'=>$this->getOnepage()->getQuote()));
                $this->getOnepage()->getQuote()->collectTotals();
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

                $result['goto_section'] = 'payment';
                $result['update_section'] = array(
                    'name' => 'payment-method',
                    'html' => $this->_getPaymentMethodsHtml()
                );
            }
            $this->getOnepage()->getQuote()->collectTotals()->save();
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    /**
     * Save payment ajax action
     *
     * Sets either redirect or a JSON response
     */
    public function savePaymentAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        try {
            if (!$this->getRequest()->isPost()) {
                $this->_ajaxRedirectResponse();
                return;
            }

            // set payment to quote
            $result = array();
            $data = $this->getRequest()->getPost('payment', array());
            $result = $this->getOnepage()->savePayment($data);

            // get section and redirect data
            $redirectUrl = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
            if (empty($result['error']) && !$redirectUrl) {
                $this->loadLayout('checkout_onepage_review');
                $result['goto_section'] = 'review';
                $result['update_section'] = array(
                    'name' => 'review',
                    'html' => $this->_getReviewHtml()
                );
            }
            if ($redirectUrl) {
                $result['redirect'] = $redirectUrl;
            }
        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = $this->__('Unable to set Payment Method.');
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /* @var $_order Mage_Sales_Model_Order */
    protected $_order;

    /**
     * Get Order by quoteId
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        if (is_null($this->_order)) {
            $this->_order = Mage::getModel('sales/order')->load($this->getOnepage()->getQuote()->getId(), 'quote_id');
            if (!$this->_order->getId()) {
                throw new Mage_Payment_Model_Info_Exception(Mage::helper('core')->__("Can not create invoice. Order was not found."));
            }
        }
        return $this->_order;
    }

    /**
     * Create invoice
     *
     * @return Mage_Sales_Model_Order_Invoice
     */
    protected function _initInvoice()
    {
        $items = array();
        foreach ($this->_getOrder()->getAllItems() as $item) {
            $items[$item->getId()] = $item->getQtyOrdered();
        }
        /* @var $invoice Mage_Sales_Model_Service_Order */
        $invoice = Mage::getModel('sales/service_order', $this->_getOrder())->prepareInvoice($items);
        $invoice->setEmailSent(true)->register();

        Mage::register('current_invoice', $invoice);
        return $invoice;
    }

    /**
     * Create order action
     */
    public function saveOrderAction()
    {
        if ($this->_expireAjax()) {
            return;
        }

        $result = array();
        try {
            if ($requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds()) {
                $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
                if ($diff = array_diff($requiredAgreements, $postedAgreements)) {
                    $result['success'] = false;
                    $result['error'] = true;
                    $result['error_messages'] = $this->__('Please agree to all the terms and conditions before placing the order.');
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                    return;
                }
            }
            if ($data = $this->getRequest()->getPost('payment', false)) {
                $this->getOnepage()->getQuote()->getPayment()->importData($data);
            }
            $this->getOnepage()->saveOrder();

            $storeId = Mage::app()->getStore()->getId();
            $paymentHelper = Mage::helper("payment");
            $zeroSubTotalPaymentAction = $paymentHelper->getZeroSubTotalPaymentAutomaticInvoice($storeId);
            if ($paymentHelper->isZeroSubTotal($storeId)
                    && $this->_getOrder()->getGrandTotal() == 0
                    && $zeroSubTotalPaymentAction == Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE
                    && $paymentHelper->getZeroSubTotalOrderStatus($storeId) == 'pending') {
                $invoice = $this->_initInvoice();
                $invoice->getOrder()->setIsInProcess(true);
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();
            }

            $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
            $result['success'] = true;
            $result['error']   = false;
        } catch (Mage_Payment_Model_Info_Exception $e) {
            $message = $e->getMessage();
            if( !empty($message) ) {
                $result['error_messages'] = $message;
            }
            $result['goto_section'] = 'payment';
            $result['update_section'] = array(
                'name' => 'payment-method',
                'html' => $this->_getPaymentMethodsHtml()
            );
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $result['success'] = false;
            $result['error'] = true;
            $result['error_messages'] = $e->getMessage();

            if ($gotoSection = $this->getOnepage()->getCheckout()->getGotoSection()) {
                $result['goto_section'] = $gotoSection;
                $this->getOnepage()->getCheckout()->setGotoSection(null);
            }

            if ($updateSection = $this->getOnepage()->getCheckout()->getUpdateSection()) {
                if (isset($this->_sectionUpdateFunctions[$updateSection])) {
                    $updateSectionFunction = $this->_sectionUpdateFunctions[$updateSection];
                    $result['update_section'] = array(
                        'name' => $updateSection,
                        'html' => $this->$updateSectionFunction()
                    );
                }
                $this->getOnepage()->getCheckout()->setUpdateSection(null);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
            $result['success']  = false;
            $result['error']    = true;
            $result['error_messages'] = $this->__('There was an error processing your order. Please contact us or try again later.');
        }
        $this->getOnepage()->getQuote()->save();
        /**
         * when there is redirect to third party, we don't want to save order yet.
         * we will save the order in return action.
         */
        if (isset($redirectUrl)) {
            $result['redirect'] = $redirectUrl;
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * Filtering posted data. Converting localized data if needed
     *
     * @param array
     * @return array
     */
    protected function _filterPostData($data)
    {
        $data = $this->_filterDates($data, array('dob'));
        return $data;
    }
}