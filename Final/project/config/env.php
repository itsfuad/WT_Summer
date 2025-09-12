<?php

class EnvReader {
    private static $data = [];
    private static $loaded = false;

    public static function load($envFile = null) {
        if (self::$loaded) {
            return true;
        }

        if ($envFile === null) {
            $envFile = __DIR__ . '/../.env';
        }

        if (!file_exists($envFile)) {
            error_log("Environment file not found: " . $envFile);
            return false;
        }

        $content = file_get_contents($envFile);
        if ($content === false) {
            error_log("Could not read environment file: " . $envFile);
            return false;
        }

        self::parse($content);
        self::$loaded = true;
        
        // Define constants for all environment variables
        foreach (self::$data as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
            }
        }
        
        return true;
    }

    private static function parse($content) {
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim(str_replace("\r", "", $line));
            
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            $commentPos = strpos($line, '#');
            if ($commentPos !== false) {
                $beforeComment = substr($line, 0, $commentPos);
                $quoteCount = substr_count($beforeComment, '"') + substr_count($beforeComment, "'");
                
                if ($quoteCount % 2 === 0) {
                    $line = substr($line, 0, $commentPos);
                    $line = trim($line);
                }
            }
            
            if (empty($line)) {
                continue;
            }
            
            $parts = explode('=', $line, 2);
            
            if (count($parts) !== 2) {
                continue;
            }
            
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            
            if (strlen($value) >= 2) {
                $firstChar = $value[0];
                $lastChar = $value[strlen($value) - 1];
                
                if (($firstChar === '"' && $lastChar === '"') ||
                    ($firstChar === "'" && $lastChar === "'")) {
                    $value = substr($value, 1, -1);
                    $value = trim($value);
                }
            }
            
            self::$data[$key] = $value;
        }
    }

    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        return isset(self::$data[$key]) ? self::$data[$key] : $default;
    }

    public static function has($key) {
        if (!self::$loaded) {
            self::load();
        }
        
        return isset(self::$data[$key]);
    }

    public static function all() {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$data;
    }

    public static function reload($envFile = null) {
        self::$data = [];
        self::$loaded = false;
        return self::load($envFile);
    }

    public static function clear() {
        self::$data = [];
        self::$loaded = false;
    }
}

function env($key, $default = null) {
    return EnvReader::get($key, $default);
}
