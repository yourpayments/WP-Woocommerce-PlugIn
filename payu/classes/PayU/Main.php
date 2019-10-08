<?php

    namespace PayU;

    /**
     * Class Main
     * @package PayU
     */
    class Main
    {

        /**
         * @return bool
         */
        public static function menu()
        {

            /** @var array $items */
            $items = [
                [
                    'slug' => 'woocommerce',
                    'page_title' => 'Настройки PayU',
                    'menu_title' => 'Настройки PayU',
                    'capability' => 8,
                    'menu_slug' => 'payu_payment_main_settings',
                    'function' => [\PayU\Configuration::class, 'main'],
                ],
            ];

            foreach ($items as $item)
            {
                add_submenu_page(
                    $item['slug'],
                    $item['page_title'],
                    $item['menu_title'],
                    $item['capability'],
                    $item['menu_slug'],
                    $item['function']
                );
            }

            return true;
        }

        public static function install()
        {

        }

        /**
         * Добавление платежной системы
         */
        public function initPayment()
        {

            if (!defined('ABSPATH'))    exit;

            \add_filter(
                'woocommerce_payment_gateways',
                function ($methods = null)
                {

                    $methods[] = \PayU\PaymentMethod::class;

                    return $methods;
                }
            );
        }
    }