<?php

require_once __DIR__ . '/cj-config.php';



class CJApiClient
{
    private $cfg;
    private $token = null;
    private static $lastRequestAt = 0;

    public function __construct()
    {
        global $cj_config;
        $this->cfg = $cj_config;
    }

    /* ---------- low level ---------- */

    private function httpJson($url, $body = null, $headers = [])
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json'], $headers),
        ]);
        if ($body !== null) {
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($body),
            ]);
        }
        $result = curl_exec($ch);
        $err    = curl_error($ch);
        // curl_close() is deprecated in PHP 8.5 (curl handles are auto-closed now)
        if ($result === false) {
            return ['code' => -1, 'message' => 'Network error: ' . $err];
        }
        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : ['code' => -2, 'message' => 'Invalid JSON from CJ API'];
    }

    private function throttle()
    {
        $minGap = 0.5; // seconds between API calls
        $wait   = $minGap - (microtime(true) - self::$lastRequestAt);
        if ($wait > 0) {
            usleep((int)($wait * 1000000));
        }
        self::$lastRequestAt = microtime(true);
    }

    private function cacheFile()
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'cj_token.json';
    }

    /* ---------- auth ---------- */

    public function getToken($force = false)
    {
        if (!$force && $this->token) {
            return $this->token;
        }
        if (!$force && is_file($this->cacheFile())) {
            $cache = json_decode((string)file_get_contents($this->cacheFile()), true);
            if (!empty($cache['token']) && !empty($cache['expires']) && $cache['expires'] > time()) {
                $this->token = $cache['token'];
                return $this->token;
            }
        }
        $res = $this->httpJson($this->cfg['base_url'] . '/authentication/getAccessToken', [
            'email'    => $this->cfg['email'],
            'password' => $this->cfg['api_password'],
        ]);
        if (!empty($res['data']['accessToken'])) {
            $this->token = $res['data']['accessToken'];
            $ttl = 15 * 86400;
            if (!empty($res['data']['expiresIn']) && is_numeric($res['data']['expiresIn'])) {
                $ttl = (int)$res['data']['expiresIn'];
            } elseif (!empty($res['data']['accessTokenExpiryDate'])) {
                $ts = strtotime((string)$res['data']['accessTokenExpiryDate']);
                if ($ts > time()) {
                    $ttl = $ts - time();
                }
            }
            @file_put_contents($this->cacheFile(), json_encode([
                'token'   => $this->token,
                'expires' => time() + max(3600, min($ttl, 14 * 86400)),
            ]));
            return $this->token;
        }
        throw new RuntimeException('CJ authentication failed: ' . ($res['message'] ?? 'unknown error'));
    }

    /** True when the CJ response indicates an authentication/token problem. */
    private static function isAuthError($res)
    {
        if (!is_array($res)) {
            return false;
        }
        $code = (int)($res['code'] ?? -1);
        // 16900207 = invalid/expired access token, 401 = unauthorized
        if ($code === 16900207 || $code === 401) {
            return true;
        }
        $msg = (string)($res['message'] ?? '');
        return $msg !== '' && (stripos($msg, 'token') !== false || stripos($msg, 'unauthorized') !== false);
    }

    /* ---------- generic call ---------- */

    public function call($path, $params = [])
    {
        $url = $this->cfg['base_url'] . $path;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        $this->throttle();
        $res = $this->httpJson($url, null, ['CJ-Access-Token: ' . $this->getToken()]);

        // Stale/invalid token -> force a fresh one and retry once
        $code = (int)($res['code'] ?? -1);
        if ($code !== 200 && self::isAuthError($res)) {
            $this->throttle();
            $res = $this->httpJson($url, null, ['CJ-Access-Token: ' . $this->getToken(true)]);
            $code = (int)($res['code'] ?? -1);
        }
        if ($code !== 200) {
            throw new RuntimeException('CJ API error (' . $code . '): ' . ($res['message'] ?? 'unknown error'));
        }
        return $res['data'] ?? [];
    }

    /* ---------- endpoints ---------- */

    public function getCategories()   { return $this->call('/product/getCategory'); }
    public function getProducts($p)
    {
        // /product/list wraps its payload under 'data': {code, message, data:{pageNum, pageSize, total, list:[...]}}
        $raw = $this->callRaw('/product/list', $p);
        if (!is_array($raw) || (int)($raw['code'] ?? -1) !== 200) {
            return $raw;
        }
        $data = $raw['data'] ?? [];
        return [
            'pageNum'  => (int)($data['pageNum'] ?? 0),
            'pageSize' => (int)($data['pageSize'] ?? 0),
            'total'    => (int)($data['total'] ?? 0),
            'list'     => $this->extractList($data),
        ];
    }

    /** Call an endpoint and return the full decoded response (code/message/data intact). */
    private function callRaw($path, $params = [])
    {
        $url = $this->cfg['base_url'] . $path;
        if ($params) {
            $url .= '?' . http_build_query($params);
        }
        $this->throttle();
        $res = $this->httpJson($url, null, ['CJ-Access-Token: ' . $this->getToken()]);

        // stale / invalid token -> force a fresh one and retry once
        $code = (int)($res['code'] ?? -1);
        if ($code !== 200 && self::isAuthError($res)) {
            $this->throttle();
            $res = $this->httpJson($url, null, ['CJ-Access-Token: ' . $this->getToken(true)]);
        }
        return $res;
    }
    public function getProduct($pid)  { return $this->call('/product/query', ['pid' => $pid]); }

    /**
     * Test whether the CJ API is reachable with the current credentials.
     * Returns ['connected' => bool, 'message' => string, 'last_test' => int].
     */
    public function checkConnection()
    {
        try {
            $this->throttle();
            $res = $this->callRaw('/product/list', ['page' => 1, 'pageSize' => 1]);
            $code = (int)($res['code'] ?? -1);
            if ($code === 200) {
                $GLOBALS['cj_config']['api_connected'] = true;
                $GLOBALS['cj_config']['api_last_test'] = time();
                return ['connected' => true, 'message' => 'API reachable. Products: ' . count(self::extractList($res['data'] ?? []))];
            }
            $msg = $res['message'] ?? 'unknown error';
            return ['connected' => false, 'message' => "API error ($code): $msg"];
        } catch (\Throwable $e) {
            return ['connected' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

    /** Clear the cached token (force re-auth on next call). */
    public function clearTokenCache()
    {
        $f = $this->cacheFile();
        if (file_exists($f)) {
            @unlink($f);
        }
        $this->token = null;
    }

    /** Extract the product array from a list response regardless of key name. */
    public static function extractList($data)
    {
        if (!is_array($data)) return [];
        foreach (['list', 'productList', 'products', 'result'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) return $data[$key];
        }
        if (isset($data[0])) return $data;
        return [];
    }

    /** Return the first existing key of $arr among $keys. */
    public static function pick($arr, $keys, $default = '')
    {
        foreach ($keys as $k) {
            if (is_array($arr) && isset($arr[$k]) && $arr[$k] !== '' && $arr[$k] !== null) {
                return $arr[$k];
            }
        }
        return $default;
    }
}