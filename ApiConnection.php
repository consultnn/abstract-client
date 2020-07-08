<?php

namespace consultnn\baseapi;

use consultnn\baseapi\exceptions\ConnectionException;

/**
 * Class ApiConnection
 * @package DGApiClient
 */
class ApiConnection
{
    /**
     * Types of request.
     * Set in curl option CURLOPT_CUSTOMREQUEST.
     * Use one of defined ApiConnection::HTTP_REQUEST_* constants
     */
    const HTTP_REQUEST_POST = 'POST';
    const HTTP_REQUEST_PATCH = 'PATCH';
    const HTTP_REQUEST_PUT = 'PUT';
    const HTTP_REQUEST_GET = 'GET';
    const HTTP_REQUEST_HEAD = 'HEAD';
    const HTTP_REQUEST_OPTIONS = 'OPTIONS';
    const HTTP_REQUEST_DELETE = 'DELETE';

    /**
     * Formats of response
     */
    const FORMAT_JSON = 'json';
    const FORMAT_JSONP = 'jsonp';
    const FORMAT_XML = 'xml';

    /* @var \Psr\Log\LoggerInterface */
    private $_logger;

    /* @var string $url url to API */
    public $url;

    /* @var string $version api version */
    public $version = 'v1';

    public $formatParam = '_format';

    /* @var string $locale */
    public $locale = 'ru_RU';

    public $responseEnvelope = 'result';

    /* @var int $timeout in milliseconds */
    public $timeout = 5000;

    /**
     * @var
     */
    public $curlOptions = [];

    /* @var resource $curl */
    protected $curl;

    /* @var string $format */
    private $format = self::FORMAT_JSON;

    /**
     * Throw exception or store it into $lastError variable
     * @var bool $raiseException
     */
    public $raiseException = true;

    /**
     * @var string
     */
    public $statusField = 'code';

    /**
     * This variable contains exception class if $raiseException is false.
     * @var \Exception
     */
    protected $lastError;

    /**
     * Meta data from request
     *
     * @var array
     */
    private $_meta;

    private $_httpRequestType;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct($logger = null)
    {
        $this->_logger = $logger;
    }

    /**
     * Returns last Exception if $raiseException is false
     * @return \Exception
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @param string $value
     * @return string
     * @throws ConnectionException
     */
    public function setFormat($value)
    {
        $value = strtolower($value);
        if (in_array($value, self::getFormats())) {
            return $this->format = $value;
        } else {
            throw new ConnectionException("Unknown format $value");
        }
    }

    private static function getFormats()
    {
        return [
            self::FORMAT_JSON,
            self::FORMAT_JSONP,
            self::FORMAT_XML,
        ];
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    private static function isSendInPost()
    {
        return [
            self::HTTP_REQUEST_POST,
            self::HTTP_REQUEST_PATCH,
            self::HTTP_REQUEST_PUT,
        ];
    }

    /**
     * @param string $service
     * @param array $params
     * @param string $httpRequestType Value for curl option CURLOPT_CUSTOMREQUEST. Use one of defined ApiConnection::HTTP_REQUEST_* constants
     *
     *
     * @return array|string
     * @throws ConnectionException
     */
    public function send($service, array $params = [], $httpRequestType = self::HTTP_REQUEST_GET)
    {
        $this->_httpRequestType = $httpRequestType;

        $curl = $this->getCurl();

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $httpRequestType);

        $url = $this->getRequest($service, $params);
        curl_setopt($this->curl,CURLOPT_URL, $url);
        $data = curl_exec($curl);

        if (curl_errno($curl)) {
            return $this->raiseException(curl_error($curl) . ' on ' . $url, curl_errno($curl), null, 'CURL');
        }

        $response = $this->decodeResponse($data);
        if ($this->getFormat() === 'xml') {
            return $data;
        }

        if (!$response || isset($response[$this->statusField])) {
            return $this->raiseException(
                "Invalid response message on " . $url,
                isset($response[$this->statusField]) ? $response[$this->statusField] : null
            );
        }

        $this->lastError = null;

        if ($this->responseEnvelope) {
            $result = &$response[$this->responseEnvelope];
            unset($response[$this->responseEnvelope]);
        } else {
            $result = $response;
        }

        $this->_meta = $response;

        return $result;
    }

    /**
     * @param string $service
     * @param array $params
     * @return string
     */
    public function getRequest($service, array $params = [])
    {
        $params = array_filter($params);
        $params[$this->formatParam] = $this->format;
        $url = $this->url;

        if ($this->version) {
            $url .= '/' . $this->version;
        }

        $url .= '/' . $service;

        $query = http_build_query($params);
        if (in_array($this->_httpRequestType, self::isSendInPost())) {
            curl_setopt($this->getCurl(), CURLOPT_POSTFIELDS, $query);
        } else {
            $url .= '?' . $query;
        }

        if ($logger = $this->getLogger()) {
            $logger->info($url);
        }
        return $url;
    }

    /**
     * @param string $data
     * @return mixed
     */
    private function decodeResponse($data)
    {
        switch ($this->format) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case self::FORMAT_JSONP:
                $data = preg_replace("/ ^[?\w(]+ | [)]+\s*$ /x", "", $data); //JSONP -> JSON
            case self::FORMAT_JSON:
                return @json_decode($data, true);
        }
        return $data;
    }

    /**
     * @return resource
     */
    private function getCurl()
    {
        if ($this->curl === null) {
            $this->curl = curl_init();
            curl_setopt_array($this->curl, [
                CURLOPT_TIMEOUT_MS => $this->timeout,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_USERAGENT => 'PHP ' . __CLASS__,
                CURLOPT_ENCODING => 'gzip, deflate',
            ] + $this->curlOptions);
        }
        return $this->curl;
    }

    public function __destruct()
    {
        if ($this->curl !== null) {
            unset($this->curl);
        }
    }

    /**
     * @param string $message
     * @param int $code
     * @param \Exception $previous
     * @param string $type
     * @return bool
     * @throws ConnectionException
     */
    protected function raiseException($message = "", $code = 0, \Exception $previous = null, $type = "")
    {
        $exception = new ConnectionException($message, $code, $previous, $type);
        if ($logger = $this->getLogger()) {
            $logger->error("[$code]: $type $message", array('exception' => $exception));
        }
        if ($this->raiseException) {
            throw $exception;
        } else {
            $this->lastError = $exception;
        }
        return false;
    }

    /**
     * @param array $meta
     * @param string $defaultMessage
     * @return bool
     * @throws ConnectionException
     */
    protected function metaException(array $meta, $defaultMessage = "")
    {
        return $this->raiseException(
            isset($meta['error']['message']) ? $meta['error']['message'] : $defaultMessage,
            isset($meta['code']) ? $meta['code'] : 0,
            null,
            isset($meta['error']['type']) ? $meta['error']['type'] : ""
        );
    }

    /**
     * Success request meta data
     *
     * @return array
     */
    public function getMeta()
    {
        return $this->_meta;
    }

    /**
     * @return null|\Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->_logger;
    }
}
