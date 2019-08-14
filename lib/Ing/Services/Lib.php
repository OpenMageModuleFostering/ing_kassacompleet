<?php

class Ing_Services_Lib
{
    public $apiKey;
    public $apiEndpoint;
    public $apiVersion;

    public $logTo;
    public $logToFilename;

    public $debugMode;
    public $debugCurl;
    public $debugCurlFilename;

    public function __construct($apiKey, $logTo, $debugMode)
    {
        $this->apiKey      = $apiKey;
        $this->apiEndpoint = "https://api.kassacompleet.nl";
        $this->apiVersion  = "v1";

        $this->logTo         = $logTo;
        $this->logToFilename = 'inglog.log';

        $this->debugMode         = $debugMode;
        $this->debugCurl         = false;
        $this->debugCurlFilename = 'ingcurl.log';

        $this->plugin_version = 'magento-ce-1.0.3';
    }

    public function ingLog($contents)
    {
        if ($this->logTo == 'file') {
            // $file = dirname(__FILE__) . '/inglog.txt';
            // file_put_contents($file, date('Y-m-d H.i.s') . ": ", FILE_APPEND);

            if (is_array($contents)) {
                $contents = var_export($contents, true);
            } elseif (is_object($contents)) {
                $contents = json_encode($contents);
            }

            // file_put_contents($file, $contents . "\n", FILE_APPEND);

            Mage::log($contents, null, $this->logToFilename);
        } else {
            error_log($contents);
        }
    }

    public function performApiCall($api_method, $post = false)
    {
        $url = implode("/", array($this->apiEndpoint, $this->apiVersion, $api_method));

        $curl = curl_init($url);

        $length = 0;
        if ($post) {
            curl_setopt($curl, CURLOPT_POST, 1);
            // curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
            $length = strlen($post);
        }

        $request_headers = array(
            "Accept: application/json",
            "Content-Type: application/json",
            "User-Agent: gingerphplib",
            "X-Ginger-Client-Info: " . php_uname(),
            "Authorization: Basic " . base64_encode($this->apiKey . ":"),
            "Connection: close",
            "Content-length: " . $length,
        );

        curl_setopt($curl, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 2 = to check the existence of a common name and also verify that it matches the hostname provided. In production environments the value of this option should be kept at 2 (default value).
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);

        if ($this->debugCurl) {
            curl_setopt($curl, CURLOPT_VERBOSE, 1); // prevent caching issues
            // $file = dirname(__FILE__) . '/ingcurl.txt';
            // $handle = fopen($file, "a+");
            $handle = fopen('php://temp', 'w+');
            curl_setopt($curl, CURLOPT_STDERR, $handle); // prevent caching issues
        }

        $responseString = curl_exec($curl);

        if ($this->debugCurl) {
            rewind($handle);
            $curlLogText = stream_get_contents($handle);
            Mage::log($curlLogText, null, $this->debugCurlFilename);
        }

        if ($responseString == false) {
            $response = array('error' => curl_error($curl));
        } else {
            $response = json_decode($responseString, true);

            if (!$response) {
                $this->ingLog('invalid json: JSON error code: ' . json_last_error() . "\nRequest: " . $responseString);
                $response = array('error' =>  'Invalid JSON');
            }
        }

        curl_close($curl);

        return $response;
    }

    public function ingGetIssuers()
    {
        // API Request to ING to fetch the issuers
        return $this->performApiCall("ideal/issuers/");
    }

    public function ingCreateIdealOrder($orders_id, $total, $issuer_id, $return_url, $description)
    {
        $post = array(
            "type"              => "payment",
            "currency"          => "EUR",
            "amount"            => 100 * round($total, 2),
            "merchant_order_id" => (string)$orders_id,
            "description"       => $description,
            "return_url"        => $return_url,
            "transactions"      => array(
                array(
                    "payment_method"         => "ideal",
                    "payment_method_details" => array("issuer_id" => $issuer_id)
                )
            ),
            'extra' => array(
                'plugin' => $this->plugin_version,
            ),
        );

        $order = json_encode($post);
        $result = $this->performApiCall("orders/", $order);

        return $result;
    }

    public function ingCreateCreditCardOrder($orders_id, $total, $return_url, $description)
    {
        $post = array(
            "type"              => "payment",
            "currency"          => "EUR",
            "amount"            => 100 * round($total, 2),
            "merchant_order_id" => (string)$orders_id,
            "description"       => $description,
            "return_url"        => $return_url,
            "transactions"      => array(
                array(
                    "payment_method" => "credit-card",
                )
            ),
            'extra' => array(
                'plugin' => $this->plugin_version,
            ),
        );

        $order = json_encode($post);
        $result = $this->performApiCall("orders/", $order);

        return $result;
    }

