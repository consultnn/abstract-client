<?php

namespace consultnn\baseapi\mappers;

abstract class AbstractMapper implements MapperInterface, \Serializable
{
    /**
     * @var MapperFactory $factory
     */
    protected $factory;

    protected static $factoryAwareProperties = array();

    public function __construct(MapperFactory $mapperFactory = null)
    {
        $this->setFactory($mapperFactory ? $mapperFactory : new MapperFactory());
    }

    public function setFactory(MapperFactory $factory)
    {
        $this->factory = $factory;
        foreach (self::$factoryAwareProperties as $property) {
            if (!isset($this->$property)) {
                continue;
            }
            if (is_array($this->$property)) {
                foreach ($this->$property as $element) {
                    if (method_exists($element, 'setFactory')) {
                        $element->setFactory($factory);
                    }
                }
            } else {
                if (method_exists($this->$property, 'setFactory')) {
                    $this->$property->setFactory($factory);
                }
            }
        }
    }

    public function serialize()
    {
        $data = get_object_vars($this);
        unset($data['factory']);
        return serialize($data);
    }

    public function unserialize($data)
    {
        $data = unserialize($data);
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @param array $data
     * @return $this
     */
    public function populate(array $data)
    {
        foreach ($data as $key => $value) {
            $propertyName = self::keyToMethodName($key);
            $methodName = 'set' . ucfirst($propertyName);
            if (method_exists($this, $methodName)) {
                $this->$methodName($value);
            } elseif (property_exists($this, $propertyName)) {
                $this->$propertyName = $value;
            }
        }
        return $this;
    }

    /**
     * Convert some_variable_name to someVariableName
     * @param string $keyName
     * @param bool $capitalizeFirstCharacter
     * @return string
     */
    protected static function keyToMethodName($keyName, $capitalizeFirstCharacter = false)
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $keyName)));
        if (!$capitalizeFirstCharacter) {
            $str = lcfirst($str);
        }
        return $str;
    }
}
