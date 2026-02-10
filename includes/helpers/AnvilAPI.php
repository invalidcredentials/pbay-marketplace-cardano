<?php
namespace PBay\Helpers;

class AnvilAPI {

    /**
     * Call Anvil utils endpoints using PBay-provided keys (utils access enabled).
     * Regular user keys don't have utils toggled on, so policy generation
     * routes through these keys while all other calls use the user's key.
     */
    private static function callUtils($endpoint, $data) {
        $network = get_option('pbay_network', 'preprod');

        if ($network === 'mainnet') {
            $api_url = 'https://prod.api.ada-anvil.app/v2/services';
            $api_key = 'mainnet_GUztXoIEyulcuuqIeS5TPy8fJR1BMnAJHQtnFQDC';
        } else {
            $api_url = 'https://preprod.api.ada-anvil.app/v2/services';
            $api_key = 'testnet_FomUHyfbv1QeO1LifQfAml9v5xO2XbRTdc9k7BIY';
        }

        $full_url = $api_url . '/' . $endpoint;

        $response = wp_remote_post($full_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Key' => $api_key,
            ],
            'body' => wp_json_encode($data),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            error_log('[PBay] WP_Error (utils): ' . $response->get_error_message());
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code !== 200) {
            error_log('[PBay] API error (utils, HTTP ' . $http_code . '): ' . $body);
            return new \WP_Error('api_error', $decoded['message'] ?? 'Utils API request failed (HTTP ' . $http_code . ')', $decoded);
        }

