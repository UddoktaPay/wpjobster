<?php


/**
 * Class UddoktaPay API
 * 
 */


class UPAPI
{

    /**
     * @var string
     */
    private static $baseUrl;

    /**
     * @var string
     */
    private static $apiKey;

    public function __construct()
    {
        self::init();
    }

    /**
     * Init API
     */

    public static function init($api_key = null, $api_url = null)
    {
        if ($api_key != "" && $api_url != "") {
            self::$apiKey = $api_key;
            self::$baseUrl = $api_url;
        }
    }


    /**
     *
     * Define Payment && Create payment.
     *
     */
    public static function create_payment($data = [])
    {
        if (empty($data)) {
            return [
                'status'    => false,
                'message'   => 'Data can\'t be empty.'
            ];
        }

        // Setup request to send json via POST.
        $headers = [];
        $headers[] = "Content-Type: application/json";
        $headers[] = "RT-UDDOKTAPAY-API-KEY:" . self::$apiKey;

        // Contact UuddoktaPay Gateway and get URL data
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$baseUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);
        return $result;
    }

    /**
     *
     * Execute payment 
     *
     */
    public static function execute_payment()
    {

        $headerApi = isset($_SERVER['HTTP_RT_UDDOKTAPAY_API_KEY']) ? $_SERVER['HTTP_RT_UDDOKTAPAY_API_KEY'] : null;
        
        if ($headerApi == null) {
            return [
                'status'    => false,
                'message'   => 'Invalid API Key.'
            ];
        }

        $apiKey = trim(self::$apiKey);

        if ($headerApi != $apiKey) {
            return [
                'status'    => false,
                'message'   => 'Unauthorized Action.'
            ];
        }

        $response = strip_tags(trim(file_get_contents('php://input')));

        if (!empty($response)) {
            // Decode response data
            $data = json_decode($response, true);

            if (is_array($data)) {
                return $data;
            }
        }

        return [
            'status'    => false,
            'message'   => 'Invalid response from UddoktaPay API.'
        ];
    }

    /**
     *
     * Execute payment v2
     *
     */
    public static function execute_payment_v2()
    {
        if (!isset($_POST['invoice_id'])) {
            return [
                'status'    => false,
                'message'   => 'Invalid Response.'
            ];
        }


        // Generate API URL
        $invoice_id = strip_tags(trim($_POST['invoice_id']));
        $apiUrl = str_replace('api/checkout-v2', 'api/verify-payment/', self::$baseUrl);
        $verifyUrl = trim($apiUrl . $invoice_id);

        // Set data
        $data = [
            'invoice_id'    => $invoice_id
        ];

        // Setup request to send json via POST.
        $headers = [];
        $headers[] = "Content-Type: application/json";
        $headers[] = "RT-UDDOKTAPAY-API-KEY:" . self::$apiKey;

        // Contact UuddoktaPay Gateway and get URL data
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $verifyUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);

        if (is_array($result)) {
            return $result;
        }

        return [
            'status'    => false,
            'message'   => 'Invalid response from UddoktaPay API.'
        ];
    }
}
