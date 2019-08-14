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

class ING_KassaCompleet_Helper_Ideal extends Mage_Core_Helper_Abstract
{
    protected $orderId      = null;
    protected $issuerId     = null;
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
     * Fetch the list of issuers
     *
     * @return null|array
     */
    public function getIssuers()
    {
        return $this->ingLib->ingGetIssuers();
    }

    /**
     * Prepare an order and get a redirect URL
     *
     * @param int $orderId
     * @param $issuerId
     * @param float $amount
     * @param string $description
     * @param string $returnUrl
     * @return bool
     */
    public function createOrder($orderId, $issuerId, $amount, $description, $returnUrl)
    {
        if (!$this->setOrderId($orderId) ||
            !$this->setIssuerId($issuerId) ||
            !$this->setAmount($amount) ||
            !$this->setDescription($description) ||
            !$this->setReturnUrl($returnUrl)
            ) {

            $this->errorMessage = "Error in the given payment data";
            return false;
        }

        $order = $this->ingLib->ingCreateIdealOrder($orderId, $amount, $issuerId, $returnUrl, $description);
        Mage::log($order);

        $this->orderId    = (string) $order['id'];
        $this->paymentUrl = (string) $order['transactions'][0]['payment_url'];

        return true;
    }

    public function getOrderDetails($ingOrderId)
    {
        return $this->ingLib->getOrderDetails($ingOrderId);
    }

    public function setIssuerId($issuerId)
    {
        return ($this->issuerId = $issuerId);
    }

    public function getIssuerId()
    {
        return $this->issuerId;
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