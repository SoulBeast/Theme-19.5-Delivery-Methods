<?php

namespace Perspective\FixShip\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;

/* Way through sessions */
/* Set this method to be available only when the Company field is filled in */
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Custom shipping model
 */
class FixShip extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'fixship';

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    private $rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    private $rateMethodFactory;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */

    private $request;

    /* Way through sessions */
    protected $checkoutSession;

    protected $order;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Quote\Model\Quote\Address\RateRequest $request,

        /* Way through sessions */
        CheckoutSession $checkoutSession, 

        \Magento\Sales\Model\Order\Address $order,

        array $data = []
    ) {
        /* Way through sessions */
        $this->checkoutSession = $checkoutSession;

        $this->order = $order;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->request = $request;

    }

    /* Way through sessions */
    public function getCompanyInfo() 
    { 
        $quote = $this->checkoutSession->getQuote(); 
        $shippingAddress = $quote->getShippingAddress(); 
        return $shippingAddress->getCompany(); 
    }

    /**
     * Custom Shipping Rates Collector
     *
     * @param RateRequest $request
     * @return \Magento\Shipping\Model\Rate\Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));

        $shippingCost = (float)$this->getConfigData('shipping_cost');

        $countriesWithDiscont = $this->getConfigData('discount_for_countries');
        $country_id = $request->getDestCountryId();

        if ($countriesWithDiscont && in_array($country_id, explode(',',$countriesWithDiscont))){
            $shippingCost = $shippingCost*$this->getConfigData('discount_percentage')/100;
        }

        $method->setPrice($shippingCost);
        $method->setCost($shippingCost);
        $result->append($method);

        $city = $request->getDestCity();

        $company = $this->getCompanyInfo();

        if (!$company) {
            return false;
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}