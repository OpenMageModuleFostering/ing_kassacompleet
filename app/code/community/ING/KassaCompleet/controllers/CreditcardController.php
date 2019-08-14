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

class ING_KassaCompleet_CreditcardController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var ING_KassaCompleet_Helper_Creditcard
     */
    protected $_creditcard;

    /**
     * @var Varien_Db_Adapter_Pdo_Mysql
     */
    protected $_read;

    /**
     * @var Varien_Db_Adapter_Pdo_Mysql
     */
    protected $_write;

    /**
     * Get CreditCard core
     * Give $_write mage writing resource
     * Give $_read mage reading resource
     */
    public function _construct()
    {
        $this->_creditcard = Mage::helper('ingkassacompleet/creditcard');

        $this->_read  = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->_write = Mage::getSingleton('core/resource')->getConnection('core_write');

        parent::_construct();
    }

    /**
     * Gets the current checkout session with order information
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Creates the order and sets the redirect url
     */
    public function paymentAction()
    {
        // Load last order
        /** @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($this->_getCheckout()->last_real_order_id);

        try {
            // Assign required value's
            $amount      = $order->getGrandTotal();
            $orderId     = $order->getIncrementId();
            $description = str_replace('%', $orderId, Mage::getStoreConfig("payment/ingkassacompleet_creditcard/description", $order->getStoreId()));
            $returnUrl   = Mage::getUrl('ingkassacompleet/creditcard/return');

            if ($this->_creditcard->createOrder($orderId, $amount, $description, $returnUrl)) {
                if (!$order->getId()) {
                    Mage::log('Geen order voor verwerking gevonden');
                    Mage::throwException('Geen order voor verwerking gevonden');
                }

                // Creates transaction
                /** @var $payment Mage_Sales_Model_Order_Payment */
                $payment = $order->getPayment();

                if (!$payment->getId()) {
                    $payment = Mage::getModel('sales/order_payment')->setId(null);
                }

                //->setMethod('CreditCard')
                $payment->setIsTransactionClosed(false)
                    ->setIngOrderId($this->_creditcard->getOrderId())
                    ->setIngIdealIssuerId(null);

                // Sets the above transaction
                $order->setPayment($payment);

                $order->setIngOrderId($this->_creditcard->getOrderId())
                    ->setIngIdealIssuerId(null);
                $order->save();

                $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

                $pendingMessage = Mage::helper('ingkassacompleet')->__(ING_KassaCompleet_Model_Banktransfer::PAYMENT_FLAG_PENDING);
                if ($order->getData('ing_order_id')) {
                    $pendingMessage .=  '. ' . 'ING Order ID: ' . $order->getData('ing_order_id');
                }

                $order->setState(
                    Mage_Sales_Model_Order::STATE_PROCESSING,
                    Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                    $pendingMessage,
                    false
                );
                $order->save();

                Mage::log("Issuer url: " . $this->_creditcard->getPaymentUrl());
                $this->_redirectUrl($this->_creditcard->getPaymentUrl());
            }
        } catch (Exception $e) {
            Mage::log($e);
            Mage::throwException(
                "Could not start transaction. Contact the owner.<br />
                Error message: " . $this->_creditcard->getErrorMessage()
            );
        }
    }

    /**
     * Customer returning with an order_id
     * Depending on the order state redirected to the corresponding page
     */
    public function returnAction()
    {
        $orderId = Mage::app()->getRequest()->getParam('order_id');

        try {
            if (!empty($orderId)) {
                $ingOrderDetails = $this->_creditcard->getOrderDetails($orderId);

                $paymentStatus = isset($ingOrderDetails['status']) ? $ingOrderDetails['status'] : null;

                if ($paymentStatus == "completed") {
                    // Redirect to success page
                    $this->_redirect('checkout/onepage/success', array('_secure' => true));
                } else {
                    $this->_restoreCart();

                    // Redirect to failure page
                    $this->_redirect('checkout/onepage/failure', array('_secure' => true));
                }
            }
        } catch (Exception $e) {
            $this->_restoreCart();

            Mage::log($e);
            $this->_redirectUrl(Mage::getBaseUrl());
        }
    }

    protected function _restoreCart()
    {
        $session = Mage::getSingleton('checkout/session');
        $orderId = $session->getLastRealOrderId();
        if (!empty($orderId)) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        }
        $quoteId = $order->getQuoteId();

        $quote = Mage::getModel('sales/quote')->load($quoteId)->setIsActive(true)->save();

        Mage::getSingleton('checkout/session')->replaceQuote($quote);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param float $orderAmount
     */
    protected function _setAmountPaid(Mage_Sales_Model_Order $order, $orderAmount)
    {
        // set the amounts paid
        $currentBase  = Mage::app()->getStore()->getBaseCurrencyCode();
        $currentStore = Mage::app()->getStore()->getCurrentCurrencyCode();

        $amountBase  = Mage::helper('directory')->currencyConvert($orderAmount, 'EUR', $currentBase);
        $amountStore = Mage::helper('directory')->currencyConvert($orderAmount, 'EUR', $currentStore);

        $order->setBaseTotalPaid($amountBase);
        $order->setTotalPaid($amountStore);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param null|string $transactionId
     * @return bool
     */
    protected function _savePaidInvoice(Mage_Sales_Model_Order $order, $transactionId = null)
    {
        $invoice = $order->prepareInvoice()
            ->register()
            ->setTransactionId($transactionId)
            ->pay();

        Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        if (Mage::getStoreConfig("payment/ingkassacompleet_creditcard/send_invoice_mail", $order->getStoreId())) {
            $invoice->sendEmail();
        }

        return true;
    }

    /**
     * Format price with currency sign
     *
     * @param Mage_Sales_Model_Order $order
     * @param float $amount
     * @return string
     */
    protected function _formatPrice($order, $amount)
    {
        return $order->getBaseCurrency()->formatTxt($amount);
    }
}