    public function ingCreateBanktransferOrder($orders_id, $total, $description, $customer = array())
    {
        $post = array(
            "type"         => "payment",
            "currency"     => "EUR",
            "amount"       => 100 * round($total, 2),
            "description"  => $description,
            "transactions" => array(array(
                "payment_method" => "bank-transfer",
            )),
            "merchant_order_id" => (string)$orders_id,
            'customer' => array(
                'merchant_customer_id' => !empty($customer['merchant_customer_id']) ? $customer['merchant_customer_id'] : null,
                'email_address' => !empty($customer['email_address']) ? $customer['email_address'] : null,
                'first_name'    => !empty($customer['first_name']) ? $customer['first_name'] : null,
                'last_name'     => !empty($customer['last_name']) ? $customer['last_name'] : null,
                'address_type'  => !empty($customer['address_type']) ? $customer['address_type'] : null,
                'address'       => !empty($customer['address']) ? $customer['address'] : null,
                'postal_code'   => !empty($customer['postal_code']) ? $customer['postal_code'] : null,
                'housenumber'   => !empty($customer['housenumber']) ? $customer['housenumber'] : null,
                'country'       => !empty($customer['country']) ? $customer['country'] : null,
                'phone_numbers' => !empty($customer['phone_numbers']) ? array($customer['phone_numbers']) : null,
                'user_agent'    => !empty($customer['user_agent']) ? $customer['user_agent'] : null,
                'referrer'      => !empty($customer['referrer']) ? $customer['referrer'] : null,
                'ip_address'    => !empty($customer['ip_address']) ? $customer['ip_address'] : null,
                'forwarded_ip'  => !empty($customer['forwarded_ip']) ? $customer['forwarded_ip'] : null,
                'gender'        => !empty($customer['gender']) ? $customer['gender'] : null,
                'birth_date'    => !empty($customer['birth_date']) ? $customer['birth_date'] : null,
                'locale'        => !empty($customer['locale']) ? $customer['locale'] : null,
            ),
            'extra' => array(
                'plugin' => $this->plugin_version,
            ),
        );

        $order = json_encode($post);
        $result = $this->performApiCall("orders/", $order);

        return $result;
    }

    public function ingCreateCashondeliveryOrder($orders_id, $total, $description, $customer = array())
    {
        $post = array(
            "type"         => "payment",
            "currency"     => "EUR",
            "amount"       => 100 * round($total, 2),
            "description"  => $description,
            "transactions" => array(array(
                "payment_method" => "cash-on-delivery",
            )),
            "merchant_order_id" => (string)$orders_id,
            'customer' => array(
                'merchant_customer_id' => !empty($customer['merchant_customer_id']) ? $customer['merchant_customer_id'] : null,
                'email_address' => !empty($customer['email_address']) ? $customer['email_address'] : null,
                'first_name'    => !empty($customer['first_name']) ? $customer['first_name'] : null,
                'last_name'     => !empty($customer['last_name']) ? $customer['last_name'] : null,
                'address_type'  => !empty($customer['address_type']) ? $customer['address_type'] : null,
                'address'       => !empty($customer['address']) ? $customer['address'] : null,
                'postal_code'   => !empty($customer['postal_code']) ? $customer['postal_code'] : null,
                'housenumber'   => !empty($customer['housenumber']) ? $customer['housenumber'] : null,
                'country'       => !empty($customer['country']) ? $customer['country'] : null,
                'phone_numbers' => !empty($customer['phone_numbers']) ? array($customer['phone_numbers']) : null,
                'user_agent'    => !empty($customer['user_agent']) ? $customer['user_agent'] : null,
                'referrer'      => !empty($customer['referrer']) ? $customer['referrer'] : null,
                'ip_address'    => !empty($customer['ip_address']) ? $customer['ip_address'] : null,
                'forwarded_ip'  => !empty($customer['forwarded_ip']) ? $customer['forwarded_ip'] : null,
                'gender'        => !empty($customer['gender']) ? $customer['gender'] : null,
                'birth_date'    => !empty($customer['birth_date']) ? $customer['birth_date'] : null,
                'locale'        => !empty($customer['locale']) ? $customer['locale'] : null,
            ),
            'extra' => array(
                'plugin' => $this->plugin_version,
            ),
        );

        $order = json_encode($post);
        $result = $this->performApiCall("orders/", $order);

        return $result;
    }

    public function getOrderStatus($order_id)
    {
        $order = $this->performApiCall("orders/" . $order_id . "/");

        if (!is_array($order) || array_key_exists('error', $order)) {
            return 'error';
        }
        else {
            return $order['status'];
        }
    }

    public function getOrderDetails($order_id)
    {
        $order = $this->performApiCall("orders/" . $order_id . "/");

        if (!is_array($order) || array_key_exists('error', $order)) {
            return 'error';
        }
        else {
            return $order;
        }
    }
}
