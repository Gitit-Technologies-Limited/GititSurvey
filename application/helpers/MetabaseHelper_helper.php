<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * MetabaseHelper
 *
 * Helper class for Metabase integration with LimeSurvey
 * Handles embed URL generation, dashboard creation, and API interactions
 *
 * @package LimeSurvey
 * @subpackage Helpers
 */
class MetabaseHelper
{
    /**
     * Check if Metabase integration is enabled
     *
     * @return bool
     */
    public static function isEnabled()
    {
        return (bool) Yii::app()->getConfig('metabaseEnabled');
    }

    /**
     * Generate a signed embed URL for a Metabase dashboard
     *
     * @param int $dashboardId The Metabase dashboard ID
     * @param array $params Parameters to pass to the dashboard (e.g., surveyid)
     * @param array $options Additional options (theme, bordered, titled, etc.)
     * @return string|null The signed embed URL or null if disabled
     */
    public static function generateEmbedUrl($dashboardId, $params = [], $options = [])
    {
        if (!self::isEnabled()) {
            return null;
        }

        $secretKey = Yii::app()->getConfig('metabaseSecretKey');
        $metabaseUrl = Yii::app()->getConfig('metabasePublicUrl');

        if (empty($secretKey) || empty($metabaseUrl)) {
            Yii::log('Metabase configuration incomplete', 'error', 'application.helpers.MetabaseHelper');
            return null;
        }

        // Build payload for JWT token
        $payload = [
            'resource' => ['dashboard' => (int)$dashboardId],
            'params' => $params,
            'exp' => time() + 600, // Token expires in 10 minutes
        ];

        // Add optional display settings
        if (isset($options['theme'])) {
            $payload['theme'] = $options['theme'];  // 'transparent', 'night', etc.
        }
        if (isset($options['bordered'])) {
            $payload['bordered'] = (bool)$options['bordered'];
        }
        if (isset($options['titled'])) {
            $payload['titled'] = (bool)$options['titled'];
        }

        // Generate JWT token
        $token = self::generateJWT($payload, $secretKey);

        // Build embed URL
        $embedUrl = rtrim($metabaseUrl, '/') . '/embed/dashboard/' . $token;

        // Add URL parameters for display options
        $urlParams = [];
        if (isset($options['hide_parameters'])) {
            $urlParams[] = 'hide_parameters=' . ($options['hide_parameters'] ? 'true' : 'false');
        }
        if (!empty($urlParams)) {
            $embedUrl .= '#' . implode('&', $urlParams);
        }

        return $embedUrl;
    }

    /**
     * Generate JWT token for Metabase embedding
     * Uses HS256 algorithm
     *
     * @param array $payload The JWT payload
     * @param string $secretKey The Metabase secret key
     * @return string The JWT token
     */
    private static function generateJWT($payload, $secretKey)
    {
        // Header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        // Encode header and payload
        $base64UrlHeader = self::base64UrlEncode(json_encode($header));
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));

        // Create signature
        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $secretKey, true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        // Build JWT
        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }

    /**
     * Base64 URL encode (JWT standard)
     *
     * @param string $data Data to encode
     * @return string Base64 URL encoded string
     */
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Get the Metabase dashboard ID for a survey
     *
     * @param int $surveyId The survey ID
     * @return int|null The dashboard ID or null if not found
     */
    public static function getDashboardIdForSurvey($surveyId)
    {
        // Option 1: Check for survey-specific dashboard mapping in database
        $setting = SettingGlobal::model()->findByPk("metabaseDashboard_{$surveyId}");
        if ($setting) {
            return (int)$setting->stg_value;
        }

        // Option 2: Use default dashboard with surveyid parameter
        $defaultDashboardId = Yii::app()->getConfig('metabaseDefaultDashboardId');
        if ($defaultDashboardId) {
            return (int)$defaultDashboardId;
        }

        // Option 3: Use convention (dashboard ID = survey ID)
        // Uncomment if you want to use this convention
        // return $surveyId;

        return null;
    }

    /**
     * Check if Metabase is available/reachable
     *
     * @return bool
     */
    public static function isAvailable()
    {
        if (!self::isEnabled()) {
            return false;
        }

        $metabaseUrl = Yii::app()->getConfig('metabaseSiteUrl');
        if (empty($metabaseUrl)) {
            return false;
        }

        try {
            $ch = curl_init($metabaseUrl . '/api/health');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
        } catch (Exception $e) {
            Yii::log('Metabase health check failed: ' . $e->getMessage(), 'warning', 'application.helpers.MetabaseHelper');
            return false;
        }
    }

    /**
     * Get embed iframe HTML for a dashboard
     *
     * @param int $surveyId The survey ID
     * @param array $options Display options
     * @return string|null HTML iframe code or null if disabled/unavailable
     */
    public static function getEmbedIframe($surveyId, $options = [])
    {
        if (!self::isEnabled() || !self::isAvailable()) {
            return null;
        }

        $dashboardId = self::getDashboardIdForSurvey($surveyId);
        if (!$dashboardId) {
            Yii::log('No dashboard ID found for survey ' . $surveyId, 'info', 'application.helpers.MetabaseHelper');
            return null;
        }

        $embedUrl = self::generateEmbedUrl($dashboardId, ['surveyid' => $surveyId], [
            'theme' => 'transparent',
            'bordered' => false,
            'titled' => true,
            'hide_parameters' => true,
        ]);

        if (!$embedUrl) {
            return null;
        }

        $height = isset($options['height']) ? $options['height'] : '800';
        $width = isset($options['width']) ? $options['width'] : '100%';

        return sprintf(
            '<iframe src="%s" frameborder="0" width="%s" height="%s" allowtransparency></iframe>',
            htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($width, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($height, ENT_QUOTES, 'UTF-8')
        );
    }
}
