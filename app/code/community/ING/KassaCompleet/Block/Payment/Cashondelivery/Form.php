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

class ING_KassaCompleet_Block_Payment_Cashondelivery_Form extends Mage_Payment_Block_Form
{
    /**
     * Sets Cashondelivery template into the checkout page
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('ing_kassacompleet/form/cashondelivery.phtml');
    }
}
