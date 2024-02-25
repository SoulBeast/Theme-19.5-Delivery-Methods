<?php

namespace Perspective\CustomCartProductShipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;

/* Way through sessions */
/* Set this method to be available only when the Company field is filled in */
/* use Magento\Checkout\Model\Session as CheckoutSession; */

/* Enable delivery method availability, only for orders with the Social attribute. */
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Cart;

/**
 * Custom shipping model
 */
class CustomCartProductShipping extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'customcartproductshipping';

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
    /* protected $checkoutSession; */

    /* Enable delivery method availability, only for orders with the Social attribute. */
    protected $productRepository;
    protected $cart;

    protected $order;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Quote\Model\Quote\Address\RateRequest $request,

        /* Way through sessions */
        /* CheckoutSession $checkoutSession, */

        /* Enable delivery method availability, only for orders with the Social attribute. */
        ProductRepository $productRepository,
        Cart $cart,

        \Magento\Sales\Model\Order\Address $order,

        array $data = []
    ) {
        /* Way through sessions */
        /* $this->checkoutSession = $checkoutSession; */

        /* Enable delivery method availability, only for orders with the Social attribute. */
        $this->productRepository = $productRepository;
        $this->cart = $cart;

        $this->order = $order;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->request = $request;
    }

    /* Way through sessions */
    /* public function getCompanyInfo() 
    { 
        $quote = $this->checkoutSession->getQuote(); 
        $shippingAddress = $quote->getShippingAddress(); 
        return $shippingAddress->getCompany();
    } */

    public function getSocialAttributeValuesInCart()
    {   
        $socialAttributes = [];
        // Получаем все элементы корзины
        $quote = $this->cart->getQuote();
        $quoteItems = $quote->getAllVisibleItems();
        foreach ($quoteItems as $item) {
            $productId = $item->getProduct()->getId();
            // Получаем объект продукта по его ID
            $product = $this->productRepository->getById($productId);
            // Получаем объект атрибута "social"
            $socialAttribute = $product->getCustomAttribute('social_attribute');
            if (!$socialAttribute)
            {
                return 0;
            }
            else {
                $socialAttributeValue = $socialAttribute->getValue();
                $socialAttributes[$productId] = $socialAttributeValue;
            }
        }
        return $socialAttributes;
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

        if ($countriesWithDiscont && in_array($country_id, explode(',', $countriesWithDiscont))) {
            $shippingCost = $shippingCost * $this->getConfigData('discount_percentage') / 100;
        }

        $qty = $request->getPackageQty();

        /* Code for get the Card price */
        /* $priceAllItem = $request->getPackagePhysicalValue(); */

        /* Way through sessions */
        /* $company = $this->getCompanyInfo(); */

        /* 50% discount */
        if ($qty >= 3 && $qty <= 5) {
            $shippingCost = $shippingCost * 0.5;
        }

        /* 80% discount */
        if ($qty >= 6 && $qty <= 10) {
            $shippingCost = $shippingCost * 0.2;
        }

        /* 100% discount */
        if ($qty > 10) {
            $shippingCost = $shippingCost * 0;
        }

        /* If the CustomCartProductShipping price is less than 2$ */
        /* if ($shippingCost < 2)
        {
            $shippingCost = 2;
        } */

        $method->setPrice($shippingCost);
        $method->setCost($shippingCost);
        $result->append($method);

        $socialValue = $this->getSocialAttributeValuesInCart();

        if (!$socialValue) {
            return false;
        }
        else {
            return $result;
        }        
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}
