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

class ING_KassaCompleet_Helper_Creditcard extends Mage_Core_Helper_Abstract
{
    protected $orderId      = null;
    protected $amount       = 0;
    protected $description  = null;
    protected $returnUrl    = null;
    protected $paymentUrl   = null;
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
     * @param int $orderId
     * @param float $amount
     * @param string $description
     * @param string $returnUrl
     * @return bool
     */
    public function createOrder($orderId, $amount, $description, $returnUrl)
    {
        if (!$this->setOrderId($orderId) ||
            !$this->setAmount($amount) ||
            !$this->setDescription($description) ||
            !$this->setReturnUrl($returnUrl)
            ) {

            $this->errorMessage = "Error in the given payment data";
            return false;
        }

        $order = $this->ingLib->ingCreateCreditCardOrder($orderId, $amount, $returnUrl, $description);
        Mage::log($order);

        $this->orderId    = (string) $order['id'];
        $this->paymentUrl = (string) $order['transactions'][0]['payment_url'];

        return true;
    }

    public function getOrderDetails($ingOrderId)
    {
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

    public function setReturnURL($returnUrl)
    {
        if (!preg_match('|(\w+)://([^/:]+)(:\d+)?(.*)|', $returnUrl)) {
            return false;
        }

        return ($this->returnUrl = $returnUrl);
    }

    public function getReturnURL()
    {
        return $this->returnUrl;
    }

    public function getPaymentURL()
    {
        return (string) $this->paymentUrl;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}