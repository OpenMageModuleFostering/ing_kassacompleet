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

class ING_KassaCompleet_Helper_Cashondelivery extends Mage_Core_Helper_Abstract
{
    const XML_PATH_EMAIL_TEMPLATE       = "payment/ingkassacompleet_cashondelivery/order_email_template";
    const XML_PATH_EMAIL_GUEST_TEMPLATE = "payment/ingkassacompleet_cashondelivery/order_email_template_guest";

    protected $orderId      = null;
    protected $amount       = 0;
    protected $description  = null;
    protected $orderStatus  = null;
    protected $consumerInfo = array();
    protected $errorMessage = '';
    protected $errorCode    = 0;
    protected $ingLib       = null;

    public function __construct()
    {
        $this->ingLib = new Ing_Services_Lib(Mage::getStoreConfig("payment/ingkassacompleet/apikey"), 'file', false);
    }

    /**
     * Prepare an order and get a redirect URL
     *
     * @param int    $orderId
     * @param float  $amount
     * @param string $description
     * @param array  $customer
     * @return bool
     */
    public function createOrder($orderId, $amount, $description, $customer = array())
    {
        if (!$this->setOrderId($orderId) ||
            !$this->setAmount($amount) ||
            !$this->setDescription($description)
            ) {

            $this->errorMessage = "Error in the given payment data";
            return false;
        }

        $ingOrder = $this->ingLib->ingCreateCashondeliveryOrder($orderId, $amount, $description, $customer);
        Mage::log($ingOrder);

        if (!is_array($ingOrder) || array_key_exists('error', $ingOrder)) {
            // TODO: handle the error
            return false;
        } else {
            $this->orderId = (string) $ingOrder['id'];
            return true;
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return string
     */
    public function getSuccessHtml(Mage_Sales_Model_Order $order)
    {
        if ($order->getPayment()->getMethodInstance() instanceof ING_KassaCompleet_Model_Cashondelivery) {
            $paymentBlock = $order->getPayment()->getMethodInstance()->getMailingAddress($order->getStoreId());

            $grandTotal   = $order->getGrandTotal();
            $currency     = Mage::app()->getLocale()->currency($order->getOrderCurrencyCode())->getSymbol();

            $amountStr    = $currency . ' ' . number_format(round($grandTotal, 2), 2, '.', '');;

            $paymentBlock = str_replace('%AMOUNT%', $amountStr, $paymentBlock);
            $paymentBlock = str_replace('\n', PHP_EOL, $paymentBlock);

            return $paymentBlock;
        }

        return '';
    }

    public function getOrderDetails($ingOrderId) {
        return $this->ingLib->getOrderDetails($ingOrderId);
    }

    public function setAmount($amount)
    {
        return ($this->amount = $amount);
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setOrderId($orderId)
    {
        return ($this->orderId = $orderId);
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function setDescription($description)
    {
        $description = substr($description, 0, 29);

        return ($this->description = $description);
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}