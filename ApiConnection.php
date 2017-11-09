<?php

namespace consultnn\baseapi;

use consultnn\baseapi\exceptions\ConnectionException;

/**
 * Class ApiConnection
 * @package DGApiClient
 */
class ApiConnection
{
    /* @var \Psr\Log\LoggerInterface */
    private $_logger;

    /* @var string $url url to API */
    public $url;

    /* @var string $version api version */
    public $version = 'v1';

    /* @var string $format */
    public $format = 'json';

    public $formatParam = '_format';

    /* @var string $locale */
    public $locale = 'ru_RU';

    /* @var int $timeout in milliseconds */
    public $timeout = 5000;

    /* @var resource $curl */
    protected $curl;

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
     * Meta data about request
     *
     * @var array
     */
    private $_meta;

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
        if (in_array($value, ['json', 'jsonp', 'xml'])) {
            return $this->format = $value;
        } else {
            throw new ConnectionException("Unknown format $value");
        }
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $service
     * @param array $params
     * @return array|string
     * @throws ConnectionException
     */
    public function send($service, array $params = [])
    {
        $curl = $this->getCurl();
        curl_setopt(
            $this->curl,
            CURLOPT_URL,
            $this->getRequest($service, $params)
        );
        $data = curl_exec($curl);

        if (curl_errno($curl)) {
            return $this->raiseException(curl_error($curl), curl_errno($curl), null, 'CURL');
        }

        $response = $this->decodeResponse($data);
        if ($this->getFormat() === 'xml') {
            return $data;
        }

        if (!$response || isset($response[$this->statusField])) {
            return $this->raiseException(
                "Invalid response message on ".$this->getRequest($service, $params),
                isset($response[$this->statusField]) ? $response[$this->statusField] : null
            );
        }

        $this->lastError = null;

        $result = &$response['result'];

        unset($response['result']);

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
        $version = ($this->version !== '') ? $this->version . '/' : '';
        $url = $this->url . '/' . $version . $service . '?' . http_build_query($params);
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
            case 'jsonp':
                $data = preg_replace("/ ^[?\w(]+ | [)]+\s*$ /x", "", $data); //JSONP -> JSON
            case 'json':
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
                CURLOPT_DNS_USE_GLOBAL_CACHE => true
            ]);
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
