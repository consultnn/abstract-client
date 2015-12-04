<?php

namespace consultnn\baseapi;

use consultnn\baseapi\exceptions\Exception;
use consultnn\baseapi\mappers\MapperFactory;
use yii\base\Component;

class AbstractDomain extends Component
{
    /**
     * @var ApiConnection
     */
    public $client;
    /**
     * @var MapperFactory
     */
    public $mapper;

    public function init()
    {
        parent::init();

        if ($this->mapper === null) {
            $this->mapper = new MapperFactory();
        }

        if ($this->client === null) {
            throw new Exception('ApiConnection is null');
        }
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
            return $this->mapper->map($response, $mapper);
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
                return array();
            } else {
                $response = $response[$items];
            }
        }

        $result = array();
        foreach ($response as $value) {
            $result[] = $this->mapper->map($value, $mapper);
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
