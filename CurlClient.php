<?php

class CurlClient
{
    /** @var string */
    private $authHeader;

    /** @var array */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $authHeader
     */
    public function setAuthHeader($authHeader)
    {
        $this->authHeader = $authHeader;
    }

    /**
     * @return string
     * @throws MyException
     */
    public function getAuthHeader()
    {
        if (empty($this->authHeader)) {
            throw new MyException('Set AuthHeader before use it.');
        }

        return $this->authHeader;
    }

    /**
     * @param string $urlPath
     * @param bool $authRequired
     * @param bool|true $isPost
     * @param array $data
     * @param array $headers
     * @return bool|mixed
     * @throws MyException
     */
    function getResponse(
        string $urlPath,
        bool $authRequired = false,
        bool $isPost = false,
        array $data = [],
        array $headers = []
    ) {
        $url = $this->config['url'] . $urlPath;

        if ($authRequired) {
            $headers = array_merge([$this->getAuthHeader()], $headers);
        }

        echo getTimestamp() . ' - Send request to ' . $url . '......';

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL             => $url,
            CURLOPT_POST            => $isPost,
            CURLOPT_POSTFIELDS      => json_encode($data),
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => false,
            CURLOPT_FRESH_CONNECT   => true,
            CURLOPT_FORBID_REUSE    => true,
            CURLOPT_TIMEOUT         => 10,
        ]);

        $response = curl_exec ($ch);
        $responseStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close ($ch);

        if ($responseStatusCode != 200) {
            echo "FAIL\n";
            return false;
        }

        $response = json_decode($response, true);
        if (empty($response['result']['code'])) {
            echo "FAIL\n";
            return false;
        } elseif ($response['result']['code'] != 100) {
            echo "FAIL\n";
            throw new MyException('Invalid response code.', 'Url: '. $url, 'Result: ' . json_encode($response['result']));
        }
        echo "OK\n";

        unset($response['result']);

        return empty($response) ? true : $response;
    }
}