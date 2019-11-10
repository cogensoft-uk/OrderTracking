<?php
namespace Cogensoft\OrderTracking\Block\Tracking;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\Request\Http;
use Cogensoft\OrderTracking\Helper\Tracking;

class Scripts extends Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Cogensoft\OrderTracking\Helper\Tracking
     */
    protected $helper;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

	/**
	 * @var \Magento\Framework\App\Request\Http
	 */
    protected $http;

    protected $lastOrderId;

	protected $order;

    protected $templateArray = [];

    /**
     * @param Context $context
     * @param \Cogensoft\OrderTracking\Helper\Tracking $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\App\Request\Http
     * @param array $data
     */
    public function __construct(
        Context $context,
	    Tracking $helper,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
	    Http $http,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->http = $http;
        parent::__construct($context, $data);

	    $this->lastOrderId = $this->checkoutSession->getLastOrderId();
	    if($this->lastOrderId) $this->getOrder();
    }

	/**®
	 * @return string
	 */
    public function getMarkup() {
	    $html = '';

	    foreach ($this->getFiles() as $file) {
		    $html .= $this->processTemplate(file_get_contents($file));
	    }

	    return $html;
    }

	protected function getFiles()
	{
		$allFiles = [];
		$rootFilePath = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, ['view', 'frontend', 'scripts']);
		$layout = $this->getLayoutName();

		foreach(['', DIRECTORY_SEPARATOR . $layout] AS $relativePath) {
			$directoryPath = $rootFilePath . $relativePath;
			if(!file_exists($directoryPath)) continue;
			$files = array_diff(scandir($directoryPath), array('.', '..'));
			foreach ($files as $file) {
				$fileFullPath = implode(DIRECTORY_SEPARATOR, [$directoryPath, $file]);
				if(is_dir($fileFullPath)) continue;

				$allFiles[] = $fileFullPath;
			}
		}

		return $allFiles;
	}

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    protected function getOrder()
    {
	    $this->order = $this->orderRepository->get($this->checkoutSession->getLastOrderId());
    }

    /**
     * @return array
     */
    protected function getTemplateArray()
    {
    	if(!$this->templateArray) {
		    if($this->order) {
			    $this->templateArray['ORDER_ID'] = $this->helper->formatOrderNumber($this->order->getIncrementId());
			    $this->templateArray['TOTAL'] = $this->helper->formatPrice($this->order->getBaseGrandTotal());
		        $this->templateArray['SUB_TOTAL'] = $this->helper->formatPrice($this->order->getBaseSubtotal());
		        $this->templateArray['SHIPPING'] = $this->helper->formatPrice($this->order->getBaseShippingAmount());
		        $this->templateArray['TAX'] = $this->helper->formatPrice($this->order->getTaxAmount());
		        $this->templateArray['COUPON_CODE'] = $this->order->getCouponCode() ?: '';
		        $this->templateArray['DISCOUNT'] = $this->helper->formatPrice($this->order->getDiscountAmount());
		    }
		    $this->templateArray['MERCHANT_ID'] = $this->helper->getGeneralConfig('merchant_id');
	    }

        return $this->templateArray;
    }

    /**
     * @param $string
     * @return mixed
     */
    protected function processTemplate($string)
    {
	    $templateArray = $this->getTemplateArray();

        foreach($templateArray AS $key => $value) {
	        $string = str_replace('{{'.$key.'}}', $value, $string);
        }

	    return $string;
    }

    protected function getLayoutName() {
    	return $this->http->getFullActionName();
    }
}
