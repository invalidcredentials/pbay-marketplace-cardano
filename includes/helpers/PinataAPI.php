<?php
namespace PBay\Helpers;

class PinataAPI {

    /**
     * Upload image to Pinata IPFS and get CIDv0
     */
    public static function uploadImage($file_path, $name = '') {
        $jwt = get_option('pbay_pinata_jwt', '');
        $api_key = get_option('pbay_pinata_api_key', '');
        $secret_key = get_option('pbay_pinata_secret_key', '');

        $headers = [];
        if (!empty($jwt)) {
            $headers['Authorization'] = 'Bearer ' . $jwt;
        } elseif (!empty($api_key) && !empty($secret_key)) {
            $headers['pinata_api_key'] = $api_key;
            $headers['pinata_secret_api_key'] = $secret_key;
        } else {
            return new \WP_Error('pinata_no_credentials', 'Pinata credentials not configured.');
        }

        if (!file_exists($file_path)) {
            return new \WP_Error('file_not_found', 'Image file not found: ' . $file_path);
        }

        $mime_type = mime_content_type($file_path);
        if (!$mime_type || strpos($mime_type, 'image/') !== 0) {
            return new \WP_Error('invalid_file', 'File is not a valid image');
        }

        if (empty($name)) {
            $name = basename($file_path);
        }

        $url = 'https://api.pinata.cloud/pinning/pinFileToIPFS';
        $ch = curl_init($url);

        $post_fields = [
            'pinataOptions' => json_encode(['cidVersion' => 0, 'wrapWithDirectory' => false]),
            'pinataMetadata' => json_encode([
                'name' => $name,
                'keyvalues' => ['app' => 'pbay', 'kind' => 'listing-image'],
            ]),
            'file' => new \CURLFile($file_path, $mime_type, basename($file_path)),
        ];

        $curl_headers = [];
        foreach ($headers as $key => $value) {
            $curl_headers[] = "$key: $value";
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_HTTPHEADER => $curl_headers,
            CURLOPT_TIMEOUT => 120,
        ]);

        $response_body = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return new \WP_Error('pinata_curl_error', 'cURL error: ' . $curl_error);
        }

        if ($status_code !== 200) {
            return new \WP_Error('pinata_upload_failed', 'Pinata upload failed: ' . $status_code . ' - ' . $response_body);
        }

        $result = json_decode($response_body, true);
        $cid = $result['IpfsHash'] ?? $result['ipfsHash'] ?? '';

        if (empty($cid)) {
            return new \WP_Error('pinata_no_cid', 'Pinata did not return a CID');
        }

        return [
            'cid' => $cid,
            'mediaType' => $mime_type,
        ];
    }

    /**
     * Test Pinata connection
     */
    public static function testConnection() {
        $jwt = get_option('pbay_pinata_jwt', '');

        if (empty($jwt)) {
            return new \WP_Error('pinata_no_jwt', 'Pinata JWT token not configured');
        }

        $response = wp_remote_get('https://api.pinata.cloud/data/pinList?pageLimit=1', [
            'headers' => ['Authorization' => 'Bearer ' . $jwt],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code === 200) {
            return ['success' => true, 'message' => 'Pinata connection successful'];
        }

        return new \WP_Error('pinata_test_failed', 'Pinata test failed: HTTP ' . $status_code);
    }
}
