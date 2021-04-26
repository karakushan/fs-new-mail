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
		add_action( 'wp_ajax_fs_get_city', array( $this, 'fs_get_city' ) );
		add_action( 'wp_ajax_nopriv_fs_get_city', array( $this, 'fs_get_city' ) );

		/**
		 * Ajax запрос для получения отделений Новой Почты.
		 */
		add_action( 'wp_ajax_fs_get_warehouses', array( $this, 'fs_get_warehouses' ) );
		add_action( 'wp_ajax_nopriv_fs_get_warehouses', array( $this, 'fs_get_warehouses' ) );

		// Получаем ID города по переданому названию
		add_action( 'wp_ajax_fs_nm_city_code', array( $this, 'fs_nm_city_code' ) );
		add_action( 'wp_ajax_nopriv_fs_nm_city_code', array( $this, 'fs_nm_city_code' ) );

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

	// Получаем ID города по переданому названию
	function fs_nm_city_code() {
		$query    = [
			"modelName"        => "Address",
			"calledMethod"     => "searchSettlements",
			"methodProperties" => [
				"CityName" => sanitize_text_field( $_POST['input'] ),
			]
		];
		$response = $this->send_query( $query );
		/*print_r( $response[0]->TotalCount );
		wp_die();*/
		if ( ! empty( $response[0]->TotalCount ) && ! empty( $response[0]->Addresses ) ) {
			$out = '<select name="select-from-nm-city" style="display: block;">';
			$out .= '<option value="">' . esc_html__( 'Виберіть населений пункт', 'fs-new-mail' ) . '</option>';
			foreach ( $response[0]->Addresses as $address ) {
				$out .= '<option value="' . esc_attr( $address->DeliveryCity ) . '">' . esc_html( $address->Present ) . '</option>';
			}
			$out .= '</select>';
			wp_send_json_success( [ 'html' => $out ] );
		}

		wp_send_json_error();
	}


	/**
	 * метод-колбек для ajax запроса по получения городов
	 * https://devcenter.novaposhta.ua/docs/services/556d7ccaa0fe4f08e8f7ce43/operations/58e5ebeceea27017bc851d67
	 */
	public function fs_get_city() {
		$query    = [
			"modelName"        => "Address",
			"calledMethod"     => "searchSettlements",
			"methodProperties" => [
				"CityName" => sanitize_text_field( $_POST['cityName'] ),
			]
		];
		$response = $this->send_query( $query );
//	wp_send_json($response);
		if ( ! empty( $response[0]->Addresses ) ) {
			$out = '<ul class="nm-city" data-fs-element="select-delivery-city">';
			foreach ( $response[0]->Addresses as $address ) {
				$out .= '<li data-name="' . esc_attr( $address->MainDescription ) . '" data-ref="' . esc_attr( $address->DeliveryCity ) . '">' . esc_html( $address->Present ) . '</li>';
			}
			$out .= '</ul>';
			wp_send_json_success( array( 'html' => $out, 'response' => $response ) );
		} else {
			wp_send_json_error( array( 'msg' => __( 'не знайдено віділень Нової Пошти', 'fs-new-mail' ) ) );
		}
	}

	/**
	 * метод-колбек для ajax запроса по получения отделений в городе
	 *
	 * https://devcenter.novaposhta.ua/docs/services/556d7ccaa0fe4f08e8f7ce43/operations/556d8211a0fe4f08e8f7ce45
	 */
	public function fs_get_warehouses() {
		$query    = [
			"modelName"        => "AddressGeneral",
			"calledMethod"     => "getWarehouses",
			"methodProperties" => [
				"CityName" => sanitize_text_field( $_POST['cityName'] ),
//				"CityRef"  => sanitize_text_field( $_POST['ref'] )

			]
		];
		$response = $this->send_query( $query );

		$delivery_cost = 0;
		if ( fs_option( 'fs_nm_from_city_id' ) ) {
			$response_cost_delivery = $this->send_query( array(
				"modelName"        => "InternetDocument",
				"calledMethod"     => "getDocumentPrice",
				"methodProperties" => [
					"CitySender"    => sanitize_text_field( fs_option( 'fs_nm_from_city_id' ) ),
					"CityRecipient" => sanitize_text_field( $_POST['ref'] ),
					"Weight"        => 2
				]
			) );
			if ( ! empty( $response_cost_delivery[0]->CostWarehouseWarehouse ) ) {
				$delivery_cost = floatval( $response_cost_delivery[0]->CostWarehouseWarehouse );
			}
		}

		if ( ! empty( $response ) ) {
			$out = '<ul class="nm-city" data-fs-element="select-warehouse">';
			foreach ( $response as $warehouse ) {
				$out .= '<li data-ref="' . esc_attr( $warehouse->Ref ) . '">' . esc_html( $warehouse->Description ) . '</li>';
			}
			$out .= '</li>';

			wp_send_json_success( array(
				'count'        => count( $response ),
				'first'        => esc_html( $response[0]->Description ),
				'html'         => $out,
				'response'     => $response,
				'deliveryCost' => sprintf( '%s <span>%s</span>', apply_filters( 'fs_price_format', $delivery_cost ), fs_currency() ),
				'totalAmount'  => sprintf( '%s <span>%s</span>', apply_filters( 'fs_price_format', fs_get_total_amount( $delivery_cost ) ), fs_currency() )
			) );
		}
		wp_send_json_error( array(
			'msg' => __( 'Не знайдено відділень, виберіть найближче до вас місто' )
		) );
	}
}