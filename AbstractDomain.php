<?php

namespace consultnn\baseapi;

use consultnn\baseapi\exceptions\Exception;
use consultnn\baseapi\mappers\MapperFactory;

abstract class AbstractDomain
{
    /**
     * @var ApiConnection
     */
    public $client;

    /**
     * @var MapperFactory
     */
    public $mapperFactory;

    /**
     * Initialization api and mapper factory
     * @return mixed
     */
    abstract public function init();

    /**
     * @param ApiConnection $client
     * @param MapperFactory $mapper
     */
    public function __construct(ApiConnection $client = null, MapperFactory $mapper = null)
    {
        $this->client = $client ? $client : new ApiConnection();
        $this->mapperFactory = $mapper ? $mapper : new MapperFactory();
        $this->init();
    }

    /**
     * @param string $service
     * @param array $params
     * @param string $mapper
     * @return mixed|mappers\MapperInterface
     */
    protected function getSingle($service, $mapper, array $params = [])
    {
        $response = $this->client->send($service, $params);
        if (isset($response)) {
            return $this->mapperFactory->map($response, $mapper);
        }

        return false;
    }


    /**
     * @param $service
     * @param $mapper
     * @param array $params
     * @param string $typeItems
     * @return mappers\MapperInterface[]
     * @throws Exception
     */
    protected function getInternalList($service, $mapper, array $params = [], $typeItems = null)
    {
        $response = $this->client->send($service, $params);

        if (is_string($response)) {
            throw new Exception("Can't get items for string response");
        }

        return $this->getItemsOfResponse($response, $mapper, $typeItems);
    }

    /**
     * @param $response
     * @param $mapper
     * @param $items
     * @return array
     */
    protected function getItemsOfResponse($response, $mapper, $items)
    {
        if ($items) {
            if (empty($response[$items])) {
                return [];
            } else {
                $response = $response[$items];
            }
        }

        $result = [];
        foreach ($response as $value) {
            $result[] = $this->mapperFactory->map($value, $mapper);
        }

        return $result;
    }

    /**
     * @param array $values
     * @return null|string
     */
    public static function toString(array $values)
    {
        return empty($values) ? null : implode(',', $values);
    }

    /**
     * @see ApiConnection::getMeta()
     */
    public function getMeta()
    {
        return $this->client->getMeta();
    }
}
