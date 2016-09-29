<?php
/**
 * Copyright © 2015 Alcatelcommerce. All rights reserved.
 */
namespace Alcatel\Poc\Model;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;

/**
 * Poc Config model
 */
class Manager extends \Magento\Framework\DataObject
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
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        Filesystem $filesystem,
        \Alcatel\Poc\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct($data);
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_backendModel = $backendModel;
        $this->_transaction = $transaction;
        $this->order = $order;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_fileFactory = $fileFactory;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->_configValueFactory = $configValueFactory;
		$this->_storeId=(int)$this->_storeManager->getStore()->getId();
		$this->_storeCode=$this->_storeManager->getStore()->getCode();

        $this->helper = $helper;
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/alcatelpoc.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $this->mgrlog = $logger;
	}

     protected function _gethelper(){
       return $this->helper;
     }


    public function orderupdate($orderid){
       $shippingurl = $this->_gethelper()->getshippingapi($orderid);
       $result =  $this->_gethelper()->request($shippingurl,$postvars=null, $header=null, $getcode = null, $method = 'GET');
       if(!empty($result)){
            if($result->status == 'success'){
                $order = $this->order;
                $order->loadByIncrementId($orderid);
                $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE, true);
                $order->setStatus($result->orderstatus);
                $order->save();
                $result = 'order updated successfully';
            }
            else{
                $error = $result->message;
                $this->mgrlog->info($error);
                $result = 'order not updated';
            }
       }else{
            $result = 'Error in response';
       } 
       return $result;
    }

    public function ordercancel($orderid){
         $order = $this->order;
         $order->load($orderid);
         if($order->getId() == $orderid){
            if($this->helper->canCancellable($orderid)){
                 $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true);
                 $order->setStatus('canceled');
                 $order->cancel()->save();
                 $result = 'success';
             }else{
                 $result = 'failed';
             }
         }else{
             $result = 'failed';
         }
         return $result;
    }

    public function orderexport(){ 
         $toDate = date('Y-m-d H:i:s', time());
         $fromDate = date('Y-m-d H:i:s', strtotime('-5 day')); 
         $headers = array('created_at','increment_id','grand_total');
         $noncancellablestatus = $this->helper->getnoncancellablestatus(); 

        // $orders = $this->_orderCollectionFactory->create()->addFieldToSelect('*')->addFieldToFilter('status','Pending');
        $orders = $this->_orderCollectionFactory->create()->addFieldToSelect('*')->addFieldToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate))->addFieldToFilter('status', $noncancellablestatus);
         $orderdetails = array();
         foreach($orders as $orderdata){
            $orderdetail['created_at'] = $orderdata->getData('increment_id');
            $orderdetail['increment_id'] = $orderdata->getData('increment_id');
            $orderdetail['grand_total'] = $orderdata->getData('grand_total');
            $orderdetails[] =  $orderdetail;
         }
       
         $exportfile = $this->getCsvdataFile($headers,$orders);
         //To download
        /* $fileName = 'test.csv';
         $this->_fileFactory->create(
            $fileName,
            $exportfile,
            DirectoryList::VAR_DIR
        ); */
        
        return $orderdetails; 
    }

     public function getCsvdataFile($headers,$orders)
    {

        $name = strtotime("now");
        $file = 'export/orderexport' . $name . '.csv';
        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();
        $stream->writeCsv($headers);
        foreach($orders as $orderdata){
            $orderdetail['created_at'] = $orderdata->getData('created_at');
            $orderdetail['increment_id'] = $orderdata->getData('increment_id');
            $orderdetail['grand_total'] = $orderdata->getData('grand_total');
            $stream->writeCsv($orderdetail);
         }
        $stream->unlock();
        $stream->close();
        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true  // can delete file after use
        ];
    }	
	
}