        return $decoded;
    }

    /**
     * Call Anvil API endpoint
     */
    public static function call($endpoint, $data) {
        $api_url = get_option('pbay_anvil_api_url', 'https://preprod.api.ada-anvil.app/v2/services');
        $api_key = get_option('pbay_anvil_api_key');

        $full_url = $api_url . '/' . $endpoint;
        error_log('[PBay] API call: ' . $endpoint . ' → ' . $full_url);

        // Verbose debug logging when PBAY_DEBUG is enabled
        if (defined('PBAY_DEBUG') && PBAY_DEBUG) {
            error_log('[PBay][DEBUG] Full URL: ' . $full_url);
            error_log('[PBay][DEBUG] Key (first 12): ' . substr($api_key, 0, 12));
            error_log('[PBay][DEBUG] Payload: ' . wp_json_encode($data));
        }

        if (!$api_key) {
            return new \WP_Error('no_api_key', 'Anvil API key not configured');
        }

        $response = wp_remote_post($full_url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Key' => $api_key,
            ],
            'body' => wp_json_encode($data),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            error_log('[PBay] WP_Error: ' . $response->get_error_message());
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code !== 200) {
            error_log('[PBay] API error (HTTP ' . $http_code . '): FULL BODY: ' . $body);
            error_log('[PBay] Request data: ' . wp_json_encode($data));
            error_log('[PBay] Key used (first 12): ' . substr($api_key, 0, 12));
            return new \WP_Error('api_error', $decoded['message'] ?? 'API request failed (HTTP ' . $http_code . ')', $decoded);
        }

        error_log('[PBay] API success: ' . $endpoint);
        error_log('[PBay] API response keys: ' . implode(', ', array_keys($decoded ?? [])));
        if ($endpoint === 'transactions/build') {
            error_log('[PBay] BUILD response complete exists: ' . (isset($decoded['complete']) ? 'YES (' . strlen($decoded['complete']) . ' chars)' : 'NO'));
            error_log('[PBay] BUILD response stripped exists: ' . (isset($decoded['stripped']) ? 'YES' : 'NO'));
            error_log('[PBay] BUILD full response: ' . substr($body, 0, 500));
        }
        return $decoded;
    }

    /**
     * Test API connection via health endpoint
     */
    public static function testConnection() {
        $api_url = get_option('pbay_anvil_api_url', 'https://preprod.api.ada-anvil.app/v2/services');
        $api_key = get_option('pbay_anvil_api_key');

        if (!$api_key) {
            return new \WP_Error('no_api_key', 'Anvil API key not configured');
        }

        $response = wp_remote_get($api_url . '/health', [
            'headers' => ['X-Api-Key' => $api_key],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        return ['success' => ($code === 200), 'status_code' => $code];
    }

    /**
     * Convert address to Bech32 format via Anvil API
     */
    public static function convertAddressToBech32($address) {
        if (preg_match('/^addr[0-9a-z]+$/', $address)) {
            return $address;
        }

        $response = self::call('utils/addresses/parse', ['address' => $address]);

        if (is_wp_error($response)) {
            return $address;
        }

        if (isset($response['address'])) {
            return $response['address'];
        } elseif (isset($response['bech32Address'])) {
            return $response['bech32Address'];
        }

        return $address;
    }

    /**
     * Get ADA price from CoinGecko with caching
     */
    public static function getAdaPrice() {
        $cached_price = get_transient('pbay_ada_price');

        if ($cached_price !== false && $cached_price > 0) {
            return floatval($cached_price);
        }

        $response = wp_remote_get('https://api.coingecko.com/api/v3/simple/price?ids=cardano&vs_currencies=usd', [
            'timeout' => 10,
            'user-agent' => 'WordPress/PBay-Plugin',
        ]);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['cardano']['usd']) && $data['cardano']['usd'] > 0) {
                $price = floatval($data['cardano']['usd']);
                set_transient('pbay_ada_price', $price, 300);
                return $price;
            }
        }

        return 1.0; // Fallback
    }

    /**
     * Truncate metadata string to 64-char Cardano limit
     */
    public static function truncateMetadata($string) {
        if (strlen($string) <= 64) {
            return $string;
        }
        return substr($string, 0, 61) . '...';
    }

    /**
     * Generate a new Cardano policy via Anvil API with time lock
     */
    public static function generatePolicy($policy_keyhash, $expiration_date = null) {
        if (empty($policy_keyhash)) {
            return new \WP_Error('no_keyhash', 'Policy wallet keyhash required');
        }

        if (empty($expiration_date)) {
            $expiration_date = date('Y-m-d\TH:i:s\Z', strtotime('+1 year'));
        }

        // Convert expiration to slot
        $expiration_timestamp_ms = strtotime($expiration_date) * 1000;
        $slot_response = self::callUtils('utils/network/time-to-slot', [
            'time' => $expiration_timestamp_ms,
        ]);

        if (is_wp_error($slot_response)) {
            return $slot_response;
        }

        $expiration_slot = $slot_response['slot'] ?? null;
        if (!$expiration_slot) {
            return new \WP_Error('no_slot', 'Could not convert expiration date to slot number');
        }

        // Build policy schema: sig + time lock
        $policy_schema = [
            'type' => 'all',
            'scripts' => [
                ['type' => 'sig', 'keyHash' => $policy_keyhash],
                ['type' => 'before', 'slot' => $expiration_slot],
            ],
        ];

        // Serialize to get policy ID
        $serialize_response = self::callUtils('utils/native-scripts/serialize', [
            'schema' => $policy_schema,
        ]);

        if (is_wp_error($serialize_response)) {
            return $serialize_response;
        }

        $policy_id = $serialize_response['policyId'] ?? null;
        $policy_script = $serialize_response['script'] ?? null;

        if (!$policy_id || !$policy_script) {
            return new \WP_Error('no_policy_data', 'Could not extract policy data from serialization response');
        }

        return [
            'policyId' => $policy_id,
            'script' => $policy_script,
            'schema' => $policy_schema,
            'expiresAt' => $expiration_date,
            'slot' => $expiration_slot,
            'policyKeyHash' => $policy_keyhash,
        ];
    }

    /**
     * Build a payment transaction (buyer pays merchant + gets receipt)
     */
    public static function buildPaymentTransaction($merchant_address, $buyer_address, $usd_price, $product_name) {
        $merchant_address = self::convertAddressToBech32($merchant_address);
        $buyer_address = self::convertAddressToBech32($buyer_address);

        // Get current ADA price and convert
        $ada_price = self::getAdaPrice();
        error_log('[PBay][PRICE] getAdaPrice() returned: ' . var_export($ada_price, true));
        error_log('[PBay][PRICE] usd_price: ' . var_export($usd_price, true));

        if (!$ada_price || $ada_price <= 0) {
            error_log('[PBay][PRICE] ADA price is zero/null/false — cannot calculate');
            return new \WP_Error('no_ada_price', 'Could not get ADA exchange rate');
        }

        $ada_amount = $usd_price / $ada_price;
        $lovelace_merchant = intval($ada_amount * 1000000);
        error_log('[PBay][PRICE] ada_amount=' . $ada_amount . ' lovelace_merchant=' . $lovelace_merchant);

        $receipt_amount = 1.0; // 1 ADA receipt back to buyer

        $transaction_request = [
            'changeAddress' => $buyer_address,
            'outputs' => [
                [
                    'address' => $merchant_address,
                    'lovelace' => $lovelace_merchant,
                ],
                [
                    'address' => $buyer_address,
                    'lovelace' => intval($receipt_amount * 1000000),
                ],
            ],
            'message' => [
                self::truncateMetadata('PBay Purchase Receipt'),
                self::truncateMetadata('Product: ' . $product_name),
                self::truncateMetadata('Price: $' . number_format($usd_price, 2) . ' USD (' . number_format($ada_amount, 2) . ' ADA)'),
                self::truncateMetadata('Receipt: 1 ADA returned to buyer'),
                self::truncateMetadata('Timestamp: ' . current_time('c')),
                self::truncateMetadata('Exchange Rate: $' . number_format($ada_price, 4) . '/ADA'),
            ],
        ];

        error_log('[PBay][BUILD_TX] Full payload: ' . wp_json_encode($transaction_request));

        return self::call('transactions/build', $transaction_request);
    }

    /**
     * Build an NFT mint transaction
     */
    public static function buildMintTransaction($policy_wallet_address, $policy_id, $asset_name, $cip25_metadata, $policy_schema) {
        $policy_wallet_address = self::convertAddressToBech32($policy_wallet_address);

        $transaction_request = [
            'changeAddress' => $policy_wallet_address,
            'outputs' => [
                [
                    'address' => $policy_wallet_address,
                    'lovelace' => 2000000, // min UTxO
                    'assets' => [
                        [
                            'policyId' => $policy_id,
                            'assetName' => ['name' => $asset_name, 'format' => 'utf8'],
                            'quantity' => 1,
                        ],
                    ],
                ],
            ],
            'mint' => [
                [
                    'version' => 'cip25',
                    'policyId' => $policy_id,
                    'quantity' => 1,
                    'assetName' => ['name' => $asset_name, 'format' => 'utf8'],
                    'metadata' => $cip25_metadata,
                ],
            ],
        ];

        if ($policy_schema) {
            $transaction_request['preloadedScripts'] = [
                [
                    'type' => 'simple',
                    'script' => $policy_schema,
                    'hash' => $policy_id,
                ],
            ];
        }

        return self::call('transactions/build', $transaction_request);
    }

    /**
     * Build an NFT transfer transaction (policy wallet → buyer)
     */
    public static function buildAssetTransferTransaction($from_address, $to_address, $policy_id, $asset_name) {
        $from_address = self::convertAddressToBech32($from_address);
        $to_address = self::convertAddressToBech32($to_address);

        $transaction_request = [
            'changeAddress' => $from_address,
            'outputs' => [
                [
                    'address' => $to_address,
                    'lovelace' => 2000000,
                    'assets' => [
                        [
                            'policyId' => $policy_id,
                            'assetName' => ['name' => $asset_name, 'format' => 'utf8'],
                            'quantity' => 1,
                        ],
                    ],
                ],
            ],
        ];

        error_log('[PBay][NFT_TRANSFER] Building transfer TX: ' . $policy_id . '.' . $asset_name . ' from=' . substr($from_address, 0, 20) . ' to=' . substr($to_address, 0, 20));

        return self::call('transactions/build', $transaction_request);
    }

    /**
     * Build a simple ADA transfer transaction (no assets)
     */
    public static function buildSimpleTransaction($from_address, $to_address, $lovelace_amount) {
        $from_address = self::convertAddressToBech32($from_address);
        $to_address = self::convertAddressToBech32($to_address);

        $transaction_request = [
            'changeAddress' => $from_address,
            'outputs' => [
                [
                    'address' => $to_address,
                    'lovelace' => intval($lovelace_amount),
                ],
            ],
        ];

        error_log('[PBay][SEND_ADA] Building simple TX: ' . $lovelace_amount . ' lovelace from=' . substr($from_address, 0, 20) . ' to=' . substr($to_address, 0, 20));

        return self::call('transactions/build', $transaction_request);
    }

    /**
     * Submit transaction with signatures
     */
    public static function submitTransaction($transaction, $signatures) {
        return self::call('transactions/submit', [
            'transaction' => $transaction,
            'signatures' => is_array($signatures) ? $signatures : [],
        ]);
    }
}
