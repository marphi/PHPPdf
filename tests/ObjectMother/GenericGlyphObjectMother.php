<?php

class GenericGlyphObjectMother
{
    private $test;

    public function __construct(TestCase $test)
    {
        $this->test = $test;
    }

    public function getPageMock($x, $y)
    {
        $gcMock = $this->test->getMock('PHPPdf\Glyph\GraphicsContext', array('drawPolygon', 'setLineDashingPattern', 'setLineWidth'), array(), '', false);
        $gcMock->expects($this->test->once())
                 ->method('drawPolygon')
                 ->with($x, $y, \Zend_Pdf_Page::SHAPE_DRAW_STROKE);

        $pageMock = $this->getEmptyPageMock($gcMock);

        return $pageMock;
    }

    public function getEmptyPageMock($graphicsContext)
    {
        $pageMock = $this->test->getMock('PHPPdf\Glyph\Page', array('getGraphicsContext'));

        $pageMock->expects($this->test->atLeastOnce())
                 ->method('getGraphicsContext')
                 ->will($this->test->returnValue($graphicsContext));

        return $pageMock;
    }

    public function getGlyphMock($x, $y, $width, $height)
    {
        $boundaryMock = $this->getBoundaryStub($x, $y, $width, $height);

        $glyphMock = $this->test->getMock('PHPPdf\Glyph\AbstractGlyph', array('getBoundary', 'getWidth', 'getHeight'));

        $glyphMock->expects($this->test->atLeastOnce())
                  ->method('getBoundary')
                  ->will($this->test->returnValue($boundaryMock));

        $glyphMock->expects($this->test->any())
                  ->method('getWidth')
                  ->will($this->test->returnValue($width));

        $glyphMock->expects($this->test->any())
                  ->method('getHeight')
                  ->will($this->test->returnValue($height));

        return $glyphMock;
    }

    public function getBoundaryStub($x, $y, $width, $height)
    {
        $boundary = new PHPPdf\Util\Boundary();

        $boundary->setNext($x, $y)
                 ->setNext($x+$width, $y)
                 ->setNext($x+$width, $y-$height)
                 ->setNext($x, $y-$height)
                 ->close()
                ;

        return $boundary;
    }
}