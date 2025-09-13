<?php

/**
 * OpenAPI 配置文件
 * 包含所有示例文件共用的配置信息
 */

// 加载环境配置
require_once __DIR__ . '/env.php';

// OpenAPI 服务配置
define('OPENAPI_HOST', EnvLoader::get('OPENAPI_HOST', 'https://openapi.lingxing.com'));
define('OPENAPI_APP_ID', EnvLoader::get('OPENAPI_APP_ID', 'ak_vExirI3elUHhm'));
define('OPENAPI_APP_SECRET', EnvLoader::get('OPENAPI_APP_SECRET', '0GDoybXegoIoRHRlQRb1gg=='));

// 示例配置
define('SAMPLE_SID', EnvLoader::get('SAMPLE_SID', '10743'));
define('SAMPLE_WID', EnvLoader::get('SAMPLE_WID', '1,578,765'));
define('DEFAULT_OFFSET', (int)EnvLoader::get('DEFAULT_OFFSET', '0'));
define('DEFAULT_LENGTH', (int)EnvLoader::get('DEFAULT_LENGTH', '20'));
define('MAX_LENGTH', (int)EnvLoader::get('MAX_LENGTH', '800'));

/**
 * 创建 OpenAPI 客户端实例
 * 
 * @return \Sxqibo\FastLingxing\Services\OpenAPIRequestService
 * @throws \Sxqibo\FastLingxing\Exception\RequiredParamsEmptyException
 */
function createOpenAPIClient()
{
    return new \Sxqibo\FastLingxing\Services\OpenAPIRequestService(
        OPENAPI_HOST,
        OPENAPI_APP_ID,
        OPENAPI_APP_SECRET
    );
}

/**
 * 显示 AccessToken 信息
 * 
 * @param \Sxqibo\FastLingxing\Dto\AccessTokenDto $accessTokenDto
 */
function displayAccessTokenInfo($accessTokenDto)
{
    echo "AccessToken生成成功\n";
    echo "AccessToken: " . $accessTokenDto->getAccessToken() . "\n";
    echo "RefreshToken: " . $accessTokenDto->getRefreshToken() . "\n";
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
    echo "消息: " . $response['message'] . "\n";
    echo "请求ID: " . ($response['request_id'] ?? 'N/A') . "\n";
    echo "响应时间: " . $response['response_time'] . "\n";
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
