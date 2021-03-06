<?php

namespace PHPPdf\Util;

/**
 * Base class for bag (collection of key => value pairs)
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
abstract class Bag implements \Countable, \Serializable
{
    private $elements = array();

    public function __construct(array $values = array())
    {
        foreach($values as $name => $value)
        {
            $this->add($name, $value);
        }
    }

    public function add($name, $value)
    {
        $name = (string) $name;

        $this->elements[$name] = $value;

        return $this;
    }

    public function count()
    {
        return count($this->elements);
    }

    public function get($name)
    {
        return $this->has($name) ? $this->elements[$name] : null;
    }

    public function getAll()
    {
        return $this->elements;
    }

    public function has($name)
    {
        return isset($this->elements[$name]);
    }

    /**
     * Merge couple of bags into one. 
     * 
     * Type of return object depends on invocation context. Return object is as same
     * type as class used in invocation (late state binding).
     *
     * @param array $bags Array of Bag objects
     * @return Bag Single Bag object contains merged data
     */
    public static function merge(array $bags)
    {
        $mergedBag = new static();

        foreach($bags as $bag)
        {
            foreach($bag->getAll() as $name => $value)
            {
                $mergedBag->add($name, $value);
            }
        }

        return $mergedBag;
    }

    public function serialize()
    {
        return serialize($this->elements);
    }

    public function unserialize($serialized)
    {
        $this->elements = \unserialize($serialized);
    }
}