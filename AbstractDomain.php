<?php

namespace consultnn\baseapi;

use consultnn\baseapi\exceptions\Exception;
use consultnn\baseapi\mappers\MapperFactory;

class AbstractDomain
{
    public $client;

    /**
     * @param ApiConnection $client
     * @param MapperFactory $mapper
     */
    public function __construct(ApiConnection $client = null, MapperFactory $mapper = null)
    {
        $this->client = $client ? $client : new ApiConnection();
        $this->factory = $mapper ? $mapper : new MapperFactory();
    }

    /**
     * @param string $service
     * @param array $params
     * @param string $mapper
     * @return mixed|mappers\MapperInterface
     */
    protected function getSingle($service, $mapper, array $params = array())
    {
        $response = $this->client->send($service, $params);
        if (isset($response)) {
            return $this->factory->map($response, $mapper);
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
    protected function getInternalList($service, $mapper, array $params = array(), $typeItems = null)
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
                return array();
            } else {
                $response = $response[$items];
            }
        }

        $result = array();
        foreach ($response as $value) {
            $result[] = $this->factory->map($value, $mapper);
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
}
