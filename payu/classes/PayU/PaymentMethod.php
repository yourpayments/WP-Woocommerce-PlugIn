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

            /** @var array $fields */
            $fields = $this->getPaymentFields($order);

            /** @var string $button */
            $button = '<input type="submit" class="button alt" id="submit_payu_payment_form" value="' . __( 'Оплатить через PayU', 'woocommerce' ) . '" />'
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
                'BILL_FNAME' => $order->billing_first_name,
                'BILL_LNAME' => $order->billing_last_name,
                'BILL_ADDRESS' => $order->billing_address_1,
                'BILL_ADDRESS2' => $order->billing_address_2,
                'BILL_CITY' => $order->billing_city,
                'BILL_PHONE' => $order->billing_phone,
                'BILL_EMAIL' => $order->billing_email,
                'BILL_COUNTRYCODE' => $order->billing_country,
                'BILL_ZIPCODE' => $order->billing_postcode,
                'LANGUAGE' => \get_option( 'payment_payu_language' ),
                'ORDER_SHIPPING' => \number_format( $shipping_total + $order->get_shipping_tax() , 2, '.', '' ),#$order->get_shipping(),
                'PRICES_CURRENCY' => \get_woocommerce_currency(),
                'ORDER_REF' => $order->get_id()
            );

            /** @var array $delivery */
            $delivery = array(
                'DELIVERY_FNAME' => $order->shipping_first_name,
                'DELIVERY_LNAME' => $order->shipping_last_name,
                'DELIVERY_ADDRESS' => $order->shipping_address_1,
                'DELIVERY_ADDRESS2' => $order->shipping_address_2,
                'DELIVERY_CITY' => $order->shipping_city,
                'DELIVERY_PHONE' => $order->billing_phone,
                'DELIVERY_EMAIL' => $order->billing_email,
                'DELIVERY_COUNTRYCODE' => $order->shipping_country,
                'DELIVERY_ZIPCODE' => $order->shipping_postcode,
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
	    public function checkIpnResponse()
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