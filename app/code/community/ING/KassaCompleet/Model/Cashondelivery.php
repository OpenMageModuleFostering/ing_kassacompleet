<?php
/**
 *   ╲          ╱
 * ╭──────────────╮  COPYRIGHT (C) 2015 GINGER PAYMENTS B.V.
 * │╭──╮      ╭──╮│
 * ││//│      │//││
 * │╰──╯      ╰──╯│
 * ╰──────────────╯
 *   ╭──────────╮    The MIT License (MIT)
 *   │ () () () │
 *
 * @category    ING
 * @package     ING_KassaCompleet
 * @author      Ginger Payments B.V. (info@gingerpayments.com)
 * @version     v1.0.3
 * @copyright   COPYRIGHT (C) 2015 GINGER PAYMENTS B.V. (https://www.gingerpayments.com)
 * @license     The MIT License (MIT)
 *
 **/

class ING_KassaCompleet_Model_Cashondelivery extends Mage_Payment_Model_Method_Abstract
{
    /**
     * @var ING_KassaCompleet_Helper_Cashondelivery
     */
    protected $_cashondelivery;

    protected $_code                    = 'ingkassacompleet_cashondelivery';
    protected $_formBlockType           = 'ingkassacompleet/payment_cashondelivery_form';
    protected $_infoBlockType           = 'ingkassacompleet/payment_cashondelivery_info';
    protected $_paymentMethod           = 'Cashondelivery';
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canUseCheckout          = true;
    protected $_canUseInternal          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = false;
    protected $_canCapture              = false;

    // Payment flags
    const PAYMENT_FLAG_PENDING          = "Payment is pending";
    const PAYMENT_FLAG_COMPLETED        = "Payment is completed";
    const PAYMENT_FLAG_CANCELLED        = "Payment is cancelled";
    const PAYMENT_FLAG_ERROR            = "Payment failed with an error";
    const PAYMENT_FLAG_FRAUD            = "Amounts don't match. Possible fraud";

    /**
     * Build constructor
     */
    public function __construct()
    {
        parent::_construct();
        $this->_cashondelivery = Mage::helper('ingkassacompleet/cashondelivery');
    }

    /**
     * @param Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (Mage::getStoreConfig('payment/ingkassacompleet/apikey', $quote ? $quote->getStoreId() : null)) {
            return parent::isAvailable($quote);
        }

        return false;
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->_getCheckout()->getQuote();
    }

    /**
     * Cashondelivery is only active if 'EURO' is currency
     *
     * @param string $currencyCode
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if ($currencyCode !== "EUR") {
            return false;
        }

        return parent::canUseForCurrency($currencyCode);
    }

    /**
     * @param null|int|Mage_Core_Model_Store $store
     * @return string
     */
    public function getMailingAddress($store = null)
    {
        /** @var $helper ING_KassaCompleet_Helper_Data */
        $helper = Mage::helper('ingkassacompleet');

        $paymentBlock  = '';
        $paymentBlock .= $helper->__('Amount:') .         ' ' . '%AMOUNT%' . PHP_EOL;

        return $paymentBlock;
    }

    /**
     * Redirects the client on click 'Place Order' to selected Cashondelivery bank
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl(
            'ingkassacompleet/cashondelivery/payment',
            array(
                '_secure' => true,
                '_query' => array(),
            )
        );
    }
}