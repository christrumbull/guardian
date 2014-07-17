<?php

/**
 *
  * Shipping model for Purolator
  * 1.08 (08-20-2012) Wrapped address request in try catch block
  * 1.07 (08-09-2012) Updated translation
  * 1.06 (08-04-2012) Ensured 1lb minimum quote for pieces as well
  * 1.05 (08-04-2012) Ensured 1lb minimum quote
  *                   Updated pricing to ensure working with magento
  * 1.02 (08-04-2012) Some final tweeks to the verbage
  * 0.12 (08-01-2012) Removed dutiable attribute, changed defaults
  *                   Swapped price, date
  *                   Added tracking url
  * 0.11 (07-26-2012) Updated configuration view
  * 0.10 (07-26-2012) Changed free shipping calculation
  * 0.09 (07-26-2012) Modified virtual box mode to only pass in weight (not dimensional attributes)
  * 0.07 (07-19-2012) Added option to store request response in method descritption as JSON
  *                  Removed option to select Ship Calculation basis
  *                  Corrected operation of address validation.
  * 0.06 (07-13-2012) Added US and International languages
  *                  Removal of older code
  *                  Added optional settings: current list
  *                    SpecialHandling
  *                    ChainOfSignature
  *                    SaturdayPickup
  *                    SaturdayDelivery
  *                    ExpressCheque
  *                    HoldForPickup
  *                    OriginSignatureNotRequired
  *                    ResidentialSignatureDomestic
  *                    ResidentialSignatureIntl
  *                  Test US, International, CA
  * 0.05 (07-13-2012) Added US and International, Added optional settings
  * 0.04 (07-12-2012) Rewrote request to use GetFullEstimate, and ValidateCityPostalCodeZip methods
  *                  to perform an even more accurate quote
  * 0.03 (07-11-2012) Added dimensional weight,
  *                  Added translation
  *                  Removed tax from puraltors total price
  *                  Manually calculated days shipping
  * 0.02 (07-11-2012) Improved error handling, 
  *                  Added weight round full up
  * 0.01 (07-08-2012) Initial testing
  */


class Deviant_PurolatorShipping_Model_Carrier_Purolator extends Mage_Shipping_Model_Carrier_Abstract {
    /** Default gateway */
    protected $_devUrl = 'http://devwebservices.purolator.com';
    protected $_liveUrl = 'http://webservices.purolator.com';
    

    /**
    * unique internal shipping method identifier, same as the system.xml "groups" child
    *
    * @var string [a-z0-9_]
    */
    protected $_code = 'deviantpurolator';

    public function isZipCodeRequired($countryId = null){return true;}
    public function isStateProvinceRequired(){return true;}
    public function isTrackingAvailable(){return true;}
    public function getTrackingInfo($trackings) {
      if (!is_array($trackings)) {
        $trackings=array($trackings);
      }
      $result = Mage::getModel('shipping/tracking_result');
      foreach($trackings as $trackingnum) {
        $tracking = Mage::getModel('shipping/tracking_result_status');
        $tracking->setCarrier($this->_code);
        $tracking->setCarrierTitle($this->getConfigData('title'));
        $tracking->setUrl("http://shipnow.purolator.com/shiponline/track/purolatortrack.asp?pinno=".$trackingnum);
        $result->append($tracking);
      } // end for
      return $tracking;
    } // and trackinginfo
    

