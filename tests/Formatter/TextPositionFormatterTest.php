<?php

use PHPPdf\Formatter\TextPositionFormatter,
    PHPPdf\Util\Point,
    PHPPdf\Document;

class TextPositionFormatterTest extends TestCase
{
    const TEXT_LINE_HEIGHT = 14;
    
    private $formatter;

    public function setUp()
    {
        $this->formatter = new TextPositionFormatter();
    }

    /**
     * @test
     */
    public function addPointsToBoundaryAccordingToLineSizes()
    {
        $mock = $this->getTextMock(array(50, 100), array(0, 700));

        $this->formatter->format($mock, new Document());
    }

    private function getTextMock($lineSizes, $parentFirstPoint, $firstXCoord = null)
    {
        $parentMock = $this->getMock('\PHPPdf\Glyph\AbstractGlyph', array('getStartDrawingPoint'));
        $parentMock->expects($this->once())
                   ->method('getStartDrawingPoint')
                   ->will($this->returnValue(array(0, 700)));

        $mock = $this->getMock('\PHPPdf\Glyph\Text', array(
            'getParent',
            'getLineHeight',
            'getLineSizes',
            'getStartDrawingPoint',
            'getBoundary',
        ));

        $mock->expects($this->once())
             ->method('getParent')
             ->will($this->returnValue($parentMock));

        $boundaryMock = $this->getMock('\PHPPdf\Util\Boundary', array(
            'getFirstPoint',
            'setNext',
            'close',
        ));

        $firstXCoord = $firstXCoord ? $firstXCoord : $parentFirstPoint[0];
        $boundaryMock->expects($this->atLeastOnce())
                     ->method('getFirstPoint')
                     ->will($this->returnValue(Point::getInstance($firstXCoord, $parentFirstPoint[1])));

        $this->addBoundaryPointsAsserts($boundaryMock, $lineSizes, $parentFirstPoint[1]);

        $mock->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->will($this->returnValue($boundaryMock));

        $mock->expects($this->atLeastOnce())
             ->method('getBoundary')
             ->will($this->returnValue($boundaryMock));

        $mock->expects($this->once())
             ->method('getLineHeight')
             ->will($this->returnValue(self::TEXT_LINE_HEIGHT));

        $mock->expects($this->once())
             ->method('getLineSizes')
             ->will($this->returnValue($lineSizes));

        return $mock;
    }

    private function addBoundaryPointsAsserts($boundaryMock, $lineSizes, $firstYCoord)
    {
        $at = 1;
        foreach($lineSizes as $i => $size)
        {
            $yCoord = $firstYCoord - self::TEXT_LINE_HEIGHT*$i;
            $boundaryMock->expects($this->at($at++))
                         ->method('setNext')
                         ->with($size, $yCoord);

            if(isset($lineSizes[$i+1]))
            {
                $boundaryMock->expects($this->at($at++))
                             ->method('setNext')
                             ->with($size, $yCoord - self::TEXT_LINE_HEIGHT);
            }
        }

        $boundaryMock->expects($this->once())
                     ->method('close');
    }
}