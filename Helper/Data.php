<?php
/**
 * Copyright © 2015 Alcatel . All rights reserved.
 */
namespace Alcatel\Poc\Helper;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

	/**
     * @param \Magento\Framework\App\Helper\Context $context
     */

    public $cancellabletimepath = 'alcatel/ordersettings/cancellabletime';
    public $cancellablestatus = 'alcatel/ordersettings/cancellable_status';
    public $noncancellablestatus = 'alcatel/ordersettings/non_cancellable_status';
    public $fakecancelstatus = 'alcatel/ordersettings/fake_status';
    public $fakecanceltime = 'alcatel/ordersettings/fake_cancel_order';


	public function __construct(\Magento\Framework\App\Helper\Context $context,
          \Magento\Store\Model\StoreManagerInterface $storeManager,
		  \Magento\Customer\Model\Session $customerSession,
          \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
          \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
		  \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
	) {
		$this->orderRepository = $orderRepository;
		$this->customerSession = $customerSession;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->currentCustomer = $currentCustomer;
        $this->_storeCode=$this->_storeManager->getStore()->getCode();
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/alcatelpoc.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
		parent::__construct($context);
	}

    public function getCurrentStoreConfigValue($path){
        return $this->_scopeConfig->getValue($path,'store',$this->_storeCode);
    }

    public function getcancellabletime(){
        return $this->getCurrentStoreConfigValue($this->cancellabletimepath);
    }

     public function getcancellablestatus(){
        return $this->getCurrentStoreConfigValue($this->cancellablestatus);
    }

     public function getnoncancellablestatus(){
        return $this->getCurrentStoreConfigValue($this->noncancellablestatus);
    }

    public function getfakestatus(){
        return $this->getCurrentStoreConfigValue($this->fakecancelstatus);
    }

    public function getfakecanceltime(){
        return $this->getCurrentStoreConfigValue($this->fakecanceltime);
    }

	public function getshippingapi($orderid){
		$url = "http://localhost/shippingapi.php?orderid=".$orderid;
		return $url;
	}

	public function canCancel($orderId)
    {
         $order = $this->orderRepository->get($orderId);                                                                                          
    	/* $expirytime = 5000*60;
    	 $subtracttime = time() - strtotime($order->getCreatedAt());
    	 if($subtracttime >= $expirytime){
    	 	return false;
    	 }else{
    	 	return true;
    	 } */
         $cancellablestatus = $this->getcancellablestatus(); 
         if($order->getStatus() == $cancellablestatus){
             return true;
         }else{
            return false;
         } 
    }

    public function canCancellable($orderId){
         $order = $this->orderRepository->get($orderId);
        if (($order->getCustomerId() == $this->currentCustomer->getCustomerId()) && ($this->canCancel($orderId)))  {
            return true;
        }else{

        }
    }

    public function gettime($orderId){
    	 $order = $this->orderRepository->get($orderId);
    	 $expirytime = 30*60;
    	 $subtracttime = time() - strtotime($order->getCreatedAt());
    	 return $subtracttime;
    	
    }

	public function request($url, $postvars=null, $header=null, $getcode = null, $method = 'POST'){
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        if($method == 'POST'){
        curl_setopt($ch,CURLOPT_POST, 1);                
        curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (count($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch); 
        if ($response === false)
        {
            //Mage::log(curl_error($ch), null, 'zestmoney.log');
            $logger->info(curl_error($ch));
        }
        $info = curl_getinfo($ch);
        curl_close ($ch);
        if($getcode){
            $result = array('response' => json_decode($response), 'http_code' => $info['http_code']);
            return $result;
        }else{
            return json_decode($response);
        }
    }
}