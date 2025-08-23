<?php
/**
 * Lightweight helper for fetching Anthropic status (server-side)
 */
if (!defined('ABSPATH')) { exit; }

class NexusAIWPAnthropicStatus {
    public static function get_api_status() {
        $status_url = 'https://status.anthropic.com/api/v2/components.json';
        $response = wp_remote_get($status_url, array('timeout' => 8));
        if (is_wp_error($response)) {
            return new WP_Error('http_error', $response->get_error_message());
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($code !== 200 || empty($body)) {
            return new WP_Error('bad_status', 'Failed to fetch status');
        }
        $data = json_decode($body, true);
        if (!is_array($data) || !isset($data['components'])) {
            return new WP_Error('bad_format', 'Invalid status response');
        }
        $api_component = null;
        foreach ($data['components'] as $comp) {
            if (isset($comp['name']) && stripos($comp['name'], 'api.anthropic.com') !== false) {
                $api_component = $comp;
                break;
            }
        }
        if (!$api_component) {
            foreach ($data['components'] as $comp) {
                if (isset($comp['name']) && stripos($comp['name'], 'api') !== false) {
                    $api_component = $comp;
                    break;
                }
            }
        }
        if (!$api_component) {
            return new WP_Error('not_found', 'API component not found');
        }
        $status_map = array(
            'operational' => array('label' => __('Operational', 'nexus-ai-wp-translator'), 'level' => 'success'),
            'degraded_performance' => array('label' => __('Degraded', 'nexus-ai-wp-translator'), 'level' => 'warning'),
            'partial_outage' => array('label' => __('Partial Outage', 'nexus-ai-wp-translator'), 'level' => 'error'),
            'major_outage' => array('label' => __('Major Outage', 'nexus-ai-wp-translator'), 'level' => 'error'),
            'under_maintenance' => array('label' => __('Maintenance', 'nexus-ai-wp-translator'), 'level' => 'warning'),
        );
        $status = isset($api_component['status']) ? $api_component['status'] : 'unknown';
        $mapped = isset($status_map[$status]) ? $status_map[$status] : array('label' => ucfirst($status), 'level' => 'warning');
        return array(
            'status' => $status,
            'label' => $mapped['label'],
            'level' => $mapped['level'],
            'raw' => $api_component,
        );
    }
}