    /**
     * Collect the rates for the request
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
    
      if (!Mage::getStoreConfig('carriers/'.$this->_code.'/active')) {
          return false;
      }
      
      
      // initialize the variables
      $shippingPrice = 0;
      $config=(object)array();
      $config->locale_choose = $this->getConfigData("locale_choose");
      $config->api_access_key = Mage::helper('core')->decrypt($this->getConfigData("api_access_key"));
      $config->api_password = Mage::helper('core')->decrypt($this->getConfigData("api_password"));
      $config->billing_acct = Mage::helper('core')->decrypt($this->getConfigData("billing_acct"));
      $config->registered_acct = Mage::helper('core')->decrypt($this->getConfigData("registered_acct"));
      $config->allowed_methods = ",".$this->getConfigData("allowed_methods").",";
      $config->free_method = $this->getConfigData("free_method");
      $config->free_shipping_with_minimum_order_amount = $this->getConfigData("free_shipping_with_minimum_order_amount");
      $config->minimum_shipping_cost = $this->getConfigData("minimum_shipping_cost");
      $config->fixed_handling = $this->getConfigData("fixed_handling");
      $config->percent_markup = $this->getConfigData("percent_markup");
      $config->days_for_handling = $this->getConfigData("days_for_handling");
      $config->convert_weight = $this->getConfigData("convert_weight");
      $config->virtual_box_mode = $this->getConfigData("virtual_box_mode");
      $config->max_package_weight = $this->getConfigData("max_package_weight")*$config->convert_weight;
      $config->calculate_dimension_weight = $this->getConfigData("calculate_dimension_weight");
      $config->dimensional_multiplier = $this->getConfigData("dimensional_multiplier");
      $config->address_validation = $this->getConfigData("address_validation");
      $config->sallowspecific = $this->getConfigData("sallowspecific");
      $config->specificerrmsg = $this->getConfigData("specificerrmsg");
      $config->developer_mode = $this->getConfigData("developer_mode");
      $config->log_mode = $this->getConfigData("log_mode");
      $config->originpostalcode = $this->getConfigData("originpostalcode");
      $config->widthattribute = $this->getConfigData("widthattribute");
      $config->defaultwidth = $this->getConfigData("defaultwidth");
      $config->lengthattribute = $this->getConfigData("lengthattribute");
      $config->defaultlength = $this->getConfigData("defaultlength");
      $config->heightattribute = $this->getConfigData("heightattribute");
      $config->defaultheight = $this->getConfigData("defaultheight");
      $config->convert_size = $this->getConfigData("convert_size");
      $config->dutiable = $this->getConfigData("dutiable");
      $config->handling_type = $this->getConfigData("handling_type");
      $config->handling_action = $this->getConfigData("handling_action");
      $config->handling_fee = $this->getConfigData("handling_fee");
      $config->address_validation_warning = $this->getConfigData("address_validation_warning");
      $config->store_response = $this->getConfigData("address_validation_warning");
      $config->json_description    = ($this->getConfigData('json_description')==1);

      
      
      $config->url= ($config->developer_mode==1 ? $this->_devUrl : $this->_liveUrl);
      $config->installPath = Mage::getModuleDir('etc', 'Deviant_PurolatorShipping')  . DS . 'wsdl' . DS ;
      /*
      $config->installPath=dirname(__FILE__);
      if (stristr(__FILE__,"Deviant_PurolatorShipping_Model_Carrier_Purolator")!==false) {
        // Running in "compile" mode, need to manually set the install path
        $config->installPath = str_replace("includes/src","app/code/community/Deviant/PurolatorShipping/Model/Carrier/",$config->installPath);
      }*/
      $config->wsdlpath=$config->installPath.($config->developer_mode==1 ? "/Development" : "/Production");

      
      $currentLocale = Mage::app()->getLocale()->getLocaleCode();
      
