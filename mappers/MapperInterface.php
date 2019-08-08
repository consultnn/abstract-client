<?php

namespace consultnn\baseapi\mappers;

interface MapperInterface
{
    /**
     * @param array $data
     * @return $this
     */
    public function populate(array $data);
}
