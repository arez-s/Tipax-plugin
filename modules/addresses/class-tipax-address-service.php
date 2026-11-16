<?php
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Tipax_Address_Service')) {
    return;
}

class Tipax_Address_Service
{
    private $request;
    private $auth;
    private $settings;

    public static function is_sender_address_record($record)
    {
        if (!is_object($record)) {
            return false;
        }

        if (isset($record->peopleAddressTypeId)) {
            return in_array($record->peopleAddressTypeId, [1, '1'], true);
        }

        if (isset($record->address) && is_object($record->address)) {
            return self::is_sender_address_record($record->address);
        }

        if (isset($record->address) && is_array($record->address) && !empty($record->address)) {
            foreach ($record->address as $detail) {
                if (!self::is_sender_address_record($detail)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    private function is_usable_address_record($record)
    {
        return self::is_sender_address_record($record) && (
            isset($record->address) ||
            isset($record->id) ||
            isset($record->addressesId) ||
            isset($record->fullAddress)
        );
    }

    private function pick_fallback_address_record($allAddresses)
    {
        if (!is_array($allAddresses) || empty($allAddresses)) {
            return null;
        }

        $selected_origin_id = get_option('tipax_shipping_address');
        if ($selected_origin_id !== false && $selected_origin_id !== null && $selected_origin_id !== '') {
            foreach ($allAddresses as $item) {
                if (
                    isset($item['originId'], $item['fullAddress']) &&
                    (string) $item['originId'] === (string) $selected_origin_id &&
                    is_object($item['fullAddress'])
                ) {
                    return $item['fullAddress'];
                }
            }
        }

        foreach ($allAddresses as $item) {
            if (isset($item['fullAddress']) && is_object($item['fullAddress'])) {
                return $item['fullAddress'];
            }
        }

        return null;
    }

    public function __construct($request = null, $settings = null, $auth = null)
    {
        $this->request = $request ?: new Request();
        $this->settings = is_array($settings) ? $settings : get_option(Tipax_Settings::SETTINGS_OPTION, []);
        if (!is_array($this->settings)) {
            $this->settings = [];
        }
        $this->auth = $auth ?: new Tipax_Auth_Service($this->request, $this->settings);
    }

    public function fetch_addresses($accessToken = null)
    {
        $token = $accessToken ?: $this->auth->get_access_token();
        if (!$token) {
            return null;
        }

        $response = $this->request->apiRequest('Addresses?peopleAddressTypeId=1', [], 'GET', $token);
        if (is_array($response)) {
            return array_values(array_filter($response, [self::class, 'is_sender_address_record']));
        }

        if (is_object($response) && !isset($response->errors)) {
            foreach (['result', 'data'] as $field) {
                if (isset($response->{$field}) && is_array($response->{$field})) {
                    return array_values(array_filter($response->{$field}, [self::class, 'is_sender_address_record']));
                }
            }
        }

        return $response;
    }

    public function create_address($data, $accessToken = null)
    {
        $token = $accessToken ?: $this->auth->get_access_token();
        if (!$token) {
            return null;
        }

        $data['peopleAddressTypeId'] = 1;
        return $this->request->apiRequest('Addresses', $data, 'POST', $token);
    }

    public function load_default_address($args = [])
    {
        $args = array_merge([
            'use_control_transient' => true,
            'set_control_transient' => false,
            'require_shipping_settings_page' => false,
            'filter_by_user' => true,
            'update_presence_option' => true,
            'missing_origin_uses_current' => false,
        ], $args);

        $controlCall = 0;
        if ($args['use_control_transient'] && get_transient('addresses_loaded')) {
            $controlCall = 1;
        }

        $canInitialLoad = !$controlCall;
        if ($args['require_shipping_settings_page']) {
            $canInitialLoad = $canInitialLoad
                && isset($_GET['page'], $_GET['tab'])
                && $_GET['page'] === 'wc-settings'
                && $_GET['tab'] === 'shipping';
        }

        if ($canInitialLoad) {
            if ($args['set_control_transient']) {
                set_transient('addresses_loaded', true, 10);
            }

            $tipax_addresses = get_transient('tipax_addresses');
            $response = $this->fetch_addresses();
            $allAddresses = [];

            if (isset($response[0])) {
                foreach ($response as $address) {
                    if (!empty($address->isDefault)) {
                        if ($args['filter_by_user']) {
                            $user = isset($this->settings['username']) ? $this->settings['username'] : null;
                            if ($address->creator == $user) {
                                if ((isset($address->modifier) && $address->modifier == $user) || !isset($address->modifier)) {
                                    $tipax_addresses = $address;
                                }
                            }
                        } else {
                            $tipax_addresses = $address;
                        }
                    }

                    if (isset($address->id)) {
                        $allAddresses[$address->id]['originId'] = isset($address->addressesId) ? $address->addressesId : null;
                        $allAddresses[$address->id]['fullAddress'] = $address;
                    }
                }
            }

            if (!$this->is_usable_address_record($tipax_addresses)) {
                $tipax_addresses = $this->pick_fallback_address_record($allAddresses);
            }

            if ($args['update_presence_option']) {
                update_option('tipax_has_address', (!empty($response) && $this->is_usable_address_record($tipax_addresses)) ? 'yes' : 'no');
            }

            set_transient('tipax_addresses', $tipax_addresses, 12 * HOUR_IN_SECONDS);
            $this->sync_selected_origin_address($tipax_addresses, $allAddresses, $args['missing_origin_uses_current']);
        }

        tipax_log('Your new AddressId is :' . get_option('tipax_shipping_address'));
        $tipax_addresses = get_transient('tipax_addresses');

        if ($tipax_addresses === false || !$this->is_usable_address_record($tipax_addresses)) {
            $response = $this->fetch_addresses();
            $allAddresses = [];

            if (isset($response[0])) {
                foreach ($response as $address) {
                    if (!empty($address->isDefault)) {
                        $tipax_addresses = $address;
                    }

                    if (isset($address->id)) {
                        $allAddresses[$address->id]['originId'] = isset($address->addressesId) ? $address->addressesId : null;
                        $allAddresses[$address->id]['fullAddress'] = $address;
                    }
                }
            }

            if (!$this->is_usable_address_record($tipax_addresses)) {
                $tipax_addresses = $this->pick_fallback_address_record($allAddresses);
            }

            if ($args['update_presence_option']) {
                update_option('tipax_has_address', (!empty($response) && $this->is_usable_address_record($tipax_addresses)) ? 'yes' : 'no');
            }

            set_transient('tipax_addresses', $tipax_addresses, 12 * HOUR_IN_SECONDS);
            $this->sync_selected_origin_address($tipax_addresses, $allAddresses, $args['missing_origin_uses_current']);
        }

        return $tipax_addresses;
    }

    public function refresh_default_address($args = [])
    {
        delete_transient('tipax_addresses');
        return $this->load_default_address($args);
    }

    public function load_cities($accessToken = null)
    {
        $tipax_cities = get_transient('tipax_cities');
        if (false === $tipax_cities || count($tipax_cities) == 0) {
            $tipax_cities = [];
            $responses = [];
            $token = $accessToken ?: $this->auth->get_access_token();
            if ($token) {
                $responses = $this->request->apiRequest('Cities/plusstate', [], 'GET', $token);
            }
            if (isset($responses->errors)) {
                return false;
            }
            if (isset($responses) && is_array($responses)) {
                foreach ($responses as $response) {
                    $tipax_cities[$response->id] = $response->title . ' - ' . $response->stateTitle;
                }
            }
            set_transient('tipax_cities', $tipax_cities, 12 * HOUR_IN_SECONDS);
        }

        return $tipax_cities;
    }

    private function sync_selected_origin_address($addresses_objects, $allAddresses, $missingOriginUsesCurrent = false)
    {
        if (!is_array($allAddresses) || count($allAddresses) === 0 || !is_object($addresses_objects) || !isset($addresses_objects->id)) {
            return;
        }

        $originIdToCheck = get_option('tipax_shipping_address') !== false ? get_option('tipax_shipping_address') : null;
        if ($missingOriginUsesCurrent && $originIdToCheck === null && isset($addresses_objects->addressesId)) {
            $originIdToCheck = $addresses_objects->addressesId;
        }
        $exists = false;
        $selectedAddress = null;

        foreach ($allAddresses as $object) {
            if (isset($object['originId']) && $object['originId'] == $originIdToCheck) {
                $exists = true;
                $selectedAddress = $object['fullAddress'] ?? null;
                break;
            }
        }

        if ($exists && is_object($selectedAddress)) {
            delete_transient('tipax_addresses');
            set_transient('tipax_addresses', $selectedAddress, 12 * HOUR_IN_SECONDS);
            return;
        }

        if (!$exists && isset($allAddresses[$addresses_objects->id])) {
            $originAddressId = $allAddresses[$addresses_objects->id]['originId'];
            if (get_option('tipax_shipping_address') !== false) {
                update_option('tipax_shipping_address', $originAddressId, 'yes');
            } else {
                add_option('tipax_shipping_address', $originAddressId, '', 'yes');
            }
            delete_transient('tipax_addresses');
            set_transient('tipax_addresses', $allAddresses[$addresses_objects->id]['fullAddress'], 12 * HOUR_IN_SECONDS);
        }
    }
}


if (!class_exists('Tipax_Address_Module')) {
    class_alias('Tipax_Address_Service', 'Tipax_Address_Module');
}
