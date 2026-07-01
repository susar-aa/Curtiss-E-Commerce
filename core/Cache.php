<?php
class Cache {
    private static $cachedData = [];

    private static function getCacheDir() {
        $dir = sys_get_temp_dir() . '/curtiss_cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }

    public static function get($key) {
        // 1. Check in-memory static array (very fast per-request cache)
        if (isset(self::$cachedData[$key])) {
            return self::$cachedData[$key];
        }

        // 2. Try APCu if available
        if (function_exists('apcu_exists') && apcu_exists($key)) {
            $val = apcu_fetch($key);
            self::$cachedData[$key] = $val;
            return $val;
        }

        // 3. Fallback to file-based cache
        $file = self::getCacheDir() . '/' . md5($key) . '.cache';
        if (file_exists($file)) {
            $data = @json_decode(file_get_contents($file), true);
            if ($data && isset($data['expires']) && $data['expires'] > time()) {
                $val = unserialize($data['value']);
                self::$cachedData[$key] = $val;
                return $val;
            } else {
                @unlink($file);
            }
        }

        return null;
    }

    public static function set($key, $value, $ttl = 3600) {
        self::$cachedData[$key] = $value;

        // 1. Try APCu if available
        if (function_exists('apcu_store')) {
            apcu_store($key, $value, $ttl);
            return;
        }

        // 2. Fallback to file-based cache
        $file = self::getCacheDir() . '/' . md5($key) . '.cache';
        $data = [
            'expires' => time() + $ttl,
            'value' => serialize($value)
        ];
        @file_put_contents($file, json_encode($data));
    }

    public static function delete($key) {
        unset(self::$cachedData[$key]);

        if (function_exists('apcu_delete')) {
            apcu_delete($key);
        }

        $file = self::getCacheDir() . '/' . md5($key) . '.cache';
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public static function clear() {
        self::$cachedData = [];

        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }

        $dir = self::getCacheDir();
        $files = glob($dir . '/*.cache');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}
