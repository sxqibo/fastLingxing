<?php

namespace Sxqibo\FastLingxing\Services;

use Sxqibo\FastLingxing\Dto\AccessTokenDto;
use Sxqibo\FastLingxing\Exception\GenerateAccessTokenException;
use Sxqibo\FastLingxing\Exception\InvalidAccessTokenException;
use Sxqibo\FastLingxing\Exception\RequiredParamsEmptyException;

class OpenAPIRequestService
{
    /** @var string openapi的域名 */
    private $host;
    /** @var string appID */
    private $appId;
    /** @var string appSecret */
    private $appSecret;
    /** @var AccessTokenDto */
    private $accessTokenDto;

    /**
     * @param string $host
     * @param string $appId
     * @param string $appSecret
     *
     * @throws \Sxqibo\FastLingxing\Exception\RequiredParamsEmptyException
     */
    public function __construct($host, $appId, $appSecret)
    {
        if (empty($host) || empty($appId) || empty($appSecret)) {
            throw new RequiredParamsEmptyException();
        }
        
        $this->host = rtrim($host, '/');
        $this->appId = $appId;
        $this->appSecret = !BaseRequestService::isUrlEncoded($appSecret) ? $appSecret : urldecode($appSecret);
    }

    /**
     * 请求OpenAPI接口
     *
     * @param string $routeName openapi接口路由，如/sc/oauth/service/getServiceStatus
     * @param string $method    请求方式
     * @param array  $params    请求参数
     * @param array  $headers   请求头，如需要
     *
     * @return array
     * @throws \Sxqibo\FastLingxing\Exception\InvalidAccessTokenException
     * @throws \Sxqibo\FastLingxing\Exception\InvalidResponseException
     * @throws \Sxqibo\FastLingxing\Exception\RequestException
     * @throws \Exception
     */
    public function makeRequest($routeName, $method, $params = [], $headers = [])
    {
        if ($this->accessTokenDto === null) {
            throw new InvalidAccessTokenException();
        }

        $timestamp = time();
        $qParams = [
            'access_token' => $this->accessTokenDto->getAccessToken(),
            'timestamp' => $timestamp,
            'app_key' => $this->appId
        ];
        $params = array_merge($params, $qParams);
        $method = strtoupper($method);
        if ($method === 'GET') {
            $qParams = $params;
        }
        $sign = SignService::makeSign($params, $this->appId);
        $qParams['sign'] = $sign;

        $headers[] = 'Content-Type: application/json';
        $url = BaseRequestService::pathJoin($this->host, $routeName);
        return BaseRequestService::sendRequest($method, BaseRequestService::buildHttpURL($url, BaseRequestService::makeQueryString($qParams)), json_encode($params), $headers);
    }

    /**
     * 获取AccessToken
     *
     * @return AccessTokenDto
     *
     * @return \Sxqibo\FastLingxing\Dto\AccessTokenDto
     * @throws \Sxqibo\FastLingxing\Exception\InvalidResponseException
     * @throws \Sxqibo\FastLingxing\Exception\RequestException
     */

    public function generateAccessToken()
    {
        $path = '/api/auth-server/oauth/access-token';
        $params = [
            'appId' => $this->appId,
            'appSecret' => $this->appSecret
        ];
        $url = BaseRequestService::pathJoin($this->host, $path);
        $res = BaseRequestService::sendPost(BaseRequestService::buildHttpURL($url, BaseRequestService::makeQueryString($params)));
        $this->generateAccessTokenDto(isset($res['data']) ? $res['data'] : []);
        return $this->accessTokenDto;
    }

    /**
     * 刷新AccessToken
     *
     * @param $refreshToken
     *
     * @return \Sxqibo\FastLingxing\Dto\AccessTokenDto
     * @throws \Sxqibo\FastLingxing\Exception\InvalidResponseException
     * @throws \Sxqibo\FastLingxing\Exception\RequestException
     */
    public function refreshToken($refreshToken)
    {
        $path = '/api/auth-server/oauth/refresh';
        $params = [
            'appId' => $this->appId,
            'refreshToken' => $refreshToken,
        ];

        $url = BaseRequestService::pathJoin($this->host, $path);
        $res = BaseRequestService::sendPost(BaseRequestService::buildHttpURL($url, BaseRequestService::makeQueryString($params)));
        $this->generateAccessTokenDto(isset($res['data']) ? $res['data'] : []);
        return $this->accessTokenDto;
    }

    /**
     * 从接口中获取AccessTokenDto
     *
     * @param $res
     *
     * @return void
     */
    private function generateAccessTokenDto($res)
    {
        $accessToken = empty($res['access_token']) ? '' : $res['access_token'];
        $refreshToken = empty($res['refresh_token']) ? '' : $res['refresh_token'];
        $expireAt = time() + (empty($res['expires_in']) ? 0 : $res['expires_in']);
        $ato = new AccessTokenDto();
        $ato->setAccessToken($accessToken)
            ->setRefreshToken($refreshToken)
            ->setExpireAt($expireAt);
        $this->accessTokenDto = $ato;
    }

    /**
     * 根据响应结果更新AccessToken的结构体
     *
     * @param $accessToken
     * @param $refreshToken
     * @param $expireAt
     *
     * @throws \Sxqibo\FastLingxing\Exception\GenerateAccessTokenException
     */
    public function setAccessToken($accessToken, $refreshToken = '', $expireAt = '')
    {
        if (empty($accessToken)) {
            throw new GenerateAccessTokenException();
        }

        $ato = new AccessTokenDto();
        $ato->setAccessToken($accessToken);
        if ($refreshToken) {
            $ato->setRefreshToken($refreshToken);
        }
        if ($expireAt) {
            $ato->setExpireAt($expireAt);
        }
        $this->accessTokenDto = $ato;
    }
}