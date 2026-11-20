<?php

/**
 * OpenAPI 配置文件
 * 包含所有示例文件共用的配置信息
 */

// 加载环境配置
require_once __DIR__ . '/env.php';

// OpenAPI 服务配置（密钥请放在项目根目录 .env，勿写入代码）
define('OPENAPI_HOST', EnvLoader::get('OPENAPI_HOST', 'https://openapi.lingxing.com'));
define('OPENAPI_APP_ID', EnvLoader::get('OPENAPI_APP_ID', ''));
define('OPENAPI_APP_SECRET', EnvLoader::get('OPENAPI_APP_SECRET', ''));

// 示例配置
define('SAMPLE_SID', EnvLoader::get('SAMPLE_SID', ''));
define('SAMPLE_WID', EnvLoader::get('SAMPLE_WID', ''));
define('DEFAULT_OFFSET', (int)EnvLoader::get('DEFAULT_OFFSET', '0'));
define('DEFAULT_LENGTH', (int)EnvLoader::get('DEFAULT_LENGTH', '20'));
define('MAX_LENGTH', (int)EnvLoader::get('MAX_LENGTH', '800'));

/**
 * 创建 OpenAPI 客户端实例
 * 
 * @return \Sxqibo\FastLingxing\Services\OpenAPIRequestService
 * @throws \Sxqibo\FastLingxing\Exception\RequiredParamsEmptyException
 * @throws \RuntimeException
 */
function createOpenAPIClient()
{
    if (OPENAPI_APP_ID === '' || OPENAPI_APP_SECRET === '') {
        throw new \RuntimeException(
            '请先配置 .env 中的 OPENAPI_APP_ID、OPENAPI_APP_SECRET（可参考 .env.example）'
        );
    }

    return new \Sxqibo\FastLingxing\Services\OpenAPIRequestService(
        OPENAPI_HOST,
        OPENAPI_APP_ID,
        OPENAPI_APP_SECRET
    );
}

/**
 * 脱敏展示 Token（避免示例输出被误提交）
 *
 * @param string $token
 * @return string
 */
function maskSecret($token)
{
    $token = (string) $token;
    $len = strlen($token);
    if ($len <= 8) {
        return str_repeat('*', $len);
    }
    return substr($token, 0, 4) . str_repeat('*', max(0, $len - 8)) . substr($token, -4);
}

/**
 * 显示 AccessToken 信息
 * 
 * @param \Sxqibo\FastLingxing\Dto\AccessTokenDto $accessTokenDto
 */
function displayAccessTokenInfo($accessTokenDto)
{
    echo "AccessToken生成成功\n";
    echo "AccessToken: " . maskSecret($accessTokenDto->getAccessToken()) . "\n";
    echo "RefreshToken: " . maskSecret($accessTokenDto->getRefreshToken()) . "\n";
    echo "过期时间: " . date('Y-m-d H:i:s', $accessTokenDto->getExpireAt()) . "\n\n";
}

/**
 * 显示响应基本信息
 * 
 * @param array $response
 */
function displayResponseInfo($response)
{
    echo "请求成功！\n";
    echo "状态码: " . $response['code'] . "\n";
    echo "消息: " . ($response['message'] ?? $response['msg'] ?? 'N/A') . "\n";
    echo "请求ID: " . ($response['request_id'] ?? $response['trace_id'] ?? 'N/A') . "\n";
    if (isset($response['response_time'])) {
    echo "响应时间: " . $response['response_time'] . "\n";
    }
}

/**
 * 显示错误信息
 * 
 * @param array $response
 */
function displayErrorInfo($response)
{
    echo "查询失败，错误信息: " . $response['message'] . "\n";
    if (!empty($response['error_details'])) {
        echo "错误详情: " . json_encode($response['error_details'], JSON_UNESCAPED_UNICODE) . "\n";
    }
}
