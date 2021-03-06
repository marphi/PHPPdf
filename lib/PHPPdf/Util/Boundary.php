<?php

namespace PHPPdf\Util;

/**
 * Set of ordered points whom determine boundary and shape of glyph element.
 *
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Boundary implements \Countable, \Iterator, \ArrayAccess, \Serializable
{
    private $points = array();
    private $numberOfPoints = 0;
    private $closed = false;
    private $current = 0;
    private $diagonalPointIndex = null;

    /**
     * Add next point to boundary
     * 
     * @return Boundary Self
     */
    public function setNext()
    {
        if($this->closed)
        {
            throw new \LogicException('Boundary has been already closed.');
        }

        $numberOfArgs = \func_num_args();
        $args = \func_get_args();

        if($numberOfArgs === 2)
        {
            $point = Point::getInstance($args[0], $args[1]);
        }
        elseif($numberOfArgs === 1 && $args[0] instanceof Point)
        {
            $point = $args[0];
        }
        else
        {
            throw new \InvalidArgumentException('Passed argument(s) should be coordinations or Point object.');
        }

        $this->points[$this->numberOfPoints] = $point;

        $diagonalPoint = $this->diagonalPointIndex === null ? null : $this->points[$this->diagonalPointIndex];

        if(!$diagonalPoint || ($diagonalPoint->getY() > $point->getY() || $diagonalPoint->getY() === $point->getY() && $diagonalPoint->getX() < $point->getX()))
        {
            $this->diagonalPointIndex = $this->numberOfPoints;
        }

        $this->numberOfPoints++;

        return $this;
    }

    /**
     * Close boundary. Adding next points occurs LogicException
     */
    public function close()
    {
        if($this->numberOfPoints <= 2)
        {
            throw new \BadMethodCallException('Boundary must have at last three points.');
        }

        $this->setNext($this->getFirstPoint());

        $this->closed = true;
    }

    /**
     * Checks if boundaries have common points
     * 
     * @param Boundary $boundary
     * @return boolean
     */
    public function intersects(Boundary $boundary)
    {
        $firstPoint = $this->getFirstPoint();
        $diagonalPoint = $this->getDiagonalPoint();

        $compareFirstPoint = $boundary->getFirstPoint();
        $compareDiagonalPoint = $boundary->getDiagonalPoint();

        foreach($boundary as $point)
        {
            if($this->contains($point))
            {
                return true;
            }
        }

        $centerPoint = $this->getPointBetween($firstPoint, $diagonalPoint);

        if($boundary->contains($centerPoint))
        {
            return true;
        }

        $centerPoint = $this->getPointBetween($compareFirstPoint, $compareDiagonalPoint);

        if($this->contains($centerPoint))
        {
            return true;
        }

        $centerPoint = $this->getPointBetween($firstPoint, $compareDiagonalPoint);

        if($this->contains($centerPoint) && $boundary->contains($centerPoint))
        {
            return true;
        }

        return false;
    }

    private function contains(Point $point, $include = false)
    {
        $firstPoint = $this->getFirstPoint();
        $diagonalPoint = $this->getDiagonalPoint();

        return ($firstPoint->getX() < $point->getX() && $firstPoint->getY() > $point->getY() && $diagonalPoint->getX() > $point->getX() && $diagonalPoint->getY() < $point->getY() || $include && $point);
    }

    private function getPointBetween(Point $point1, Point $point2)
    {
        $x = $point1->getX() + ($point2->getX() - $point1->getX())/2;
        $y = $point2->getY() + ($point1->getY() - $point2->getY())/2;

        return Point::getInstance($x, $y);
    }

    /**
     * @return integer Number of points in boundary
     */
    public function count()
    {
        return $this->numberOfPoints;
    }

    public function current()
    {
        $points = $this->getPoints();
        return $this->valid() ? $points[$this->current] : null;
    }

    /**
     * @return array Array of Point objects
     */
    public function getPoints()
    {
        return $this->points;
    }

    public function key()
    {
        return $this->current;
    }

    public function next()
    {
        $this->current++;
    }

    public function rewind()
    {
        $this->current = 0;
    }

    public function valid()
    {
        $points = $this->getPoints();
        return isset($points[$this->current]);
    }

    /**
     * Translate boundary by vector ($x, $y)
     * @param integer $x First vector's coordinate
     * @param integer $y Second vector's coordinate
     */
    public function translate($x, $y)
    {
        foreach($this as $key => $point)
        {
            $this->points[$key] = $point->translate($x, $y);
        }

        return $this;
    }

    /**
     * Translate and replace Point within boundary (@see translate())
     *
     * @param integer $pointIndex Index of the point
     * @param integer $x First vector's coordinate
     * @param integer $y Second vector's coordinate
     */
    public function pointTranslate($pointIndex, $x, $y)
    {
        $point = $this[$pointIndex];

        $this->points[$pointIndex] = $point->translate($x, $y);

        return $this;
    }

    /**
     * @return Point First added point or null if boundary is empty
     */
    public function getFirstPoint()
    {
        if(isset($this->points[0]))
        {
            return $this->points[0];
        }

        return null;
    }

    /**
     * @return Point Point diagonally to first point (@see getFirstPoint()) or null if boundary is empty
     */
    public function getDiagonalPoint()
    {
        if($this->diagonalPointIndex !== null)
        {
            return $this->points[$this->diagonalPointIndex];
        }

        return null;
    }

    /**
     * Clears points and status of the object
     */
    public function reset()
    {
        $this->closed = false;
        $this->points = array();
        $this->rewind();
        $this->numberOfPoints = 0;
        $this->diagonalPointIndex = null;
    }

    public function isClosed()
    {
        return $this->closed;
    }

    public function offsetExists($offset)
    {
        return (is_int($offset) && $offset < $this->numberOfPoints);
    }

    public function offsetGet($offset)
    {
        if(!$this->offsetExists($offset))
        {
            throw new \OutOfBoundsException(sprintf('Point of index "%s" dosn\'t exist. Index should be in range 0-%d.', $offset, $this->numberOfPoints - 1));
        }

        return $this->points[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new \BadFunctionCallException('You can not set point directly.');
    }

    public function offsetUnset($offset)
    {
        throw new \BadFunctionCallException('You can not unset point directly.');
    }

    public function __clone()
    {
    }

    public function serialize()
    {
        $points = array();
        foreach($this->getPoints() as $point)
        {
            $points[] = $point->toArray();
        }

        return serialize(array(
            'closed' => $this->closed,
            'points' => $points,
        ));
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $points = $data['points'];

        foreach($points as $point)
        {
            $this->setNext($point[0], $point[1]);
        }

        $this->closed = (bool) $data['closed'];
    }
}