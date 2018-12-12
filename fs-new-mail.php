<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://f-shop.top
 * @since             1.0.1
 * @package           Fs_New_Mail
 *
 * @wordpress-plugin
 * Plugin Name:       F-Shop Новая Почта
 * Plugin URI:        https://f-shop.top
 * Description:       Этот плагин добавляет расширенные возможности для интернет магазина на базе F-Shop по работе с Новой Почтой.
 * Version:           1.0.01
 * Author:            Vitaliy Karakushan
 * Author URI:        https://f-shop.top
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fs-new-mail
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once "inc/class-fs-new-mail.php";
if ( class_exists( 'FS_New_Mail' ) ) {
	new FS_New_Mail();
}

/**
 * Здесь подключаются скрипты и стили плагина
 */
function fs_nm_enqueue_script() {
	wp_enqueue_style( 'fs-new-mail', plugin_dir_url( __FILE__ ) . 'assets/fs-new-mail.css' );
	wp_enqueue_script( 'fs-new-mail', plugin_dir_url( __FILE__ ) . 'assets/fs-new-mail.js', array( 'jquery' ), null, true );
	$translation_array = array(
		'pochtomatId' => fs_option( 'nm_pochtomat' ),
		'warehouseId' => fs_option( 'nm_warehouse' ),

	);
	wp_localize_script( 'fs-new-mail', 'fsNmOptions', $translation_array );
}

add_action( 'wp_enqueue_scripts', 'fs_nm_enqueue_script' );

function fs_nm_admin_script() {
	wp_enqueue_script( 'fs-nm-admin', plugin_dir_url( __FILE__ ) . 'assets/fs-new-mail-admin.js', array( 'jquery' ), null, true );
}

add_action( 'admin_enqueue_scripts', 'fs_nm_admin_script' );


/**
 * Создаём вкладку настроек плагина в F-Shop
 *
 * @param $settings
 *
 * @return mixed
 */
function fs_nm_plugin_settings( $settings ) {
	$settings['nm'] = array(
		'name'   => __( 'Новая почта', 'fast-shop' ),
		'fields' => array(
			array(
				'type'  => 'text',
				'name'  => 'nm_api_key',
				'label' => 'API ключ <span><a href="https://devcenter.novaposhta.ua/blog/%D0%BF%D0%BE%D0%BB%D1%83%D1%87%D0%B5%D0%BD%D0%B8%D0%B5-api-%D0%BA%D0%BB%D1%8E%D1%87%D0%B0" target="_blank">получение ключа</a></span>',
				'value' => fs_option( 'nm_api_key' )
			),
			array(
				'type'     => 'dropdown_categories',
				'taxonomy' => 'fs-delivery-methods',
				'name'     => 'nm_warehouse',
				'label'    => 'Способ доставки в отделение Новой Почты',
				'value'    => fs_option( 'nm_warehouse' ),
				'selected' => fs_option( 'nm_warehouse' )
			),
			array(
				'type'     => 'dropdown_categories',
				'taxonomy' => 'fs-delivery-methods',
				'name'     => 'nm_pochtomat',
				'label'    => 'Способ доставки в почтоматы Приватбанка',
				'value'    => fs_option( 'nm_pochtomat' ),
				'selected' => fs_option( 'nm_pochtomat' )
			),
			array(
				'type'     => 'dropdown_categories',
				'taxonomy' => 'fs-delivery-methods',
				'name'     => 'nm_pochtomat',
				'label'    => 'Способ доставки в почтоматы Приватбанка',
				'value'    => fs_option( 'nm_pochtomat' ),
				'selected' => fs_option( 'nm_pochtomat' )
			),
			array(
				'type'  => 'checkbox',
				'name'  => 'fs_nm_show_all',
				'label' => 'Не разделять почтоматы и отделения',
				'value' => fs_option( 'fs_nm_show_all' ),
			),
			array(
				'type'  => 'text',
				'name'  => 'fs_nm_from_city',
				'label' => __( 'Город отправки товара', 'fs-new-mail' ),
				'value' => fs_option( 'fs_nm_from_city' ),
			),
			array(
				'type'  => 'text',
				'name'  => 'fs_nm_from_city_id',
				'label' => __( 'Код города отправки товара', 'fs-new-mail' ),
				'value' => fs_option( 'fs_nm_from_city_id' ),
			)
		)
	);

	return $settings;
}

add_filter( 'fs_plugin_settings', 'fs_nm_plugin_settings' );







