<?php
namespace Alcatel\Poc\Controller\Test;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
//use Magento\Framework\Controller\Result\JsonFactory;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
// use Magento\Framework\Setup\SchemaSetupInterface;


 
class Ordercron extends \Magento\Framework\App\Action\Action
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
      $this->manager = $this->_objectManager->get('Alcatel\Poc\Model\Manager');
      $response = $this->manager->refund2();
      echo '<pre>'; print_r($response); exit;
    }    
}