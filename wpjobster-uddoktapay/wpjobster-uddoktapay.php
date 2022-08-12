<?php

// Exit if the file is accessed directly
if (!defined('ABSPATH')) exit;

// Include plugin library
require_once(plugin_dir_path(__FILE__) . 'lib/uddoktapay.class.php');

if (!class_exists('WPJobster_UddoktaPay_Loader')) {

    class WPJobster_UddoktaPay_Loader
    {

        public function __construct($gateway = 'uddoktapay')
        {
            // Define gateway slug
            $this->unique_id = 'uddoktapay';

            // Add gateway to payment methods list
            add_filter('wpj_payment_gateways_filter', function ($payment_gateways_list) {
                $payment_gateways_list[$this->unique_id] = __('UddoktaPay', 'wpjobster');
                return $payment_gateways_list;
            }, 10, 1);

            // Add gateway option to admin
            add_filter('wpj_admin_settings_items_filter', function ($menu) {
                $menu['payment-gateways']['childs'][$this->unique_id] = array('order' => 1, 'path' => get_template_directory() . '/lib/plugins/wpjobster-uddoktapay/admin-fields.php');
                return $menu;
            }, 10, 1);

            // Add gateway to payment process flow
            add_action('wpjobster_taketo_' . $this->unique_id . '_gateway', array($this, 'initializePayment'), 10, 2);
            add_action('wpjobster_processafter_' . $this->unique_id . '_gateway', array($this, 'processPayment'), 10, 2);

            // Init payment library class
            UPAPI::init(wpj_get_option('wpjobster_uddoktapay_api_key'), wpj_get_option('wpjobster_uddoktapay_api_url'));
        }

        public static function init()
        {
            $class = __CLASS__;
            new $class;
        }

        public function initializePayment($payment_type, $order_details)
        { // params from gateways/init.php
            // Payment ROW
            $payment_row = wpj_get_payment(array('payment_type_id' => $order_details['id'], 'payment_type' => $payment_type));

            // Callback URL
            $callback_url = get_bloginfo('url') . '/?payment_response=' . $this->unique_id . '&payment_id=' . $payment_row->id;

            $current_user = wp_get_current_user();

            $data = [
                'amount' => $payment_row->final_amount_exchanged,
                'full_name' => $current_user->display_name,
                'email' => $current_user->user_email,
                'metadata' => [
                    'item_name' => apply_filters('wpj_gateway_order_title_filter', wpj_get_title_by_payment_type($payment_type, $order_details), $order_details, $payment_type),
                    'order_id' => $order_details['id'],
                    'payment_id' => $payment_row->id,
                    'payment_type'  => $payment_type
                ],
                'redirect_url' => $callback_url . '&action=paid',
                'cancel_url' => $callback_url . '&action=cancelled',
                'webhook_url' => $callback_url . '&action=ipn',
            ];

            $response = UPAPI::create_payment($data);
            if (!isset($response)) {
                die("API Key or Api URL is invalid");
            }

            if (isset($response['status'])) {
                header("Location:" . $response['payment_url']);
            }
            die($response['message']);
        }

        public function processPayment($payment_type, $payment_type_class)
        { // params from gateways/init.php
            if (isset($_REQUEST['invoice_id'])) {
                $response = UPAPI::execute_payment_v2();

                $payment_id       = $response['metadata']['payment_id'];
                $payment_row      = wpj_get_payment(array('id' => $payment_id));
                $order_id         = $payment_row->payment_type_id;
                $payment_type     = $payment_row->payment_type;
                $order            = wpj_get_order_by_payment_type($payment_type, $order_id);

                $payment_response = json_encode($response);
                $response_decoded = json_decode($payment_response);
                $payment_status   = isset($response_decoded->status) ? strtolower($response_decoded->status) : '';
                $payment_details  = $response_decoded->transaction_id;

                // Save response
                if ($response_decoded->status === "COMPLETED") {
                    $webhook = wpj_save_webhook(array(
                        'webhook_id'       => $response_decoded->invoice_id,
                        'payment_id'       => $payment_id,
                        'status'           => $payment_status,
                        'type'             => WPJ_Form::get('action') . ' ' . $response_decoded->metadata->payment_type,
                        'description'      => $response_decoded->metadata->item_name,
                        'amount'           => $response_decoded->amount,
                        'amount_currency'  => 'BDT',
                        'fees'             => 0,
                        'fees_currency'    => 'BDT',
                        'create_time'      => isset($response_decoded->date) ? strtotime($response_decoded->date) : current_time('timestamp', 1),
                        'payment_response' => $payment_response,
                        'payment_type'     => $payment_type,
                        'order_id'         => $order_id
                    ));

                    if (WPJ_Form::get('action') == 'paid' && $order->payment_status != 'completed') { // mark order as paid
                        do_action("wpjobster_" . $payment_type . "_payment_success", $order_id, $this->unique_id, $payment_details, $payment_response);
                    }
                }

                if (WPJ_Form::get('action') == 'cancelled' && $order->payment_status != 'cancelled') { // mark order as cancelled
                    do_action("wpjobster_" . $payment_type . "_payment_failed", $order_id, $this->unique_id, 'Buyer clicked cancel', $payment_response);
                }
            } elseif(WPJ_Form::get('action') == 'ipn') {
                $response = UPAPI::execute_payment();
                $payment_id         = $response['metadata']['payment_id'];
                $payment_status     = $response['status'];
                $payment_row        = wpj_get_payment(array('id' => $payment_id));
                $order_id           = $payment_row->payment_type_id;
                $payment_type       = $payment_row->payment_type;
                $payment_response   = json_encode($response);
                $payment_details    = $response['transaction_id'];
                if (isset($response) && $response['status'] === "COMPLETED") {
                    do_action("wpjobster_" . $payment_type . "_payment_success", $order_id, $this->unique_id, $payment_details, $payment_response);
                }
            }
        }
    } // END CLASS

} // END IF CLASS EXIST

add_action('after_setup_theme', array('WPJobster_UddoktaPay_Loader', 'init'));