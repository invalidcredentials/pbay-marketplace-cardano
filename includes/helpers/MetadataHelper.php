<?php
namespace PBay\Helpers;

/**
 * CIP-25 metadata builder with 64-char chunking
 * Ported from anvil-playground metadata-utils.ts
 */
class MetadataHelper {

    /**
     * Chunk a string into 64-character segments for CIP-25 compliance
     */
    public static function chunkText($text) {
        if (strlen($text) <= 64) {
            return $text;
        }

        $chunks = [];
        $len = strlen($text);
        for ($i = 0; $i < $len; $i += 64) {
            $chunks[] = substr($text, $i, 64);
        }
        return $chunks;
    }

    /**
     * Build CIP-25 metadata object from listing data + meta attributes
     *
     * @param array $listing Listing row from DB
     * @param array $meta_rows Array of ['meta_key' => ..., 'meta_value' => ...] rows
     * @return array CIP-25 metadata object ready for Anvil API
     */
    public static function buildCIP25Metadata($listing, $meta_rows = []) {
        // Resolve image URL: manual IPFS > pinata IPFS > WP media
        $image = '';
        if (!empty($listing['ipfs_cid_manual'])) {
            $image = 'ipfs://' . $listing['ipfs_cid_manual'];
        } elseif (!empty($listing['ipfs_cid'])) {
            $image = 'ipfs://' . $listing['ipfs_cid'];
        } elseif (!empty($listing['image_id'])) {
            $url = wp_get_attachment_url($listing['image_id']);
            if ($url) {
                $image = $url;
            }
        }

        // Core CIP-25 fields
        $metadata = [
            'name' => self::chunkText($listing['title'] ?? 'Untitled'),
            'image' => self::chunkText($image),
            'mediaType' => 'image/png',
        ];

        // Description (auto-chunked)
        if (!empty($listing['description'])) {
            $metadata['description'] = self::chunkText($listing['description']);
        }

        // Marketplace-specific fields
        if (!empty($listing['price_usd'])) {
            $metadata['priceUSD'] = number_format(floatval($listing['price_usd']), 2, '.', '');
        }
        if (!empty($listing['category'])) {
            $metadata['category'] = self::chunkText($listing['category']);
        }
        if (!empty($listing['condition_type'])) {
            $metadata['condition'] = $listing['condition_type'];
        }
        if (!empty($listing['quantity'])) {
            $metadata['quantity'] = strval(intval($listing['quantity']));
        }

        $store_name = get_option('pbay_store_name', get_bloginfo('name'));
        $metadata['seller'] = self::chunkText($store_name);

        // files array for explorer compatibility
        $files = [];
        if (!empty($image)) {
            $raw_image = $image;
            if (!empty($listing['ipfs_cid_manual'])) {
                $raw_image = 'ipfs://' . $listing['ipfs_cid_manual'];
            } elseif (!empty($listing['ipfs_cid'])) {
                $raw_image = 'ipfs://' . $listing['ipfs_cid'];
            }
            $files[] = [
                'name' => self::chunkText($listing['title'] ?? 'Untitled'),
                'mediaType' => 'image/png',
                'src' => self::chunkText($raw_image),
            ];
        }

        // Gallery images
        $gallery_ids = array_filter(explode(',', $listing['gallery_ids'] ?? ''));
        $gallery_cids = array_filter(explode(',', $listing['gallery_ipfs_cids'] ?? ''), 'strlen');

        foreach ($gallery_ids as $i => $gid) {
            $gallery_src = '';
            if (isset($gallery_cids[$i]) && !empty($gallery_cids[$i])) {
                $gallery_src = 'ipfs://' . $gallery_cids[$i];
            } else {
                $url = wp_get_attachment_url(intval($gid));
                if ($url) {
                    $gallery_src = $url;
                }
            }
            if (!empty($gallery_src)) {
                $label = ($listing['title'] ?? 'Image') . ' ' . ($i + 2);
                $files[] = [
                    'name' => self::chunkText($label),
                    'mediaType' => 'image/png',
                    'src' => self::chunkText($gallery_src),
                ];
            }
        }

        if (!empty($files)) {
            $metadata['files'] = $files;
        }

        // Dynamic attributes from listing_meta (attr_* prefix)
        foreach ($meta_rows as $row) {
            $key = $row['meta_key'];
            $value = $row['meta_value'];
            if (!empty($key) && $value !== '' && $value !== null) {
                $attr_key = 'attr_' . preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($key));
                $metadata[$attr_key] = self::chunkText($value);
            }
        }

        return $metadata;
    }

    /**
     * Validate that all metadata values fit within 64-char limits
     */
    public static function validateMetadata($metadata) {
        $errors = [];

        foreach ($metadata as $key => $value) {
            if (is_string($value) && strlen($value) > 64) {
                $errors[] = "Field '{$key}' exceeds 64 characters and was not chunked";
            }
            if (is_array($value) && !isset($value[0])) {
                // Nested object (like files) - validate recursively
                foreach ($value as $nested) {
                    if (is_array($nested)) {
                        $nested_errors = self::validateMetadata($nested);
                        $errors = array_merge($errors, $nested_errors);
                    }
                }
            }
        }

        return $errors;
    }
}
