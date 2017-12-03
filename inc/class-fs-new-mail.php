<?php

class FS_New_Mail {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;


	/**
	 * API РєР»СЋС‡ РќРѕРІР°СЏ РџРѕС‡С‚Р°.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $api_key;


	/**
	 * РўРѕС‡РєР° РІС…РѕРґР° РґР»СЏ API РќРѕРІР°СЏ РџРѕС‡С‚Р°.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $api_host = 'https://api.novaposhta.ua/v2.0/json/';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name The name of the plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct() {
		// присваиваем API ключ из настроек
		$this->api_key = fs_option( 'nm_api_key' );

		/**
		 * Ajax запрос для получения городов Новой Почты.
		 */
		add_action( 'wp_ajax_fs_get_city', array( $this, 'ajax_nm_get_city' ) );
		add_action( 'wp_ajax_nopriv_fs_get_city', array( $this, 'ajax_nm_get_city' ) );

		/**
		 * Ajax запрос для получения отделений Новой Почты.
		 */
		add_action( 'wp_ajax_fs_get_warehouses', array( $this, 'ajax_get_warehouses' ) );
		add_action( 'wp_ajax_nopriv_fs_get_warehouses', array( $this, 'ajax_get_warehouses' ) );

	}


	/**
	 * отправляет запрос методом POST
	 *
	 * @param array $args
	 *
	 * @return bool
	 */
	function send_query( $args ) {
		$args["apiKey"] = $this->api_key;

		# преобразование в JSON-формат
		$request = json_encode( $args );

		# параметры запроса
		$opts = [
			'http' => [
				'method'  => "POST",
				'header'  => "Content-type: application/x-www-form-urlencoded;\r\n",
				'content' => $request,
			]
		];

		# создание контекста потока
		$context = stream_context_create( $opts );

		# отправляем запрос и получаем ответ от сервера
		$result = file_get_contents( $this->api_host, 0, $context );
		$res    = json_decode( $result );
//		echo '<pre>';
//		print_r( $res );
//		echo '</pre>';
		if ( $res->success ) {
			return $res->data;
		} else {
			return $res;
		}
	}

	/**
	 * метод-колбек для ajax запроса по получения городов
	 */
	public function ajax_nm_get_city() {
		$query    = [
			"modelName"        => "Address",
			"calledMethod"     => "searchSettlements",
			"methodProperties" => [
				"CityName" => sanitize_text_field( $_POST['id'] ),
			]
		];
		$response = $this->send_query( $query );
		// если возникли ошибки выходим
		if ( count( $response->errors ) ) {
			echo '<li>не знайдено віділень Нової Пошти</li>';
			exit();
		}


		$droppdown = '';
		if ( $response[0]->TotalCount ) {
			$addr = $response[0]->Addresses;
			foreach ( $addr as $city ) {
				$droppdown .= '<li data-value="' . $city->MainDescription . '">' . $city->MainDescription . '</li>';
			}
		} else {
			$droppdown .= '<li>не знайдено віділень Нової Пошти</li>';
		}
		echo $droppdown;
		exit();
	}

	/**
	 * метод-колбек для ajax запроса по получения отделений в городе
	 */
	public function ajax_get_warehouses() {

		$query = [
			"modelName"        => "AddressGeneral",
			"calledMethod"     => "getWarehouses",
			"methodProperties" => [
				"CityName" => sanitize_text_field( $_POST['id'] ),
			]
		];


// Обновляем типы отделений
		if ( get_option( 'fs_nm_update_date' ) != date( 'd-m-Y' ) ) {
			$query_wh_types = [
				"modelName"    => "AddressGeneral",
				"calledMethod" => "getWarehouseTypes"
			];
			$wh_types       = $this->send_query( $query_wh_types );
			$pochtomats     = array();
			$warhouses      = array();
			if ( ! empty( $wh_types ) ) {
				foreach ( $wh_types as $wh_type ) {
					if ( $wh_type->Description == 'Поштомат ПриватБанку' || $wh_type->Description == 'Поштомат' ) {
						$pochtomats[] = $wh_type->Ref;
					} elseif ( $wh_type->Description == 'Поштове відділення' || $wh_type->Description == 'Вантажне відділення' ) {
						$warhouses[] = $wh_type->Ref;
					}
				}
			}
			if ( ! empty( $pochtomats ) ) {
				update_option( 'fs_nm_pochtomats', $pochtomats );
			}
			if ( ! empty( $warhouses ) ) {
				update_option( 'fs_nm_warhouses', $warhouses );
			}
			update_option( 'fs_nm_update_date', date( 'd-m-Y' ) );
		}

		$pochtomats = get_option( 'fs_nm_pochtomats' );
		$warhouses  = get_option( 'fs_nm_warhouses' );
		$response   = $this->send_query( $query );
		$droppdown  = '';
		if ( count( $response ) ) {
			foreach ( $response as $city ) {

				if ( in_array( $city->TypeOfWarehouse, $warhouses ) && $_POST['type'] == fs_option( 'nm_warehouse' ) ) {
					$droppdown .= '<li data-value="' . esc_html( $city->Description ) . '">' . esc_html( $city->Description ) . '</li>';
					continue;
				}
				if ( in_array( $city->TypeOfWarehouse, $pochtomats ) && $_POST['type'] == fs_option( 'nm_pochtomat' ) ) {
					$droppdown .= '<li data-value="' . esc_html( $city->Description ) . '">' . esc_html( $city->Description ) . '</li>';
					continue;
				}
			}
		}

		if ( empty( $droppdown ) ) {
			$droppdown = '<li>Не знайдено відділень у  вашому місті.</li>';
		}

		echo $droppdown;
		exit();
	}
}