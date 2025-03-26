<?php

class FS_New_Mail
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     *
     * @var string the ID of this plugin
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     *
     * @var string the current version of this plugin
     */
    private $version;

    /**
     * API РєР»СЋС‡ РќРѕРІР°СЏ РџРѕС‡С‚Р°.
     *
     * @since    1.0.0
     *
     * @var string
     */
    private $api_key;

    /**
     * РўРѕС‡РєР° РІС…РѕРґР° РґР»СЏ API РќРѕРІР°СЏ РџРѕС‡С‚Р°.
     *
     * @since    1.0.0
     *
     * @var string
     */
    private $api_host = 'https://api.novaposhta.ua/v2.0/json/';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        // присваиваем API ключ из настроек
        $this->api_key = fs_option('nm_api_key');

        /*
         * Ajax запрос для получения городов Новой Почты.
         */
        add_action('wp_ajax_fs_get_city', [$this, 'fs_get_city']);
        add_action('wp_ajax_nopriv_fs_get_city', [$this, 'fs_get_city']);

        /*
         * Ajax запрос для получения отделений Новой Почты.
         */
        add_action('wp_ajax_fs_get_warehouses', [$this, 'fs_get_warehouses']);
        add_action('wp_ajax_nopriv_fs_get_warehouses', [$this, 'fs_get_warehouses']);

        // Получаем ID города по переданому названию
        add_action('wp_ajax_fs_nm_city_code', [$this, 'fs_nm_city_code']);
        add_action('wp_ajax_nopriv_fs_nm_city_code', [$this, 'fs_nm_city_code']);

        /*
         * Добавляем поля в профиль пользователя.
         */
        add_filter('fs_user_fields', [$this, 'fs_user_fields']);
    }

    public function fs_user_fields($fields)
    {
        $fields['fs_region_ref'] = [
            'type' => 'text',
            'name' => __('Код регіону (Новая Пошта)', 'fs-new-mail'),
            'label' => '',
            'value' => get_user_meta(get_current_user_id(), 'fs_region_ref', true),
        ];

        $fields['fs_city_ref'] = [
            'type' => 'text',
            'name' => __('Код міста (Нова Пошта)', 'fs-new-mail'),
            'label' => '',
            'value' => get_user_meta(get_current_user_id(), 'fs_city_ref', true),
        ];

        return $fields;
    }

    /**
     * отправляет запрос методом POST.
     *
     * @param array $args
     *
     * @return bool|object
     */
    public function send_query($args)
    {
        $args['apiKey'] = $this->api_key;

        // преобразование в JSON-формат
        $request = json_encode($args);

        // параметры запроса
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded;\r\n",
                'content' => $request,
            ],
        ];

        // создание контекста потока
        $context = stream_context_create($opts);

        // отправляем запрос и получаем ответ от сервера
        $result = file_get_contents($this->api_host, 0, $context);
        $res = json_decode($result);
        if ($res->success) {
            return $res->data;
        } else {
            return (object) [
                'success' => false,
                'errors' => !empty($res->errors) ? $res->errors : [__('Неизвестная ошибка API', 'fs-new-mail')],
                'errorCodes' => !empty($res->errorCodes) ? $res->errorCodes : [],
                'data' => null,
            ];
        }
    }

    // Получаем ID города по переданому названию
    public function fs_nm_city_code()
    {
        $query = [
            'modelName' => 'Address',
            'calledMethod' => 'searchSettlements',
            'methodProperties' => [
                'CityName' => sanitize_text_field($_POST['input']),
            ],
        ];
        $response = $this->send_query($query);

        // Проверяем на ошибки
        if (is_object($response) && isset($response->success) && $response->success === false) {
            wp_send_json_error([
                'msg' => implode(', ', $response->errors),
                'errorCodes' => $response->errorCodes,
            ]);

            return;
        }

        // Проверяем, является ли $response объектом stdClass
        if (is_object($response) && property_exists($response, 'data')) {
            $response = [$response->data];
        }

        if (!empty($response[0]->TotalCount) && !empty($response[0]->Addresses)) {
            $out = '<select name="select-from-nm-city" style="display: block;">';
            $out .= '<option value="">'.esc_html__('Виберіть населений пункт', 'fs-new-mail').'</option>';
            foreach ($response[0]->Addresses as $address) {
                $out .= '<option value="'.esc_attr($address->DeliveryCity).'">'.esc_html($address->Present).'</option>';
            }
            $out .= '</select>';
            wp_send_json_success(['html' => $out]);
        }

        wp_send_json_error(['msg' => __('Не знайдено населених пунктів', 'fs-new-mail')]);
    }

    /**
     * метод-колбек для ajax запроса по получения городов
     * https://devcenter.novaposhta.ua/docs/services/556d7ccaa0fe4f08e8f7ce43/operations/58e5ebeceea27017bc851d67.
     */
    public function fs_get_city()
    {
        $query = [
            'modelName' => 'AddressGeneral',
            'calledMethod' => 'searchSettlements',
            'methodProperties' => [
                'CityName' => sanitize_text_field($_POST['cityName']),
                'Limit' => isset($_POST['limit']) ? intval($_POST['limit']) : 50,
                'Page' => isset($_POST['page']) ? intval($_POST['page']) : 1,
            ],
        ];
        $response = $this->send_query($query);

        // Проверяем на ошибки
        if (is_object($response) && isset($response->success) && $response->success === false) {
            wp_send_json_error([
                'msg' => implode(', ', $response->errors),
                'errorCodes' => $response->errorCodes,
            ]);

            return;
        }

        // Проверяем, является ли $response объектом stdClass
        if (is_object($response) && property_exists($response, 'data')) {
            $response = [$response->data];
        }

        if (!empty($response[0]->Addresses)) {
            $cities = $response[0]->Addresses ?? [];
            $cities = array_map(function ($item) {
                return [
                    'name' => $item->Present,
                    'ref' => $item->DeliveryCity,
                ];
            }, $cities);
            wp_send_json_success(compact('cities'));
        } else {
            wp_send_json_error(['msg' => __('не знайдено віділень Нової Пошти', 'fs-new-mail')]);
        }
    }

    /**
     * метод-колбек для ajax запроса по получения отделений в городе.
     *
     * https://devcenter.novaposhta.ua/docs/services/556d7ccaa0fe4f08e8f7ce43/operations/556d8211a0fe4f08e8f7ce45
     */
    public function fs_get_warehouses()
    {
        $query = [
            'modelName' => 'AddressGeneral',
            'calledMethod' => 'getWarehouses',
            'methodProperties' => [
                //				"CityName" => sanitize_text_field( $_POST['cityName'] ),
                'CityRef' => sanitize_text_field($_POST['ref']),
            ],
        ];
        $response = $this->send_query($query);

        $delivery_cost = 0;
        if (fs_option('fs_nm_from_city_id')) {
            $response_cost_delivery = $this->send_query([
                'modelName' => 'InternetDocument',
                'calledMethod' => 'getDocumentPrice',
                'methodProperties' => [
                    'CitySender' => sanitize_text_field(fs_option('fs_nm_from_city_id')),
                    'CityRecipient' => sanitize_text_field($_POST['ref']),
                    'Weight' => 2,
                ],
            ]);
            if (!empty($response_cost_delivery[0]->CostWarehouseWarehouse)) {
                $delivery_cost = floatval($response_cost_delivery[0]->CostWarehouseWarehouse);
            }
        }

        if (!empty($response) && is_array($response)) {
            $warehouses = array_map(function ($item) {
                return [
                    'description' => $item->Description,
                    'ref' => $item->Ref,
                ];
            }, $response);

            wp_send_json_success([
                'warehouses' => $warehouses,
                'deliveryCost' => sprintf('%s <span>%s</span>', apply_filters('fs_price_format', $delivery_cost), fs_currency()),
                'totalAmount' => sprintf('%s <span>%s</span>', apply_filters('fs_price_format', fs_get_total_amount($delivery_cost)), fs_currency()),
            ]);
        }

        wp_send_json_error([
            'msg' => __('Не знайдено відділень, виберіть найближче до вас місто'),
        ]);
    }
}
