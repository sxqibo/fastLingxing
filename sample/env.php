<?php

/**
 * 环境配置加载器
 * 用于加载 .env 文件中的配置
 */

class EnvLoader
{
    private static $loaded = false;
    private static $config = [];

    /**
     * 加载环境配置
     */
    public static function load($envFile = null)
    {
        if (self::$loaded) {
            return;
        }

        $envFile = $envFile ?: __DIR__ . '/../.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // 跳过注释行
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // 解析键值对
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // 移除引号
                    if (($value[0] === '"' && $value[-1] === '"') || 
                        ($value[0] === "'" && $value[-1] === "'")) {
                        $value = substr($value, 1, -1);
                    }
                    
                    self::$config[$key] = $value;
                }
            }
        }
        
        self::$loaded = true;
    }

    /**
     * 获取配置值
     */
    public static function get($key, $default = null)
    {
        self::load();
        return isset(self::$config[$key]) ? self::$config[$key] : $default;
    }

    /**
     * 获取所有配置
     */
    public static function all()
    {
        self::load();
        return self::$config;
    }
}

// 自动加载环境配置
EnvLoader::load();
