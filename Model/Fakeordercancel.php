<?php
/**
 * Copyright © 2015 Alcatelcommerce. All rights reserved.
 */
namespace Alcatel\Poc\Model;

/**
 * Poc Config model
 */
class Fakeordercancel extends \Magento\Framework\DataObject
{

	/**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
	/**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface 
     */
    protected $_scopeConfig;
	/**
     * @var \Magento\Framework\App\Config\ValueInterface
     */
    protected $_backendModel;
	/**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;
	/**
     * @var \Magento\Framework\App\Config\ValueFactory
     */
    protected $_configValueFactory;
	/**
     * @var int $_storeId
     */
    protected $_storeId;
	/**
     * @var string $_storeCode
     */
    protected $_storeCode;

    public $cancellabletimepath = 'alcatel/ordersettings/cancellabletime';
    public $cancellablestatus = 'alcatel/ordersettings/cancellable_status';
    public $noncancellablestatus = 'alcatel/ordersettings/non_cancellable_status';

	/**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager,
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
     * @param \Magento\Framework\App\Config\ValueInterface $backendModel,
     * @param \Magento\Framework\DB\Transaction $transaction,
     * @param \Magento\Framework\App\Config\ValueFactory $configValueFactory,
     * @param array $data
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Config\ValueInterface $backendModel,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\App\Config\ValueFactory $configValueFactory,
        \Magento\Sales\Model\Order $order,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Alcatel\Poc\Helper\Data $alcatelhelper,
        array $data = []
    ) {
        parent::__construct($data);
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_backendModel = $backendModel;
        $this->_transaction = $transaction;
        $this->order = $order;
        $this->helper = $alcatelhelper;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_configValueFactory = $configValueFactory;
		$this->_storeId=(int)$this->_storeManager->getStore()->getId();
		$this->_storeCode=$this->_storeManager->getStore()->getCode();

          $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/alcatelpoc.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $this->mgrlog = $logger;
	}
	
	/**
	 * Function for getting Config value of current store
     * @param string $path,
     */
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
	
	/**
	 * Function for setting Config value of current store
     * @param string $path,
	 * @param string $value,
     */
	public function setCurrentStoreConfigValue($path,$value){
		$data = [
                    'path' => $path,
                    'scope' =>  'stores',
                    'scope_id' => $this->_storeId,
                    'scope_code' => $this->_storeCode,
                    'value' => $value,
                ];

		$this->_backendModel->addData($data);
		$this->_transaction->addObject($this->_backendModel);
		$this->_transaction->save();
	}

    public function execute(){
        $fakecancelstatus = $this->helper->getfakestatus(); 
        $fakecanceltime = $this->helper->getfakecanceltime(); 
        $toDate = date('Y-m-d H:i:s', time());
      //  $from = '-'.$fakecanceltime.' minutes';
        $from = '-5 day';
         $fromDate = date('Y-m-d H:i:s', strtotime($from)); 
        $orders = $this->_orderCollectionFactory->create()->addFieldToSelect('*')->addFieldToFilter('status', $fakecancelstatus)
        ->addFieldToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate)); 
        $orderdetails = array();
         foreach($orders as $orderdata){
             $orderdata->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
             $orderdata->setStatus('canceled');
             $orderdata->cancel()->save();
         } 
    }

    public function orderupdate(){
         $order = $this->order;
         $cancellablestatus = $this->helper->getfakestatus();  
         $order->load('000000009');
         $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true);
         $order->setStatus($cancellablestatus);
         // $order->setStatus('processing');
        // $order->setState(\Magento\Sales\Model\Order::STATE_NEW, true);S
         // $order->setStatus('Pending');
         $order->save();
    }

	
}
