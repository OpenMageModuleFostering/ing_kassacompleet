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

class ING_KassaCompleet_Model_Banktransfer extends Mage_Payment_Model_Method_Abstract
{
    /**
     * @var ING_KassaCompleet_Helper_Banktransfer
     */
    protected $_banktransfer;

    protected $_code                    = 'ingkassacompleet_banktransfer';
    protected $_formBlockType           = 'ingkassacompleet/payment_banktransfer_form';
    protected $_infoBlockType           = 'ingkassacompleet/payment_banktransfer_info';
    protected $_paymentMethod           = 'Banktransfer';
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
        $this->_banktransfer = Mage::helper('ingkassacompleet/banktransfer');
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
     * Banktransfer is only active if 'EURO' is currency
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

    public function getMailingAddress($store = null)
    {
        /** @var $helper ING_KassaCompleet_Helper_Data */
        $helper = Mage::helper('ingkassacompleet');

        $accountDetails = Mage::getStoreConfig("payment/ingkassacompleet_banktransfer/account_details", $store);

        $paymentBlock = '';

        $paymentBlock .= $helper->__('Amount:') .         ' ' . '%AMOUNT%' . "\n";
        $paymentBlock .= $helper->__('Reference:') .      ' ' . '%REFERENCE%' . "\n";
        $paymentBlock .= $helper->__('IBAN:') .           ' ' . $accountDetails['iban'] . "\n";
        $paymentBlock .= $helper->__('BIC:') .            ' ' . $accountDetails['bic'] . "\n";
        $paymentBlock .= $helper->__('Account holder:') . ' ' . $accountDetails['holder'] . "\n";
        $paymentBlock .= $helper->__('City:') .           ' ' . $accountDetails['city'];

        return $paymentBlock;
    }

    /**
     * Redirects the client on click 'Place Order' to selected Banktransfer bank
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl(
            'ingkassacompleet/banktransfer/payment',
            array(
                '_secure' => true,
                '_query' => array(),
            )
        );
    }
}