      $config->language = "en";
      if ($config->locale_choose=="fr") {
        $config->language = "fr";
      }
      else if ($config->locale_choose=="choose") {
        $storeLocale = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE,  $this->getStore());
        if ($storeLocale == "fr_CA" || $storeLocale == "fr_FR") {
          $config->language = "fr";
        }
      }
      
      $config->originpostalcode = Mage::getStoreConfig('shipping/origin/postcode', $this->getStore());
      $config->originCountryCode = Mage::getStoreConfig('shipping/origin/country_id', $this->getStore());
      $config->originRegionID = Mage::getStoreConfig('shipping/origin/region_id', $this->getStore());
      $config->originCity = Mage::getStoreConfig('shipping/origin/city', $this->getStore());
      $config->originRegion = Mage::getModel('directory/region')->load($config->originRegionID)->getCode();
      
      //$config->originCountryCode;
      $config->destCity = $request->getDestCity();
      if ($request->getDestCountryId()) {
          $config->destCountry = $request->getDestCountryId();
      }
      else {
          $config->destCountry = self::USA_COUNTRY_ID;
      }
      
      $config->destRegion = $request->getDestRegionCode();
      if ($request->getDestPostcode()) {
          $config->destPostalCode = $request->getDestPostcode();
      }
      else {
        $config->destPostalCode='';
      }
      $this->shipLog($config, "Config for shipment*****",$config);
      $config->request = $request;
      $config->shipping=(object)array("methodkey"=>array(),"methods"=>array());
      $config->responseError="Shipment error, please contact us";
      $config->shippable = new Deviant_PurolatorShipping_Model_Carrier_Purolator_Shippable();
      $config->shippable->config = $config;
      $config->shippable->carrier = $this;
      $this->preptranslate($config);
      
      $result = Mage::getModel('shipping/rate_result');
      $config->result=$result;
      $defaults = $this->getDefaults();
      
      // Quick check to verify we can make a quote
      if (!$config->destPostalCode || !$config->originpostalcode) {
        // Return an error response
        $error = Mage::getModel('shipping/rate_result_error');
        $error->setCarrier($this->_code);
        $error->setCarrierTitle($this->getConfigData('title'));
        $error->setErrorMessage($this->getConfigData('specificerrmsg') . " - Missing postal codes for quote");
        $result->append($error);
        
      }
      else {
      
        $config->validateclient = new SoapClient( $config->wsdlpath."/ServiceAvailabilityService.wsdl", 
                                        array   (
                                                'trace'         =>  true,
                                                'location'  =>  $config->url."/PWS/V1/ServiceAvailability/ServiceAvailabilityService.asmx",
                                                'uri'               =>  "http://purolator.com/pws/datatypes/v1",
                                              'login'         => $config->api_access_key,
                                              'password'  =>  $config->api_password
                                              )
                                      );

        $validateheaders[] = new SoapHeader ( 'http://purolator.com/pws/datatypes/v1', 
                                            'RequestContext', 
                                            array (
                                                    'Version'           =>  '1.2',
                                                    'Language'          =>  'en',
                                                    'GroupID'           =>  'xxx',
                                                    'RequestReference'  =>  'Rating Example'
                                                  )
                                          ); 
              //Apply the SOAP Header to your client                            
              $config->validateclient->__setSoapHeaders($validateheaders);
              
              $config->validateclientrequest=$this->arrayToObject(
                array(
                  "Addresses"=>array("ShortAddress"=>array("PostalCode"=>$config->destPostalCode,
                                                           "Country"=>$config->destCountry,
                                                           ))
                                                           
                  )
                );
             
             // If destination city is not populated this is a quick quote
             // but we need to manually populate it regardless
             // Also perform this function if address validation is off
             try {
             if ($config->destCity=="" || $config->address_validation==0) {
              $response = $config->validateclient->ValidateCityPostalCodeZip($config->validateclientrequest);
              
              if ($response->SuggestedAddresses->SuggestedAddress) {
                  $config->destCity = $response->SuggestedAddresses->SuggestedAddress->Address->City;
                  
                  // Check the province JIC
                  if ($response->SuggestedAddresses->SuggestedAddress->Address->Province!=$config->request->getDestRegionCode()) {
                    if ($config->address_validation_warning==1) {
                        $error = Mage::getModel('shipping/rate_result_error');
                        $error->setCarrier($this->_code);
                        $error->setCarrierTitle($this->getConfigData('title'));
                        $error->setErrorMessage("Warning: Selected State / Province code does not match postal code ".
                            $config->destRegion."," . " auto changed to " . 
                            $response->SuggestedAddresses->SuggestedAddress->Address->Province );
                        $result->append($error);
                    }    
                    $config->destRegion=$response->SuggestedAddresses->SuggestedAddress->Address->Province;
                    $request->setDestRegionCode($config->destRegion);
                    $request->setDestCity($config->destCity);
                  }
               }
             }
            } catch(Exception $exception) {
              // Log the error
              $config->responseError="Purolator server reported an error on this quote, this information has been logged. ";
              $error = Mage::getModel('shipping/rate_result_error');
              $error->setCarrier($this->_code);
              $error->setCarrierTitle($this->getConfigData('title'));
              $error->setErrorMessage($this->getConfigData('specificerrmsg') . " - ".$config->responseError);
              $config->result->append($error);
              $this->shipLogError($config,"Error reported by purolator " .$exception->getMessage());
              return $result;
            }
             
            /** Purpose : Creates a SOAP Client in Non-WSDL mode with the appropriate authentication and 
              *           header information
            **/
            //Set the parameters for the Non-WSDL mode SOAP communication with your Development/Production credentials
            $config->estimateclient = new SoapClient( $config->wsdlpath."/EstimatingService.wsdl", 
                                      $config->estimateclientHead=array   (
                                              'trace'         =>  true,
                                              // Development (dev)
                                              'location'  =>  $config->url."/PWS/V1/Estimating/EstimatingService.asmx",
                                              // Production 
                                              // 'location'   =>  "https://webservices.purolator.com/PWS/V1/Estimating/EstimatingService.asmx",
                                              'uri'               =>  "http://purolator.com/pws/datatypes/v1",
                                              'login'         => $config->api_access_key,
                                              'password'  =>  $config->api_password
                                            )
                                    );
            //Define the SOAP Envelope Headers
            $headers[] = new SoapHeader ( 'http://purolator.com/pws/datatypes/v1', 
                                          'RequestContext', 
                                          array (
                                                  'Version'           =>  '1.3',
                                                  'Language'          =>  $config->language,
                                                  'GroupID'           =>  'xxx',
                                                  'RequestReference'  =>  'Rating Example'
                                                )
                                        ); 
            $this->shipLog($config,"Estimate Request head ", $config->estimateclientHead);
            
            //Apply the SOAP Header to your client                            
            $config->estimateclient->__setSoapHeaders($headers);
            $config->fullestimateclientrequest=$this->arrayToObject(
              array(
                "Shipment"=>array(// "M9W 7J2" ETOBICOKE
                  "SenderInformation"=>array("Address"=>array("PostalCode"=>$config->originpostalcode,
                                                              // Unneeded
                                                              //"Name"=>,
                                                              //"StreetNumber"=>,
                                                              //"StreetName"=>,
                                                              "City"=>$config->originCity,
                                                              "Province"=>$config->originRegion,
                                                              "Country"=>$config->originCountryCode,
                                                              )),
                  "ReceiverInformation"=>array("Address"=>array(//"PostalCode"=>$config->destPostalCode,
                                                                "PostalCode"=>$config->destPostalCode,
                                                              //"Name"=>"Foo Bar",
                                                              //"StreetNumber"=>"1234",
                                                              //"StreetName"=>"Main Street",
                                                              "City"=>$config->destCity,
                                                              "Province"=>$config->destRegion,
                                                              "Country"=>$config->destCountry,
                                                              //"PhoneNumber"=>array("CountryCode"=>"1","AreaCode"=>"905","Phone"=>"5555555"),
                                                                )),
                  "PackageInformation"=>array("TotalWeight"=>array("Value"=>0,"WeightUnit"=>"lb"),
                                             "TotalPieces"=>0,
                                             "PiecesInformation"=>array("Piece"=>array()),
                                             "ServiceID"=>($config->destCountry=="CA" ? 
                                               "PurolatorExpress":($config->destCountry=="US" ?
                                               "PurolatorExpressU.S.":"PurolatorExpressInternational")),
                                             
                                             ),
                  "PaymentInformation"=>array("PaymentType"=>"Sender",
                                              "BillingAccountNumber" => $config->billing_acct,
                                              "RegisteredAccountNumber"=>$config->registered_acct),
                  "PickupInformation"=>array("PickupType"=>"DropOff"),
                  
                  
              ),
              "ShowAlternativeServicesIndicator"=>"true")
              );
            $config->fullestimateclientitem=array("Weight"=>array("Value"=>0,"WeightUnit"=>"lb"),
                                                  "Length"=>array("Value"=>0,"DimensionUnit"=>"in"),
                                                  "Width"=>array("Value"=>0,"DimensionUnit"=>"in"),
                                                  "Height"=>array("Value"=>0,"DimensionUnit"=>"in"),
                                                  //"Options"
                                                 );
            //print_r($config->fullestimateclientrequest);
            $config->fullestimateclientrequest->Shipment->PackageInformation->PiecesInformation->Piece=array();
            $options = array();
            $configOptionsInformation = "";
            switch ($config->destCountry) {
              case "CA":
                $configOptionsInformation=$this->getConfigData("options_information_canada");
                break;
              case "US":
                $configOptionsInformation=$this->getConfigData("options_information_us");
                break;
              default:  
                $configOptionsInformation=$this->getConfigData("options_information_international");
                break;
            }
            
            // Remove whitespace from options
            $c=str_replace(array(" ","\t","\n","\r","\0","\x0b"),"",$configOptionsInformation);
            if ($c!="") {
              foreach(explode(",",$c) as $option) {
                $detail    = explode("=",$option);
                $options[] = (object)array("ID"=>$detail[0],"Value"=>$detail[1]);
              }
              //print_r($options);die();
              $config->fullestimateclientrequest->Shipment->PackageInformation->OptionsInformation=(object)array("Options"=>(object)array("OptionIDValuePair"=>array()));
              $config->fullestimateclientrequest->Shipment->PackageInformation->OptionsInformation->Options->OptionIDValuePair=$options;
            }
            
          $quoteResult = false;
          try {
            $quoteResult = $this->processQuote($config, $result, $defaults);
          } catch(Exception $exception) {
            // Log the error
            $config->responseError="Purolator server reported an error on this quote, this information has been logged. ";
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($this->getConfigData('specificerrmsg') . " - ".$config->responseError);
            $config->result->append($error);
            $this->shipLogError($config,"Error reported by purolator " .$exception->getMessage());
          }
        if ($quoteResult!=FALSE) {
        
            // Determine cheapest shipping method, if neccesary
            $freeshipmethod=$config->free_method;
            if ($config->free_shipping_with_minimum_order_amount>0 && 
                  $request->getPackageValueWithDiscount()>=$config->free_shipping_with_minimum_order_amount &&
                  $config->free_method=="LowestPriceFreeShipping") {
               $lowestPrice=9999999999;     
               foreach ($config->shipping->methods as $method=>$estimate) {
                  if ($estimate->PreTaxPrice<$lowestPrice) {
                    $freeshipmethod=$method;
                    $lowestPrice=$estimate->PreTaxPrice;
                  }
               }
            }
            
            // Got through all methods to create shipping options
            foreach ($config->shipping->methods as $method=>$estimate) {
                $this->shipLog($config, "Composed total estimate",$estimate);
                
                $rate = Mage::getModel('shipping/rate_result_method');
                $rate->setCarrier($this->_code);

                $rate->setMethod($method);
                
                
                $rate->setCost($estimate->PreTaxPrice);
                if ($config->json_description) {
                  $rate->setMethodDescription( json_encode(array("request"=>$config->fullestimateclientrequest,"response"=>$estimate)));
                }
                
                $totalPrice = max($estimate->PreTaxPrice*(1+$config->percent_markup/100)+$config->fixed_handling,
                                  $config->minimum_shipping_cost);
                
                
                $methodTitle = $this->translate($config,$method) . " (" 
                  . ($estimate->RealTransitDays + $config->days_for_handling) . 
                    " ".$this->translate($config,"Business Day".(($estimate->RealTransitDays + $config->days_for_handling)>1?"s":"")).")";
                
                // Add in any special magento pricing
                $totalPrice = $this->getMethodPrice($totalPrice, $methodTitle);
                
                if ($config->free_shipping_with_minimum_order_amount>0 && 
                  $config->runningPrice>$config->free_shipping_with_minimum_order_amount &&
                  $freeshipmethod==$method) {
                  $this->shipLog($config, "Free Shipping Detected, ",$config->runningPrice);
                  $totalPrice=0;
                  $methodTitle = $this->translate($config,"FREE")." " . $methodTitle;
                }
                $rate->setMethodTitle($methodTitle);
                $rate->setPrice($totalPrice);
                $result->append($rate);
            } // end for
          
        }
        else {
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($this->getConfigData('specificerrmsg') . " - ".$config->responseError);
            $config->result->append($error);
          
        }
      }
    return $result;
    }
    
    /**
    * Process quote against all items in cart
    */
    private function processQuote(&$config, &$result, &$defaults) {
      $config->runningWeight=0;
      $config->runningPrice=0;
      $invoiceItemList = $config->request->getAllItems();
      $config->shipment = (object) array();
      foreach($invoiceItemList as $invoiceItem) {
        // Protect against duplicate items
        if (!$invoiceItem->getHasChildren()) {
          // Re-request the product (So dimension attributes are available)
          $productId = $invoiceItem->getProductId();
          $productList = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect($config->widthattribute)
                ->addAttributeToSelect($config->lengthattribute)
                ->addAttributeToSelect($config->heightattribute);
          
          
          $productList->addIdFilter($productId);
          // Fetch the product
          foreach($productList as $product) {
            //echo $pl->getData('package_depth');
            break;
          } // end for
          $weight = ($invoiceItem->getWeight()*$config->convert_weight);
          $productData=(object)array("Product"=>$productId,"Weight"=>$weight);
          
          //print_r($invoiceItem->toArray());die();
          if ($product) {
            if ($config->calculate_dimension_weight==1) {
              $productData->length = $product->getData($config->lengthattribute)*$config->convert_size;
              if ($productData->length=="" && $config->defaultlength>0) {
                $productData->length = $config->defaultlength*$config->convert_size;
              }
              
              $productData->width = $product->getData($config->widthattribute)*$config->convert_size;
              if ($productData->width=="" && $config->defaultwidth>0) {
                $productData->width = $config->defaultwidth*$config->convert_size;
              }
              
              $productData->height = $product->getData($config->heightattribute)*$config->convert_size;
              if ($productData->height=="" && $config->defaultheight>0) {
                $productData->height = $config->defaultheight*$config->convert_size;
              }
            } else {
              $productData->length=0;
              $productData->width=0;
              $productData->height=0;
            }
            
            
            $productData->qty = ($invoiceItem->getParentItem() ? $invoiceItem->getParentItem()->getQty() : $invoiceItem->getQty());
            
            $config->runningPrice+=$invoiceItem->getProduct()->getPrice()*$productData->qty;
            
            $this->shipLog($config,"Product data:",$productData);
            // Append product data to estimate call
            
            if (!$config->shippable->addPiece($productData)) {
              return false;
            }
            
          }
          else {
            // Product not found
            $this->shipLogError($config,"Product not found :", $productData);
            return false;
          }
        }
      }
      
      // Full estimate request
      $config->shippable->prepareRequest();
      $config->fullestimateclientrequest->Shipment->
            PackageInformation->TotalWeight->Value = max(1,$config->fullestimateclientrequest->Shipment->
            PackageInformation->TotalWeight->Value);
      foreach($config->fullestimateclientrequest->Shipment->
              PackageInformation->PiecesInformation->Piece  as $piece) {
            // If piece is less then 1lb round up
            $piece->Weight->Value = max($piece->Weight->Value,1);
            
      }
      $this->shipLog($config,"Full Request **",$config->fullestimateclientrequest);
      $config->fullestimateclientresponse=$response = $config->estimateclient->GetFullEstimate($config->fullestimateclientrequest);

      if (!$this->checkResponse($config,$response,"")) {
       
        return false;
      }
      
      return true;
    }
    private function checkResponse(&$config, &$response, $methodprefix="") {
      $found=false;
      $this->shipLog($config,"Response ",$response);
      $shipping = $config->shipping;
      $firstresponse = !isset($shipping->methodkey[$methodprefix]);
      $shipping->methodkey[$methodprefix]=$methodprefix;
      $methods = $shipping->methods;
      if ($response) {
      //print_r($response);die();
        if (!$response->ShipmentEstimates) {
          $error = "";
          foreach($this->foreachArray($response->ResponseInformation->Errors->Error) as $err) {
              $error .=$err->Description;
          }
          $this->shipLog($config,"Error in response",$errors );
          $config->responseError=$error;
        }
        else {
            
          // Estimates available
          foreach($this->foreachArray($response->ShipmentEstimates->ShipmentEstimate) as $estimate) {
          
            $taxes = 0;
            foreach($estimate->Taxes->Tax as $tax) {
              $taxes += $tax->Amount;
            }
            $estimate->RealTransitDays=$this->datediff(strtotime($estimate->ExpectedDeliveryDate), 
                                                      strtotime($estimate->ShipmentDate));
            $estimate->PreTaxPrice=$estimate->TotalPrice - $taxes;
            if (stristr($config->allowed_methods,",".$estimate->ServiceID.",")!==FALSE) {
              $methodName = $methodprefix.$estimate->ServiceID;
                if ($firstresponse) {                  
                  if (stristr($config->allowed_methods,",".$estimate->ServiceID.",")!==FALSE) {
                    $found = true;
                    $methods[$methodName]=$estimate;
                    // We need to remove taxes from the total
                  }
                }
                else if (isset($methods[$methodName])) {
                  // Was in the old methods, update quote with new amounts.
                  $oldestimate = $methods[$methodName];
                  $oldestimate->RealTransitDays=max($oldestimate->RealTransitDays, $estimate->RealTransitDays);
                  $oldestimate->PreTaxPrice+=$estimate->PreTaxPrice;
                  $methods[$methodName]=$oldestimate;
                  $found = true;
                }
            }
          }
          
        }
      }
      else {
        $this->shipLogError($config, $config->responseError="Unable to contact purolator");
        return false;
      }
      if (!$found) {
        $this->shipLog($config,"Unable to generate quote for item(s)");
        return false;
      }
      
      
      $config->shipping->methods=$methods;
      return true;
    }
    
    private function foreachArray(&$arrayOrItem) {
      return (is_array($arrayOrItem) ? $arrayOrItem: array($arrayOrItem));
    }
    
    function arrayToObject($array) {
        if(!is_array($array)) {
            return $array;
        }
        
        $object = new stdClass();
        if (is_array($array) && count($array) > 0) {
          foreach ($array as $name=>$value) {
            $name = trim($name);
            if (!empty($name)) {
                $object->$name = $this->arrayToObject($value);
            }
          }
          return $object;
        }
        else {
          return FALSE;
        }
    }    
    
    
    function preptranslate(&$config) {
      $config->translation=
      array(
    "Item too heavy to ship using this service"=>array("fr"=>"Trop lourd pour expédier en utilisant ce service"),
        "days"=>array("fr"=>"jours"),
        "Business Days"=>array("fr"=>"jours ouvrables"),
        "Business Day"=>array("fr"=>"jour ouvrable"),
        "FREE"=>array("fr"=>"GRATUIT"),
        
    "PurolatorExpress9AM"=>array("en"=>"Express 9AM","fr"=>"Express 9 h"),
    "PurolatorExpress10:30AM"=>array("en"=>"Express 10:30AM","fr"=>"Express 10 h 30"),
    "PurolatorExpress"=>array("en"=>"Express","fr"=>"Express"),
    "PurolatorExpressPackU.S."=>array("en"=>"Express U.S. Pack ","fr"=>"Express Soirée"),
    "PurolatorExpressU.S.Pack9AM"=>array("en"=>"Express U.S. Pack 9AM","fr"=>"Express Pack 9 h vers les É.-U."),
    "PurolatorExpressU.S.Pack10:30AM"=>array("en"=>"Express U.S. Pack 10:30AM","fr"=>"Express Pack 10 h 30 vers les É.-U."),
    "PurolatorExpressEvening"=>array("en"=>"Express Evening","fr"=>"Express Soirée"),
    "PurolatorExpressEnvelope9AM"=>array("en"=>"Express Envelope 9AM","fr"=>"Express Enveloppe 9 h"),
    "PurolatorExpressEnvelope10:30AM"=>array("en"=>"Express Envelope 10:30AM","fr"=>"Express Enveloppe 10 h 30"),
    "PurolatorExpressEnvelope"=>array("en"=>"Express Envelope","fr"=>"Express Enveloppe"),
    "PurolatorExpressEnvelopeEvening"=>array("en"=>"Express Envelope Evening","fr"=>"Express Enveloppe Soirée"),
    "PurolatorExpressPack9AM"=>array("en"=>"Express Pack 9AM","fr"=>"Express Pack 9 h"),
    "PurolatorExpressPack10:30AM"=>array("en"=>"Express Pack 10:30AM","fr"=>"Express Pack 10 h 30"),
    "PurolatorExpressPack"=>array("en"=>"Express Pack","fr"=>"Express Pack"),
    "PurolatorExpressPackEvening"=>array("en"=>"Express Pack Evening","fr"=>"Express Pack Soirée"),
    "PurolatorExpressBox9AM"=>array("en"=>"Express Box 9AM","fr"=>"Express Boîte 9 h"),
    "PurolatorExpressBox10:30AM"=>array("en"=>"Express Box 10:30AM","fr"=>"Express Boîte 10 h 30"),
    "PurolatorExpressBox"=>array("en"=>"Express Box","fr"=>"Express Boîte"),
    "PurolatorExpressBoxEvening"=>array("en"=>"Express Box Evening","fr"=>"Express Boîte Soirée"),
    "PurolatorGround"=>array("en"=>"Ground","fr"=>"Routier"),
    "PurolatorGround9AM"=>array("en"=>"Ground 9AM","fr"=>"Routier 9 h"),
    "PurolatorGround10:30AM"=>array("en"=>"Ground 10:30AM","fr"=>"Routier 10 h 30"),
    "PurolatorGroundEvening"=>array("en"=>"Ground Evening","fr"=>"Routier"),
    "PurolatorExpressU.S."=>array("en"=>"Express U.S.","fr"=>"Routier Soirée"),
    "PurolatorExpressU.S.9AM"=>array("en"=>"Express U.S. 9AM","fr"=>"Express 9 h vers les É.-U."),
    "PurolatorExpressU.S.10:30AM"=>array("en"=>"Express U.S. 10:30AM","fr"=>"Express 10 h 30 vers les É.-U."),
    "PurolatorExpressU.S.12:00"=>array("en"=>"Express U.S. 12:00","fr"=>"Express Midi vers les É.-U."),
    "PurolatorExpressEnvelopeU.S."=>array("en"=>"Express Envelope U.S.","fr"=>"Express Enveloppe vers les É.-U."),
    "PurolatorExpressU.S.Envelope9AM"=>array("en"=>"Express U.S. Envelope 9AM","fr"=>"Express Enveloppe 9 h vers les É.-U."),
    "PurolatorExpressU.S.Envelope10:30AM"=>array("en"=>"Express U.S. Envelope 10:30AM","fr"=>"Express Enveloppe 10 h 30 vers les É.-U."),
    "PurolatorExpressU.S.Envelope12:00"=>array("en"=>"Express U.S. Envelope 12:00","fr"=>"Express Enveloppe Midi vers les É.-U."),
    "PurolatorExpressU.S.Pack12:00"=>array("en"=>"Express U.S. Pack 12:00","fr"=>"Express Pack Midi vers les É.-U."),
    "PurolatorExpressBoxU.S."=>array("en"=>"Express U.S. Box","fr"=>"Express Boîte vers les É.-U."),
    "PurolatorExpressU.S.Box9AM"=>array("en"=>"Express U.S. Box 9AM","fr"=>"Express Boîte 9 h vers les É.-U."),
    "PurolatorExpressU.S.Box10:30AM"=>array("en"=>"Express U.S. Box 10:30AM","fr"=>"Express Boîte 10 h 30 vers les É.-U."),
    "PurolatorExpressU.S.Box12:00"=>array("en"=>"Express U.S. Box 12:00","fr"=>"Express Boîte Midi vers les É.-U."),
    "PurolatorGroundU.S."=>array("en"=>"Ground U.S.","fr"=>"Routier vers les É.-U."),
    "PurolatorExpressInternational"=>array("en"=>"Express International","fr"=>"Express International"),
    "PurolatorExpressInternational9AM"=>array("en"=>"Express International 9AM","fr"=>"Express 9 h International"),
    "PurolatorExpressInternational10:30AM"=>array("en"=>"Express International 10:30AM","fr"=>"Express 10 h 30 International"),
    "PurolatorExpressInternational12:00"=>array("en"=>"Express International 12:00","fr"=>"Express Midi International"),
    "PurolatorExpressEnvelopeInternational"=>array("en"=>"Express Envelope International","fr"=>"Express Enveloppe International"),
    "PurolatorExpressInternationalEnvelope9AM"=>array("en"=>"Express International Envelope 9AM","fr"=>"Express Enveloppe 9 h International"),
    "PurolatorExpressInternationalEnvelope10:30AM"=>array("en"=>"Express International Envelope 10:30AM","fr"=>"Express Enveloppe 10 h 30 International"),
    "PurolatorExpressInternationalEnvelope12:00"=>array("en"=>"Express International Envelope 12:00","fr"=>"Express Enveloppe Midi International"),
    "PurolatorExpressPackInternational"=>array("en"=>"Express International Pack","fr"=>"Express Pack International"),
    "PurolatorExpressInternationalPack9AM"=>array("en"=>"Express International Pack 9AM","fr"=>"Express Pack 9 h International"),
    "PurolatorExpressInternationalPack10:30AM"=>array("en"=>"Express International Pack 10:30AM","fr"=>"Express Pack 10 h 30 International"),
    "PurolatorExpressInternationalPack12:00"=>array("en"=>"Express International Pack 12:00","fr"=>"Express Pack Midi International"),
    "PurolatorExpressBoxInternational"=>array("en"=>"Express International Box","fr"=>"Express Boîte International"),
    "PurolatorExpressInternationalBox9AM"=>array("en"=>"Express International Box 9AM","fr"=>"Express Boîte International 9 h"),
    "PurolatorExpressInternationalBox10:30AM"=>array("en"=>"Express International Box 10:30AM","fr"=>"Express Boîte International 10 h 30"),
    "PurolatorExpressInternationalBox12:00"=>array("en"=>"Express International Box 12:00","fr"=>"Express Boîte International Midi"),
    "PurolatorGroundDistribution"=>array("en"=>"Ground Distribution","fr"=>"Routier - Distribution"),

    "LowestPriceFreeShipping"=>array("en"=>"Free Shipping","fr"=>"Livraison Gratuite"),
        
          );
    }
    
    function translate(&$config,$message) {
      $message=trim($message);
      if (isset($config->translation[$message]) && isset($config->translation[$message][$config->language])) {
        return $config->translation[$message][$config->language];
      }
      
      if (isset($config->translation[$message]) && isset($config->translation[$message]["en"])) {
        return $config->translation[$message]["en"];
      }
      
      return $message;
    }
    
    private function datediff($d1,$d2) {
      return ($d1-$d2)/(60*60*24);
    }
    
    // Loggers
    public function shipLog(&$config, $message, &$item=false) {
      if ($config->log_mode==1) {
        Mage::log($message.($item!==false ? print_r($item,TRUE):""),Zend_Log::DEBUG,"purolator.log");
      }
    }
    // Loggers
    public function shipLogError(&$config, $message, &$item=false) {
      Mage::log($message.($item!==false ? print_r($item,TRUE):""),Zend_Log::ERR,"purolator.log");
    }
    
      
      
      
      
    // Function to shop options for multi lingual..
    public function toOptionArray()
    {
    $options = array(
    array(
               'value' => "en",
               'label' => "English"
            ),
    array(
               'value' => "fr",
               'label' => "French"
            ),
    array(
               'value' => "choose",
               'label' => "Based on store"
            )
          );
        return $options;
    }
}    
    class Deviant_PurolatorShipping_Model_Carrier_Purolator_Shippable_Box {
      public $runningWeight=0;
      public $runningSize=0;
      public $config=false;
      public $carrier=false;
      public $items = array();

      public function addPiece(&$piece, &$productData) {
        if ($this->runningWeight+$piece->Weight->Value>$this->config->max_package_weight) {
            $this->config->responseError="Item weight to large";
            return false;
        }
        if ($this->config->calculate_dimension_weight==1) {
          // Use the formula to calculate the
          $newweight= (($productData->length*
                              $productData->width*
                              $productData->height)/1728)*$this->config->dimensional_multiplier;
          $this->carrier->shipLog($this->config, "Calculated weight,$newweight was, ".$piece->Weight->Value .",Product ".$productData->Product);
          
          if ($piece->Weight->Value<$newweight) {
            $piece->Weight->Value=$newweight;
          }                    
        }
        
        $this->runningWeight+=$piece->Weight->Value;
        
        $this->items[]=$piece;
        return true;
      }
      
    }
    class Deviant_PurolatorShipping_Model_Carrier_Purolator_Shippable {
      public $config=false;
      public $carrier=false;
      protected $boxes = array();
      public function addPiece(&$productData) {
        for($x=$productData->qty;$x>0;$x--) {      
          $piece = $this->carrier->arrayToObject($this->config->fullestimateclientitem);
          $piece->Weight->Value=$productData->Weight;
          $this->config->fullestimateclientrequest->Shipment->
            PackageInformation->TotalWeight->Value+=$productData->Weight;
            
          if ($this->config->virtual_box_mode==1) {
            // find a box for the piece
            if (!$this->findBox($piece,$productData)) {
                // $productData->Product
                  $this->config->responseError="Item too large to ship. ";
                  $error = Mage::getModel('shipping/rate_result_error');
                  $error->setCarrier($this->carrier->_code);
                  $error->setCarrierTitle($this->carrier->getConfigData('title'));
                  $error->setErrorMessage($this->carrier->getConfigData('specificerrmsg') . " - ".$this->config->responseError);
                  $this->config->result->append($error);
                $this->carrier->shipLogError($this->config, "Item too big for quote", $productData);
                
                return false;
            }

          }
          else {
                if ($this->config->calculate_dimension_weight==1) {
                  $piece->Length->Value=$productData->length;
                  $piece->Width->Value=$productData->width;
                  $piece->Height->Value=$productData->height;
                }
                $this->config->fullestimateclientrequest->Shipment->
                  PackageInformation->PiecesInformation->Piece[]=$piece;
                $this->config->fullestimateclientrequest->Shipment->
                  PackageInformation->TotalPieces++;
          }
        }  
      
        return true;
      }
      public function findBox(&$piece, &$productData) {
        $found = false;
        foreach($this->boxes as $box) {
          if ($box->addPiece($piece,$productData)) {
            $found = true;
            break;
          }
        }
        if (!$found) {
          $box = new Deviant_PurolatorShipping_Model_Carrier_Purolator_Shippable_Box();
          $box->config = $this->config;
          $box->carrier = $this->carrier;
          if (!$box->addPiece($piece, $productData)) {
            $this->carrier->shipLogError($this->config, "Item too big for quote");
            return false;
          }
          $this->boxes[]=$box;
        }
        return true;
      }
      public function prepareRequest() {
        if ($this->config->virtual_box_mode==1) {
          foreach($this->boxes as $box) {

            $piece = $this->carrier->arrayToObject($this->config->fullestimateclientitem);
            $piece->Weight->Value=$box->runningWeight;
            
            $this->config->fullestimateclientrequest->Shipment->
              PackageInformation->PiecesInformation->Piece[]=$piece;
            $this->config->fullestimateclientrequest->Shipment->
              PackageInformation->TotalPieces++;
            
          }
        }
      }
      
    }




