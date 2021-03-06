<?php

use PHPPdf\Util\Boundary;
use PHPPdf\Util\Point;

class BoundaryTest extends PHPUnit_Framework_TestCase
{
    private $boundary;

    public function setUp()
    {
        $this->boundary = new Boundary();
    }

    /**
     * @test
     */
    public function creation()
    {
        $this->boundary->setNext(Point::getInstance(10, 10))
                       ->setNext(20, 10)
                       ->setNext(20, 5)
                       ->setNext(Point::getInstance(10, 5));

        $this->boundary->close();

        $this->assertEquals(5, count($this->boundary));
    }

    /**
     * @test
     * @expectedException \BadMethodCallException
     */
    public function invalidCreation()
    {
        $this->boundary->setNext(10, 10);
        $this->boundary->close();
    }

    /**
     * @test
     * @expectedException \LogicException
     */
    public function invalidStateException()
    {
        $this->boundary->setNext(10, 10)
                       ->setNext(20, 10)
                       ->setNext(20, 5)
                       ->setNext(10, 5);

        $this->boundary->close();
        $this->boundary->setNext(30, 30);
    }

    /**
     * @test
     */
    public function translation()
    {
        $this->boundary->setNext(10, 10)
               ->setNext(20, 10)
               ->setNext(20, 5)
               ->setNext(10, 5);

        $old = clone $this->boundary;

        $vector = array(10, 5);

        $this->boundary->translate($vector[0], $vector[1]);

        for($it1 = $this->boundary, $it2 = $old; $it1->valid() && $it2->valid(); $it1->next(), $it2->next())
        {
            $point1 = $it1->current();
            $point2 = $it2->current();

            $this->assertEquals($point1->toArray(), array($point2->getX() + $vector[0], $point2->getY() - $vector[1]));
        }
    }

    /**
     * @test
     */
    public function translateOnePoint()
    {
        $this->boundary->setNext(10, 10)
               ->setNext(20, 10)
               ->setNext(20, 5)
               ->setNext(10, 5);

        $old = clone $this->boundary;

        $this->boundary->pointTranslate(0, 1, 1);
        foreach(array(1, 2, 3) as $index)
        {
            $this->assertEquals($old[$index], $this->boundary[$index]);
        }
        $this->assertEquals($old[0]->translate(1, 1), $this->boundary[0]);
    }
    
    /**
     * @test
     */
    public function diagonal()
    {
        $this->boundary->setNext(10, 10)
               ->setNext(20, 10)
               ->setNext(20, 5)
               ->setNext(10, 5);

        $this->assertEquals(array(20, 5), $this->boundary->getDiagonalPoint()->toArray());
        $this->assertEquals(array(10, 10), $this->boundary->getFirstPoint()->toArray());
    }

    /**
     * @test
     */
    public function arrayAccessInterface()
    {
        $this->boundary->setNext(10, 10)
               ->setNext(20, 10)
               ->setNext(20, 5)
               ->setNext(10, 5);

        $this->assertEquals(array(10, 10), $this->boundary[0]->toArray());
        $this->assertEquals(array(10, 5), $this->boundary[3]->toArray());
    }

    /**
     * @test
     * @expectedException \OutOfBoundsException
     */
    public function arrayAccessInvalidIndex()
    {
        $this->boundary->setNext(10, 10)
               ->setNext(20, 10);

        $this->boundary[2];
    }

    /**
     * @test
     * @expectedException \BadFunctionCallException
     */
    public function arrayAccessInvalidOperation()
    {
        $this->boundary->setNext(10, 10)
               ->setNext(20, 10);

        $this->boundary[2] = 123;
    }
    
    /**
     * @test
     */
    public function intersecting()
    {
        $this->boundary->setNext(0, 100)
                       ->setNext(100, 100)
                       ->setNext(100, 50)
                       ->setNext(0, 50)
                       ->close();

        $this->assertTrue($this->boundary->intersects($this->boundary));

        $clone = clone $this->boundary;
        $clone->translate(99, 0);
        $this->assertTrue($this->boundary->intersects($clone));
        $clone->translate(1, 0);
        $this->assertFalse($this->boundary->intersects($clone));

        $clone->translate(-50, -20);
        
        $this->assertTrue($this->boundary->intersects($clone));
    }
}