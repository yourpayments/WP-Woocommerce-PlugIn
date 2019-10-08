<?php

    namespace PayU;

    /**
     * Class Configuration
     * @package PayU
     */
    class Configuration
    {

        /**
         * Настройки
         */
        public static function main()
        {

            /** @var array $country */
            $country = [
                'https://secure.payu.ua/order/lu.php' => 'Украина',
                'https://secure.payu.ru/order/lu.php' => 'Россия'
            ];

            /** @var array $languages */
            $languages = [
                'RU' => 'Русский',
                'EN' => 'Английский'
            ];

            /** @var array $params */
            $params = [
                'payment_payu_ipn_url' => [
                    'title' => __( 'Ссылка для IPN', 'woocommerce' ),
                    'type' => 'title',
                    'description' => 'Установите такую ссылку IPN : <b>'.site_url('/?payu_payment_result=result').'</b>',
                ],

                'payment_payu_title' => [
                    'title' => __( 'Название', 'woocommerce' ),
                    'type' => 'text',
                    'description' => __( 'Такоей название будет отображаться в корзине.', 'woocommerce' ),
                    'default' => __( 'PayU', 'woocommerce' ),
                ],

                'payment_payu_description' => [
                    'title' => __( 'Описание', 'woocommerce' ),
                    'type' => 'textarea',
                    'description' => __( 'Такое описание будет под названием способа оплаты.', 'woocommerce' ),
                    'default' => __( 'Оплата через платежный шлюз PayU<a target="_blank" href="payu.ru">payu.ru</a>', 'woocommerce' )
                ],

                'payment_payu_Merchant_ops' => [
                    'title' => __( 'Настройки мерчанта', 'woocommerce' ),
                    'type' => 'title',
                    'description' => '',
                ],

                'payment_payu_merchant' => [
                    'title' => __( 'Идентификатор мерчанта', 'woocommerce' ),
                    'type' 			=> 'text',
                    'description' => __( 'Идентификатор мерчанта в системе PayU.', 'woocommerce' ),
                    'default' => '',
                ],

                'payment_payu_secret_key' => [
                    'title' => __( 'Секретный ключ', 'woocommerce' ),
                    'type' 			=> 'text',
                    'description' => __( 'Секретный ключ системы PayU.', 'woocommerce' ),
                    'default' => '',
                ],

                'payment_payu_country' => [
                    'title' => __( 'Страна мерчанта', 'woocommerce' ),
                    'type' => 'select',
                    'description' => __( 'Выберите страну, в которой зарегистрирован мерчант PayU.', 'woocommerce' ),
                    'default' => 'https://secure.payu.ru/order/lu.php',
                    'options' => $country,
                ],

                'payment_payu_debug' => [
                    'title' => __( 'Режим отладки', 'woocommerce' ),
                    'type' => 'select',
                    'label' => __( 'Включить режим отладки', 'woocommerce' ),
                    'default' => 'no',
                    'description' => __( 'При включеном режиме все транзакции будут тестовыми', 'woocommerce' ),
                    'options' => [
                        'no' => 'Нет',
                        'yes' => 'Да',
                    ]
                ],

                'payment_payu_language' => [
                    'title' => __( 'Язык страницы оплаты', 'woocommerce' ),
                    'type' => 'select',
                    'description' => __( 'Выберите язык для старницы оплаты.', 'woocommerce' ),
                    'default' => 'RU',
                    'options' => $languages
                ],

                'payment_payu_Optional' => [
                    'title' => __( 'Опциональные настройки', 'woocommerce' ),
                    'type' => 'title',
                    'description' => '',
                ],

                'payment_payu_VAT' => [
                    'title' => __( 'Ставка НДС', 'woocommerce' ),
                    'type' => 'text',
                    'description' => '0 - для того, чтобы не учитывать НДС в стоимости',
                    'default' => '0',
                ],

                'payment_payu_backref' => [
                    'title' => __( 'Ссылка для возврата клиента', 'woocommerce' ),
                    'type' => 'text',
                    'label' => __( 'Ссылка, по которой клиент вернется после оплаты.', 'woocommerce' ),
                    'description' => '',
                    'default' => 'no'
                ]
            ];

            foreach($params as $id => $param)
                $params[$id]['value'] = get_option($id);

            require_once __DIR__ .'/../../templates/ConfigurationMain.php';
        }
    }