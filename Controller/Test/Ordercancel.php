<?php
namespace Alcatel\Poc\Controller\Test;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
//use Magento\Framework\Controller\Result\JsonFactory;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
// use Magento\Framework\Setup\SchemaSetupInterface;


 
class Ordercancel extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;
    protected $urlBuilder;
    public function __construct(Context $context, PageFactory $pageFactory)
    { 
        
        $this->pageFactory = $pageFactory;
        parent::__construct($context);
    }
 
    public function execute()
    { 
      $request = $this->getRequest()->getParams();
      $orderid = $request['orderid'];
      $this->manager = $this->_objectManager->get('Alcatel\Poc\Model\Manager');
      $response = $this->manager->ordercancel($orderid);
      if($response=='success')
        $this->messageManager->addSuccess(__("Order Cancelled successfully"));
      else
        $this->messageManager->addError(__("Order cannot be cancelled"));
       return $this->resultRedirectFactory->create()->setPath('customer/account/', ['_current' => true]);
    }    
}