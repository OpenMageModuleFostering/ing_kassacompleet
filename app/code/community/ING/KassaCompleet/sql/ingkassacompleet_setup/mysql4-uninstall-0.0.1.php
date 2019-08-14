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
 * @author      Ginger B.V. (info@gingerpayments.com)
 * @version     v1.0.3
 * @copyright   COPYRIGHT (C) 2015 GINGER PAYMENTS B.V. (https://www.gingerpayments.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php  Open Software License (OSL 3.0)
 *
 **/

/** @var $this Mage_Catalog_Model_Resource_Setup */
$this->startSetup();

$this->run(
    sprintf("DROP TABLE IF EXISTS `%s`",
        $this->getTable('ingkassacompleet_payments')
    )
);

$this->run("DELETE FROM `{$this->getTable('core_config_data')}` where `path` = 'ingkassacompleet/ideal/active';
    DELETE FROM `{$this->getTable('core_config_data')}` where `path` = 'ingkassacompleet/ideal/description';
    DELETE FROM `{$this->getTable('core_config_data')}` where `path` = 'ingkassacompleet/settings/apikey';
    DELETE FROM `{$this->getTable('core_resource')}` where `code` = 'ingkassacompleet_setup';"
);

$this->endSetup();
