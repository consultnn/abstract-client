<?php

namespace consultnn\baseapi\mappers ;

use consultnn\baseapi\exceptions\Exception;

class MapperFactory
{
    /**
     * Custom class mappings
     * Ex.: 'Address' => '\MyCustomAddress',
     * @var array
     */
    private $maps = [];

    /**
     * MapperFactory constructor.
     * @param array $classMap
     */
    public function __construct(array $classMap = [])
    {
        $this->setClassMap($classMap);
    }

    public function setClassMap(array $classMap = [])
    {
        $this->maps = $classMap;
    }

    /**
     * @param $data
     * @param string $className
     * @return $this
     * @throws \consultnn\baseapi\exceptions\Exception
     */
    public function map($data, $className)
    {
        $className = $this->getClassName($className, $data);
        $object = new $className($this);
        if (!$object instanceof MapperInterface) {
            throw new Exception("$className must implement MapperInterface");
        }
        /* @var MapperInterface $object */
        return $object->populate($data);
    }

    /**
     * @param $name string|callable $name
     * @param null $data
     * @return string
     * @throws \consultnn\baseapi\exceptions\Exception
     */
    public function getClassName($name, $data = null)
    {
        if (class_exists($name)) {
            return $name;
        } elseif (is_callable($name)) {
            return $this->getClassName(call_user_func($name, $data));
        }

        throw new Exception(
            "Undefined " . (is_callable($name) ? 'callable(' . print_r($name, true) .')' : "class " . $name)
        );
    }
}
