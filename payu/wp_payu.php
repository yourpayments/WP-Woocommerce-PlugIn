<?php

    /*
      Plugin Name: «Твои платежи» WooCommerce

      Description: Данный плагин добавляет на Ваш сайт метод оплаты «Твои платежи» для WooCommerce
      Plugin URI: /wp-admin/admin.php?page=main_settings_payu.php
      Author: PayU
      Version: 1.0.0
    */


    try
    {
        spl_autoload_register(
            function ($className)
            {

                $file = __DIR__ . '/classes/' . \str_replace('\\', '/', $className) . '.php';

                if (file_exists($file)) require_once $file;
            }
        );
    }
    catch (Exception $e)
    {}

    /** @var array $actions */
    $actions = [
        [
            'code' => 'admin_menu',
            'action' => [\PayU\Main::class, 'menu']
        ],
        [
            'code' => 'plugins_loaded',
            'action' => [\PayU\Main::class, 'initPayment']
        ],
        [
            'code' => 'parse_request',
            'action' => [\PayU\PaymentMethod::class, 'checkIpnResponse']
        ],
        [
            'code' => 'valid-payu-standard-ipn-request',
            'action' => [\PayU\PaymentMethod::class, 'successfulRequest']
        ],
    ];

    foreach($actions as $action)
        add_action($action['code'], $action['action']);

    register_activation_hook(
        __FILE__,
        [\PayU\Main::class, 'install']
    );