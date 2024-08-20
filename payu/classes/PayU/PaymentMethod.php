<?php

    namespace PayU;

    /**
     * Class PaymentMethod
     * @package PayU
     */
    class PaymentMethod extends \WC_Payment_Gateway
    {

        /** @var bool|PayU $payU */
        protected $payU;

        /**
         * PaymentMethod constructor.
         */
        public function __construct()
        {

            $this->id = 'payu_payment';

            $this->title = \get_option('payment_payu_title');
            $this->description = \get_option('payment_payu_description');

            $this->init_form_fields();
            $this->init_settings();

            \add_action(
                'woocommerce_api_wc_' . $this->id,
                [$this, 'checkIpnResponse']
            );

            \add_action(
                'woocommerce_receipt_' . $this->id,
                [$this, 'paymentForm']
            );

	        add_action(
	        	'woocommerce_order_status_cancelled',
		        [$this, 'cancelOrder']
	        );

	        $this->payU = PayU::getInst();
        }

        /**
         * init fields
         */
        public function init_form_fields()
        {
            $this->form_fields = [
                'enabled' => [
                    'title' => 'Включить/Выключить',
                    'type' => 'checkbox',
                    'label' => $this->title,
                    'default' => 'yes',
                ],
            ];
        }

        /**
         * @param $orderId
         */
        public function paymentForm($orderId)
        {

            /** @var bool|\WC_Order $order */
            $order = \wc_get_order($orderId);

            if(!$order instanceof \WC_Order)
                return;

            $this->createPaymentForm($order);
        }

        /**
         * @param \WC_Order $order
         */
        private function createPaymentForm(\WC_Order $order)
        {

            wp_enqueue_style(
                'payu_payment_admin_style_menu',
                plugin_dir_url(__DIR__) . '/../../assets/css/menu.css'
            );

            wp_enqueue_style(
                'payu_payment_admin_style_main',
                plugin_dir_url(__DIR__) . '/../../assets/css/main.css'
            );
            /** @var array $fields */
            $fields = $this->getPaymentFields($order);

            /** @var string $button */

            $button = '<div class="payment_btn__wrapper">
            <button class="payment_btn__link">
              <div class="payment_btn__logo">
                <svg xmlns="http://www.w3.org/2000/svg" 
                     width="70" 
                     height="70" 
                     viewBox="0 0 160 162"
                     style="
                            display: inline;
                            padding-right: 16px;
                            float: left;
                            "
                         ><style>
                    @keyframes a1_t { 0% { transform: translate(100px,43.74px) rotate(0deg); animation-timing-function: cubic-bezier(0,0,.58,1); } 16.625% { transform: translate(100px,43.74px) rotate(-15deg); } 100% { transform: translate(100px,43.74px) rotate(0deg); } }
                                @keyframes a0_t { 0% { transform: scale(1,1) translate(-126.64px,-77.99px); animation-timing-function: cubic-bezier(0,0,.58,1); } 16.625% { transform: scale(1.2,1.2) translate(-126.64px,-77.99px); animation-timing-function: cubic-bezier(.42,0,1,1); } 100% { transform: scale(1,1) translate(-126.64px,-77.99px); } }
                                @keyframes a3_t { 0% { transform: translate(100px,43.74px) rotate(0deg); animation-timing-function: cubic-bezier(0,0,.58,1); } 33.3% { transform: translate(100px,43.74px) rotate(-15deg); } 100% { transform: translate(100px,43.74px) rotate(0deg); } }
                                @keyframes a2_t { 0% { transform: scale(1,1) translate(-126.64px,-77.99px); animation-timing-function: cubic-bezier(0,0,.58,1); } 33.3% { transform: scale(1.2,1.2) translate(-126.64px,-77.99px); animation-timing-function: cubic-bezier(.42,0,1,1); } 100% { transform: scale(1,1) translate(-126.64px,-77.99px); } }
                                @keyframes a5_t { 0% { transform: translate(100px,43.74px) rotate(0deg); animation-timing-function: cubic-bezier(0,0,.58,1); } 50% { transform: translate(100px,43.74px) rotate(-15deg); } 100% { transform: translate(100px,43.74px) rotate(0deg); } }
                                @keyframes a4_t { 0% { transform: scale(1,1) translate(-126.64px,-77.99px); animation-timing-function: cubic-bezier(0,0,.58,1); } 50% { transform: scale(1.15,1.15) translate(-126.64px,-77.99px); animation-timing-function: cubic-bezier(.42,0,1,1); } 100% { transform: scale(1,1) translate(-126.64px,-77.99px); } }
                                @keyframes a7_t { 0% { transform: translate(100px,43.74px) rotate(0deg); animation-timing-function: cubic-bezier(0,0,.58,1); } 75% { transform: translate(100px,43.74px) rotate(-12deg); animation-timing-function: cubic-bezier(0,0,.58,1); } 100% { transform: translate(100px,43.74px) rotate(0deg); } }
                                @keyframes a6_t { 0% { transform: scale(1,1) translate(-126.64px,-77.99px); animation-timing-function: cubic-bezier(0,0,.58,1); } 75% { transform: scale(1.1,1.1) translate(-126.64px,-77.99px); animation-timing-function: cubic-bezier(.42,0,1,1); } 100% { transform: scale(1,1) translate(-126.64px,-77.99px); } }
                                </style><g fill="none" transform="translate(7.26,3.76)"><g style="animation: 4s linear infinite both a1_t;"><path d="M131.59 67.09c-9.79-7.14-35.57-2.34-39.37 12.79c-4.63 18.41 17.27 34.97 34.42 25.09c12.29-7.08 14.72-30.73 4.95-37.88Z" fill="#80f3ae" transform="translate(100,43.74) translate(-126.64,-77.99)" style="animation: 4s linear infinite both a0_t;"/></g><g style="animation: 4s linear infinite both a3_t;"><path fill-rule="evenodd" clip-rule="evenodd" d="M141.97 78.4c-1.01-5.78-3.36-10.6-6.98-13.72c-7.43-6.39-20.66-7.64-33.03-4.13c-12.36 3.51-22.48 11.36-24.53 21.57c-2.46 12.22 .86 23.11 8.04 30.52c7.17 7.4 18.43 11.56 32.31 9.78c11.45-1.92 19.75-12.66 23.13-25.4c1.67-6.3 2.06-12.85 1.06-18.62Zm4.08 19.98c-3.65 13.73-13.03 26.9-27.45 29.28l-0.04 .01l-0.05 .01c-15.35 1.98-28.36-2.58-36.85-11.34c-8.49-8.77-12.21-21.48-9.44-35.27c2.63-13.04 15.08-21.88 28.29-25.63c13.22-3.75 28.6-2.83 37.95 5.22c4.81 4.15 7.59 10.23 8.74 16.83c1.15 6.62 .69 13.95-1.15 20.89Z" fill="#65d988" transform="translate(100,43.74) translate(-126.64,-77.99)" style="animation: 4s linear infinite both a2_t;"/></g><g style="animation: 4s linear infinite both a5_t;"><path fill-rule="evenodd" clip-rule="evenodd" d="M149.39 108.93c6.2-19.26 4.28-40.16-8.67-51.67c-9.52-8.46-27.12-10.8-44.45-6.22c-17.21 4.56-33.25 15.73-39.76 33.1c-5.38 14.34-0.85 31.2 10.01 43.98c10.84 12.74 27.65 21.02 46.21 18.44c16.64-2.3 30.46-18.37 36.66-37.63Zm5.06 1.62c-6.5 20.18-21.45 38.56-40.99 41.28c-20.65 2.86-39.18-6.39-50.98-20.27c-11.77-13.84-17.17-32.69-10.95-49.29c7.3-19.43 25.04-31.51 43.38-36.36c18.23-4.82 37.96-2.73 49.33 7.38c15.22 13.53 16.7 37.08 10.21 57.26Z" fill="#4fc694" transform="translate(100,43.74) translate(-126.64,-77.99)" style="animation: 4s linear infinite both a4_t;"/></g><g style="animation: 4s linear infinite both a7_t;"><path fill-rule="evenodd" clip-rule="evenodd" d="M161.81 78.82c-1.86-11.83-6.31-21.63-13.19-27.62c-14.81-12.89-40.55-14.61-64.49-7.42c-23.92 7.18-44.72 22.87-50.15 43.16c-6.14 22.97 2.18 45.73 17.83 61.91c15.68 16.19 38.43 25.49 60.7 21.76c24.19-4.05 40.57-27.22 47.14-53.46c3.26-13.02 4.02-26.54 2.16-38.33Zm2.99 39.62c-6.78 27.08-24.14 52.84-51.41 57.41c-24.28 4.07-48.73-6.09-65.39-23.31c-16.69-17.23-25.86-41.86-19.15-66.97c6.11-22.84 28.99-39.44 53.75-46.88c24.74-7.43 52.71-6.12 69.51 8.5c8.15 7.1 12.97 18.27 14.95 30.8c1.98 12.58 1.16 26.82-2.26 40.45Z" fill="#48a992" transform="translate(100,43.74) translate(-126.64,-77.99)" style="animation: 4s linear infinite both a6_t;"/></g></g>
                            </svg>
              </div>
              <div class="payment_btn__sign">
                    '. __( 'Оплатить через <span style="white-space:nowrap">«Твои Платежи»</span>', 'woocommerce' ) .'
              </div></button><br><br>'
            . ' <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__( 'Отменить заказ и вернуться в корзину', 'woocommerce' ).'</a>';


            /** @var array $option */
            $option = [
                'merchant' => $fields['MERCHANT'],
                'secretkey' => $fields['SECRET_KEY'],
                'debug' => $fields['DEBUG'],
                'luUrl' => $fields['LuUrl'],
                'button' => $button
            ];

            echo $this->payU
                ->setOptions($option)
                ->setData($fields['Payu_data'])
                ->LU()
            ;
        }

        /**
         * Get PayU Args for passing to PP
         *
         * @access public
         * @param mixed $order
         * @return array
         */
        function getPaymentFields(\WC_Order $order)
        {

            /** @var array $fields */
            $fields = [
                'MERCHANT' => \get_option('payment_payu_merchant'),
                'SECRET_KEY' => \get_option('payment_payu_secret_key'),
                'DEBUG' => \get_option( 'payment_payu_debug' ) === "yes" ? 1 : 0,
                'LuUrl' => \get_option("payment_payu_country"),
                'no_note' => 1,
                'currency_code' => \get_woocommerce_currency(),
                'charset' => 'UTF-8',
                'rm' => \is_ssl() ? 2 : 1,
                'upload' => 1,

            ];

            /** @var double $shipping_total */
            $shipping_total = 0;
            foreach ($order->get_shipping_methods() as $shipping )
                $shipping_total += $shipping['cost'];

            /** @var array $billing */
            $billing = array(
                'BILL_FNAME' => $order->get_billing_first_name(),
                'BILL_LNAME' => $order->get_billing_last_name(),
                'BILL_ADDRESS' => $order->get_billing_address_1(),
                'BILL_ADDRESS2' => $order->get_billing_address_2(),
                'BILL_CITY' => $order->get_billing_city(),
                'BILL_PHONE' => $order->get_billing_phone(),
                'BILL_EMAIL' => $order->get_billing_email(),
                'BILL_COUNTRYCODE' => $order->get_billing_country(),
                'BILL_ZIPCODE' => $order->get_billing_postcode(),
                'LANGUAGE' => \get_option( 'payment_payu_language' ),
                'ORDER_SHIPPING' => \number_format( $shipping_total + $order->get_shipping_tax() , 2, '.', '' ),#$order->get_shipping(),
                'PRICES_CURRENCY' => \get_woocommerce_currency(),
                'ORDER_REF' => $order->get_id()
            );

            /** @var array $delivery */
            $delivery = array(
                'DELIVERY_FNAME' => $order->get_shipping_first_name(),
                'DELIVERY_LNAME' => $order->get_shipping_last_name(),
                'DELIVERY_ADDRESS' => $order->get_shipping_address_1(),
                'DELIVERY_ADDRESS2' => $order->get_shipping_address_2(),
                'DELIVERY_CITY' => $order->get_shipping_city(),
                'DELIVERY_PHONE' => $order->get_billing_phone(),
                'DELIVERY_EMAIL' => $order->get_billing_email(),
                'DELIVERY_COUNTRYCODE' => $order->get_shipping_country(),
                'DELIVERY_ZIPCODE' => $order->get_shipping_postcode(),
            );

            /** @var array $orderFields */
            $orderFields = \array_merge( $billing, $delivery );

            if (\get_option('payment_payu_backref') !== '' && \get_option('payment_payu_backref') !== 'no')
                $orderFields['BACK_REF'] = \get_option( "payment_payu_backref" );


            if(\count($order->get_items()) > 0)
            {

                /** @var \WC_Order_Item $item */
                foreach($order->get_items() as $item)
                {

                    $orderFields['ORDER_PNAME'][] = $item['name'];
                    $orderFields['ORDER_QTY'][] = $item['qty'];
                    $orderFields['ORDER_PRICE'][] = $order->get_item_total($item);
                    $orderFields['ORDER_PCODE'][] = $item['product_id'];
                    $orderFields['ORDER_VAT'][] = \get_option('payment_payu_VAT');
                    $orderFields['ORDER_PRICE_TYPE'][] = 'GROSS';
                }
            }

            $orderFields['DISCOUNT'] = (\defined('WC_VERSION') && \version_compare(WC_VERSION, '2.3', '<'))
                ? $discount = $order->get_order_discount()
                : $discount = $order->get_total_discount();

            $fields['Payu_data'] = $orderFields;

            return $fields;
        }

        /**
         * По идее - выполняем процесс оплаты и получаем результат
         *
         * @param int $orderId
         *
         * @return array
         */
        public function process_payment($orderId)
        {

            /** @var bool|\WC_Order $order */
            $order = \wc_get_order($orderId);

            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }


        /**
         * Check PayU IPN validity
         **/
        public static function checkIpnRequestIsValid()
        {

            /** @var int $debug */
            $debug = \get_option('debug') === 'yes' ? 1 : 0;

            /** @var array $options */
            $options = [
                'merchant' => \get_option('payment_payu_merchant'),
                'secretkey' => \get_option('payment_payu_secret_key'),
                'debug' => $debug,
            ];

            $payAnswer = PayU::getInst()
                ->setOptions($options)
                ->IPN();

            if (
                $_POST['ORDERSTATUS'] !== "COMPLETE"
                && (
                    $debug == 1
                    &&  $_POST['ORDERSTATUS'] !== "TEST"
                )
            )
                return false;

            return $payAnswer;
        }

        /**
         * @return bool
         */
        public static function checkIpnRequest()
        {

            /** @var bool $isIpnRequest */
            $isIpnRequest = true;

            /** @var array $fields */
            $fields = ['IPN_PID', 'IPN_PNAME', 'IPN_DATE', 'ORDERSTATUS'];

            foreach ($fields as $field)
                if(empty($_POST[$field]))
                    $isIpnRequest = false;

            return $isIpnRequest;
        }

        /**
         * Check for PayU IPN Response
         *
         * @access public
         * @return void
         */
	    public static function checkIpnResponse()
        {

            if(!self::checkIpnRequest())
                return;

            @\ob_clean();

            if(
                !empty($_POST)
                && $response = self::checkIpnRequestIsValid()
            )
            {

                \header( 'HTTP/1.1 200 OK' );
                \do_action( "valid-payu-standard-ipn-request", $_POST );

                if ($response)
                {

                    echo $response;
                    @ob_flush();
                }
            }
            else
            {
                \wp_die( "PayU IPN Request Failure" );
            }
        }

        /**
         * Successful Payment!
         *
         * @access public
         * @param array $posted
         * @return void
         */
        public function successfulRequest($posted)
        {

            /** @var \WC_Order $order */
            $order = new \WC_Order($_POST['REFNOEXT']);

            /** @var int $debug */
            $debug = \get_option('debug') === 'yes' ? 1 : 0;

            if($debug == 1 && $posted['ORDERSTATUS'] == 'TEST')
            {
                \update_post_meta(
                    $order->get_id(),
                    'Тип транзакции',
                    $posted['ORDERSTATUS']
                );
            }

            if($order->get_status() == 'completed')
            {
                exit;
            }

            if($order->get_total() != $posted['IPN_TOTALGENERAL'])
            {
                $order->update_status(
                    'on-hold',
                    sprintf(
                        __('Ошибка валидации: сумма оплаты не совпадает (сумма PayU : %s).', 'woocommerce'),
                        $posted['IPN_TOTALGENERAL']
                    )
                );
                exit;
            }

            if(!empty($posted['payer_email']))
                \update_post_meta($order->get_id(), 'Адрес плательщики', $posted['payer_email']);

            if(!empty($posted['REFNO']))
                \update_post_meta($order->get_id(), 'ID транзакции', $posted['REFNO']);

            if(!empty($posted['FIRSTNAME']))
                \update_post_meta($order->get_id(), 'Имя плательщика', $posted['FIRSTNAME']);

            if(!empty($posted['LASTNAME']))
                \update_post_meta($order->get_id(), 'Фамилия плательщика', $posted['LASTNAME']);

            if(!empty($posted['PAYMETHOD']))
                \update_post_meta($order->get_id(), 'Платежная система', $posted['PAYMETHOD']);

            $order->add_order_note( __( 'IPN оплата завершена', 'woocommerce' ) );
            $order->payment_complete();
        }

	    /**
	     * IRN if Order is canceled
	     *
	     * @param $orderId
	     * @return bool|PayU
	     */
        public function cancelOrder($orderId)
        {

        	$order = new \WC_Order($orderId);

	        $response = $this->payU
		        ->setOptions(
			        [
				        'merchant' => \get_option('payment_payu_merchant'),
				        'secretkey' => \get_option('payment_payu_secret_key'),
			        ]
		        )
		        ->setData(
			        [
				        'ORDER_AMOUNT' => $order->get_total(),
				        'ORDER_CURRENCY' => $order->get_currency(),
				        'ORDER_REF' => get_post_meta($order->get_id(), 'ID транзакции', true)
			        ]
		        )
		        ->IRN();

	        \file_put_contents(
	        	__DIR__ . '/log.log',
		        \print_r($response, true),
		        FILE_APPEND
	        );

	        return $response;
        }
    }