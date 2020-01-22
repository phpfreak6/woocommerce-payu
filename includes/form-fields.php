<?php

return array(
    'enabled' => array(
        'title'=> __('Enable:', 'payu'),
        'type' => 'checkbox',
        'label' => ' ',
		'description'  => __( 'enable payment method', 'payu-for-woocommerce' ),
        'default' => 'no'
    ),
	'sandbox' => array(
        'title'=> __('Sandbox:', 'payu'),
        'type' => 'checkbox',
        'label' => ' ',
		'description' => __( 'Select payment environment.', 'payu-for-woocommerce' ),
        'default' => 'yes'
    ),
	'saved_cards' => array(
                'type'          => 'checkbox',
                'title'         => __( 'Saved Cards', 'payu-for-woocommerce' ),
                'description'   => __( 'Allow customers to use saved cards for future purchases.', 'payu-for-woocommerce' ),
                'default'       => 'yes',
            ),
    'title' => array(
        'title' => __('Title:', 'payu'),
        'type'=> 'text',
        'description' => __('Title of PayU Payment Gateway that users sees on Checkout page.', 'payu'),
        'default' => __('PayU', 'payu'),
        'desc_tip' => true
    ),
    'test_pos_id' => array(
        'title' => __('Test POS ID:', 'payu'),
        'type' => 'text',
        'description' => __('Pos identifier from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'test_md5' => array(
        'title' => __('Test Second key:', 'payu'),
        'type' => 'text',
        'description' =>  __('Second key from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
	'live_pos_id' => array(
        'title' => __('Live POS ID:', 'payu'),
        'type' => 'text',
        'description' => __('Pos identifier from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'live_md5' => array(
        'title' => __('Live Second key:', 'payu'),
        'type' => 'text',
        'description' =>  __('Second key from "Configuration Keys" section of PayU management panel.', 'payu'),
        'desc_tip' => true
    ),
    'description' => array(
        'title' => __('Description:', 'payu'),
        'type' => 'text',
        'description' => __('Description of PayU Payment Gateway that users sees on Checkout page.', 'payu'),
        'default' => __('PayU is a leading payment services provider with presence in 16 growth markets across the world.', 'payu'),
        'desc_tip' => true
    ),
    'validity_time' => array(
        'title' => __('Validity time:', 'payu'),
        'type' => 'text',
        'description' =>  __('Time when paying for order is possible (in seconds).', 'payu'),
        'default' => '1440',
        'desc_tip' => true
    ),
    'payu_feedback' => array(
        'title'=> __('Automatic collection:', 'payu'),
        'type' => 'checkbox',
        'description' =>  __('Automatic collection makes it possible to automatically confirm incoming payments.', 'payu'),
        'label' => ' ',
        'default' => 'no',
        'desc_tip' => true
    )
);