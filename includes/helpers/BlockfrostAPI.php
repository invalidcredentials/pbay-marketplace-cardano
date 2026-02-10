<?php
namespace PBay\Helpers;

class BlockfrostAPI {

    /**
     * Get Blockfrost API config for a given network
     */
    public static function getApiConfig($network = 'preprod') {
        if ($network === 'mainnet') {
            $base_url = 'https://cardano-mainnet.blockfrost.io/api/v0';
            $api_key = get_option('pbay_blockfrost_api_key_mainnet', '');
        } else {
            $base_url = 'https://cardano-preprod.blockfrost.io/api/v0';
            $api_key = get_option('pbay_blockfrost_api_key_preprod', '');
        }

        return [
            'base_url' => $base_url,
            'api_key' => $api_key,
        ];
    }

    /**
     * Call Blockfrost API endpoint (GET)
     */
    public static function call($endpoint, $network = 'preprod') {
        $config = self::getApiConfig($network);

        if (empty($config['api_key'])) {
            return new \WP_Error('no_blockfrost_key', 'Blockfrost API key not configured');
        }

        $url = $config['base_url'] . '/' . ltrim($endpoint, '/');

        $response = wp_remote_get($url, [
            'headers' => [
                'project_id' => $config['api_key'],
            ],
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('[PBay][Blockfrost] WP_Error: ' . $response->get_error_message());
            return $response;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($http_code === 404) {
            // Address not found = unused address, return empty
            return new \WP_Error('not_found', 'Address has no transactions yet');
        }

        if ($http_code !== 200) {
            $message = $decoded['message'] ?? 'Blockfrost API error (HTTP ' . $http_code . ')';
            error_log('[PBay][Blockfrost] Error: ' . $message);
            return new \WP_Error('blockfrost_error', $message);
        }

        return $decoded;
    }

    /**
     * Get address balance (lovelace + assets)
     */
    public static function getAddressBalance($address, $network = 'preprod') {
        $result = self::call('addresses/' . $address, $network);

        if (is_wp_error($result)) {
            // Not-found = zero balance
            if ($result->get_error_code() === 'not_found') {
                return ['lovelace' => 0, 'assets' => []];
            }
            return $result;
        }

        $lovelace = 0;
        $assets = [];

        if (isset($result['amount']) && is_array($result['amount'])) {
            foreach ($result['amount'] as $item) {
                if ($item['unit'] === 'lovelace') {
                    $lovelace = intval($item['quantity']);
                } else {
                    $assets[] = [
                        'unit' => $item['unit'],
                        'quantity' => $item['quantity'],
                        'policy_id' => substr($item['unit'], 0, 56),
                        'asset_name_hex' => substr($item['unit'], 56),
                        'asset_name' => self::hexToAscii(substr($item['unit'], 56)),
                    ];
                }
            }
        }

        return ['lovelace' => $lovelace, 'assets' => $assets];
    }

    /**
     * Get address UTxOs
     */
    public static function getAddressUtxos($address, $network = 'preprod') {
        $result = self::call('addresses/' . $address . '/utxos', $network);

        if (is_wp_error($result)) {
            if ($result->get_error_code() === 'not_found') {
                return [];
            }
            return $result;
        }

        return $result;
    }

    /**
     * Check if API key is configured for the given network
     */
    public static function hasApiKey($network = 'preprod') {
        $config = self::getApiConfig($network);
        return !empty($config['api_key']);
    }

    /**
     * Get on-chain metadata for a specific asset (for NFT image, name, etc.)
     */
    public static function getAssetInfo($unit, $network = 'preprod') {
        $result = self::call('assets/' . $unit, $network);

        if (is_wp_error($result)) {
            return $result;
        }

        $info = [
            'name' => '',
            'image' => '',
            'metadata' => [],
            'fingerprint' => $result['fingerprint'] ?? '',
            'mint_quantity' => $result['quantity'] ?? '',
        ];

        // CIP-25 on-chain metadata
        $meta = $result['onchain_metadata'] ?? null;
        if ($meta) {
            $info['name'] = $meta['name'] ?? '';

            // Image can be a string or array of chunks
            $image = $meta['image'] ?? '';
            if (is_array($image)) {
                $image = implode('', $image);
            }
            $info['image'] = self::ipfsToHttp($image);

            // Pass all metadata fields for the detail card
            $info['metadata'] = self::flattenMetadata($meta);
        }

        return $info;
    }

    /**
     * Convert ipfs:// URI to an HTTP gateway URL
     */
    public static function ipfsToHttp($uri) {
        if (empty($uri)) {
            return '';
        }

        // Already HTTP
        if (strpos($uri, 'http') === 0) {
            return $uri;
        }

        // ipfs:// protocol
        if (strpos($uri, 'ipfs://') === 0) {
            $cid = substr($uri, 7);
            return 'https://ipfs.io/ipfs/' . $cid;
        }

        // Raw CID (starts with Qm or bafy)
        if (preg_match('/^(Qm|bafy)/', $uri)) {
            return 'https://ipfs.io/ipfs/' . $uri;
        }

        return $uri;
    }

    /**
     * Flatten CIP-25 metadata into key/value pairs for display.
     * Joins array values (chunked strings) and converts ipfs:// URIs.
     */
    private static function flattenMetadata($meta, $prefix = '') {
        $flat = [];

        foreach ($meta as $key => $value) {
            $label = $prefix ? $prefix . '.' . $key : $key;

            if (is_array($value)) {
                // Check if it's a simple list of strings (chunked text)
                $all_strings = true;
                foreach ($value as $v) {
                    if (!is_string($v)) { $all_strings = false; break; }
                }

                if ($all_strings && !empty($value)) {
                    $joined = implode('', $value);
                    // Convert ipfs links
                    if (strpos($joined, 'ipfs') !== false) {
                        $joined = self::ipfsToHttp($joined);
                    }
                    $flat[$label] = $joined;
                } else {
                    // Nested object — recurse
                    $flat = array_merge($flat, self::flattenMetadata($value, $label));
                }
            } else {
                $str = (string) $value;
                if (strpos($str, 'ipfs://') === 0 || preg_match('/^(Qm|bafy)/', $str)) {
                    $str = self::ipfsToHttp($str);
                }
                $flat[$label] = $str;
            }
        }

        return $flat;
    }

    /**
     * Convert hex-encoded asset name to ASCII
     */
    private static function hexToAscii($hex) {
        if (empty($hex)) {
            return '';
        }

        $ascii = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $char = chr(intval(substr($hex, $i, 2), 16));
            // Only include printable ASCII
            if (ctype_print($char)) {
                $ascii .= $char;
            } else {
                return $hex; // Return raw hex if not printable
            }
        }

        return $ascii;
    }
}
