<?php

use PHPPdf\Document,
    PHPPdf\Util\Point,
    PHPPdf\Glyph\AbstractGlyph,
    PHPPdf\Glyph\Container;

class StubGlyph extends AbstractGlyph
{
    public function initialize()
    {
        parent::initialize();
        $this->addAttribute('name-two');
        $this->addAttribute('name', 'value');
    }

    public function setNameTwo($value)
    {
        $this->setAttributeDirectly('name-two', $value.' from setter');
    }
}

class StubComposeGlyph extends Container
{
}

class AbstractGlyphTest extends TestCase
{
    private $glyph;

    public function setUp()
    {
        $this->glyph = new StubGlyph();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function failureSettingAttribute()
    {
        $this->glyph->setAttribute('someName', 'someValue');
    }

    /**
     * @test
     */
    public function gettingAttribute()
    {
        $this->assertEquals('value', $this->glyph->getAttribute('name'));
    }

    /**
     * @test
     */
    public function successSettingAttribute()
    {
        $this->glyph->setAttribute('name', 'value2');
        $this->assertEquals('value2', $this->glyph->getAttribute('name'));
    }

    /**
     * @test
     */
    public function setter()
    {
        $this->glyph->setNameTwo('value');
        $this->assertEquals('value from setter', $this->glyph->getAttribute('name-two'));
    }

    /**
     * @test
     */
    public function gettingAncestor()
    {
        $this->assertNull($this->glyph->getAncestorByType('Page'));
        $this->assertNull($this->glyph->getParent());

        $composeGlyph = new StubComposeGlyph();
        $secondComposeGlyph = new StubComposeGlyph();
        $composeGlyph->add($secondComposeGlyph);
        $secondComposeGlyph->add($this->glyph);

        $this->assertEquals($secondComposeGlyph, $this->glyph->getParent());
        $this->assertEquals($secondComposeGlyph, $this->glyph->getAncestorByType('StubComposeGlyph'));
        $this->assertEquals($composeGlyph, $secondComposeGlyph->getAncestorByType('StubComposeGlyph'));

        $composeGlyph->add($this->glyph);
        $this->assertNotEquals($secondComposeGlyph, $this->glyph->getAncestorByType('StubComposeGlyph'));
        $this->assertEquals($composeGlyph, $this->glyph->getAncestorByType('StubComposeGlyph'));

        $this->assertNull($this->glyph->getWidth());
        $this->glyph->setWidth(100);
        $this->assertEquals(100, $this->glyph->getWidth());

        $this->assertNull($this->glyph->getAttribute('margin-top'));
        $this->glyph->setAttribute('margin-top', 10);
        $this->assertEquals(10, $this->glyph->getAttribute('margin-top'));
    }

    /**
     * @test
     */
    public function displayModes()
    {
        $width = $height= 100;
        $this->glyph->setWidth($width);
        $this->glyph->setHeight($height);

        $this->glyph->setAttribute('display', 'block');

        $this->assertEquals($width, $this->glyph->getWidth());
        $this->assertEquals($height, $this->glyph->getHeight());

        //fix
//        $this->glyph->setAttribute('display', 'inline');
//        $this->assertEquals(0, $this->glyph->getWidth());
//        $this->assertEquals(0, $this->glyph->getHeight());

        $this->glyph->setAttribute('display', 'none');
        $this->assertEquals(0, $this->glyph->getWidth());
        $this->assertEquals(0, $this->glyph->getHeight());
    }

    /**
     * @test
     */
    public function settingMargin()
    {
        $margins = array('margin-top', 'margin-right', 'margin-bottom', 'margin-left');
        $this->glyph->setMargin('auto');
        foreach($margins as $margin)
        {
            $this->assertEquals('auto', $this->glyph->getAttribute($margin));
        }

        $this->glyph->setMargin('auto', 10);

        $this->assertEquals('auto', $this->glyph->getMarginTop());
        $this->assertEquals('auto', $this->glyph->getMarginBottom());
        $this->assertEquals(10, $this->glyph->getMarginLeft());
        $this->assertEquals(10, $this->glyph->getMarginRight());

        $this->glyph->setMargin(5, 10, 15, 20);
        $this->assertEquals(5, $this->glyph->getMarginTop());
        $this->assertEquals(15, $this->glyph->getMarginBottom());
        $this->assertEquals(20, $this->glyph->getMarginLeft());
        $this->assertEquals(10, $this->glyph->getMarginRight());

        $this->glyph->setMargin(5, 10, 15);
        $this->assertEquals(5, $this->glyph->getMarginTop());
        $this->assertEquals(15, $this->glyph->getMarginBottom());
        $this->assertEquals(5, $this->glyph->getMarginLeft());
        $this->assertEquals(10, $this->glyph->getMarginRight());
    }

    /**
     * @test
     */
    public function callMethod()
    {
        try
        {
            $color = '#aaaaaa';
            $result = $this->glyph->setColor($color);
            $this->assertEquals($this->glyph, $result);
            $this->assertEquals($color, $this->glyph->getColor());
        }
        catch(BadMethodCallException $e)
        {
            $this->fail('exception should not be thrown');
        }
    }

    /**
     * @test
     * @expectedException \BadMethodCallException
     */
    public function callUndefinedAttribute()
    {
        $this->glyph->getAttributeDosntExists();
    }

    /**
     * @test
     * @expectedException \BadMethodCallException
     */
    public function callUndefinedMethod()
    {
        $this->glyph->callUndefinedMethod();
    }

    /**
     * @test
     */
    public function callLongAttribute()
    {
        try
        {
            $padding = 12;
            $result = $this->glyph->setPaddingTop($padding);
            $this->assertEquals($this->glyph, $result);
            $this->assertEquals($padding, $this->glyph->getPaddingTop());
        }
        catch(BadMethodCallException $e)
        {
            $this->fail('exception should not be thrown');
        }
    }

    /**
     * @test
     */
    public function translation()
    {
        $this->glyph->getBoundary()->setNext(0, 500)
                                   ->setNext(200, 500)
                                   ->setNext(200, 400);

        $this->glyph->translate(20, 100);

        $this->assertEquals(array(20, 400), $this->glyph->getStartDrawingPoint());
        $this->assertEquals(array(220, 300), $this->glyph->getEndDrawingPoint());
    }

    /**
     * @test
     */
    public function paddings()
    {
        $this->glyph->setWidth(100);
        $this->glyph->setHeight(80);
        $this->glyph->setPadding(10);

        $this->assertEquals(80, $this->glyph->getWidthWithoutPaddings());
        $this->assertEquals(60, $this->glyph->getHeightWithoutPaddings());
    }

    /**
     * @test
     */
    public function split()
    {
        $this->glyph->setWidth(100);
        $this->glyph->setHeight(80);
        $boundary = $this->glyph->getBoundary();
        $boundary->setNext(20, 50)
                 ->setNext(70, 50)
                 ->setNext(70, -30)
                 ->setNext(20, -30)
                 ->close();

        $result = $this->glyph->split(50);

        $this->assertEquals(100, $this->glyph->getWidth());
        $this->assertEquals(50, $this->glyph->getHeight());
        $this->assertEquals(array(70, 0), $this->glyph->getEndDrawingPoint());

        $this->assertEquals(100, $result->getWidth());
        $this->assertEquals(30, $result->getHeight());
        $this->assertEquals(array(70, -30), $result->getEndDrawingPoint());
    }

    /**
     * @test
     */
    public function splitWhenSplitableAttributeIsOff()
    {
        $this->glyph->setSplittable(false);
        $this->glyph->setWidth(100)->setHeight(200);
        $boundary = $this->glyph->getBoundary();
        $boundary->setNext(0, 100)
                 ->setNext(100, 100)
                 ->setNext(100, -100)
                 ->setNext(0, -100)
                 ->close();

        $result = $this->glyph->split(50);

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function attributeSnapshot()
    {
        $this->glyph->makeAttributesSnapshot();
        $snapshot = $this->glyph->getAttributesSnapshot();

        foreach($snapshot as $name => $value)
        {
            $this->assertEquals($value, $this->glyph->getAttribute($name));
        }

        $this->glyph->setAttribute('display', 'none');

        $this->assertNotEquals($snapshot['display'], $this->glyph->getAttribute('display'));
    }

    /**
     * @test
     */
    public function mergeEnhancements()
    {
        $this->glyph->mergeEnhancementAttributes('border', array('color' => 'red'));
        $this->assertEquals(array('border' => array('color' => 'red')), $this->glyph->getEnhancementsAttributes());

        $this->glyph->mergeEnhancementAttributes('border', array('style' => 'dotted'));
        $this->assertEquals(array('border' => array('color' => 'red', 'style' => 'dotted')), $this->glyph->getEnhancementsAttributes());
    }

    /**
     * @test
     * @dataProvider pseudoAttributes
     */
    public function marginAndPaddingAsPseudoAttribute($attribute)
    {
        $this->glyph->setAttribute($attribute, '10 20');
        $this->assertEquals(20, $this->glyph->getAttribute($attribute.'-left'));
        $this->assertEquals(10, $this->glyph->getAttribute($attribute.'-bottom'));
    }

    public function pseudoAttributes()
    {
        return array(
            array('margin'),
            array('padding'),
        );
    }

    /**
     * @test
     */
    public function usePlaceholders()
    {
        $this->assertFalse($this->glyph->hasPlaceholder('name'));
        $this->assertNull($this->glyph->getPlaceholder('name'));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function throwExceptionIfNotExistedPlaceholderIsSet()
    {
        $this->glyph->setPlaceholder('name', $this->getMock('PHPPdf\Glyph\Container'));
    }

    /**
     * @test
     */
    public function gettingBoundaryPoints()
    {
        $boundary = $this->getMock('PHPPdf\Util\Boundary', array('getFirstPoint', 'getDiagonalPoint'));
        $boundary->expects($this->once())
                 ->id('first-point')
                 ->method('getFirstPoint')
                 ->will($this->returnValue(Point::getInstance(10, 10)));

        $boundary->expects($this->once())
                 ->after('first-point')
                 ->method('getDiagonalPoint')
                 ->will($this->returnValue(Point::getInstance(20, 20)));

        $this->invokeMethod($this->glyph, 'setBoundary', array($boundary));

        $this->assertTrue($this->glyph->getFirstPoint() === Point::getInstance(10, 10));
        $this->assertTrue($this->glyph->getDiagonalPoint() === Point::getInstance(20, 20));
    }

    /**
     * @test
     */
    public function serializeWithoutParentAndBoundary()
    {
        $parent = new StubComposeGlyph();
        $this->glyph->setParent($parent);

        $this->glyph->getBoundary()->setNext(10, 10);
        $glyph = unserialize(serialize($this->glyph));

        $this->assertNull($glyph->getParent());
        $this->assertEquals(1, $glyph->getBoundary()->count());
    }

    /**
     * @test
     */
    public function serializeWithAttributesAndEnhancementBagAndFormattersNames()
    {
        $this->glyph->mergeEnhancementAttributes('some-enhancement', array('attribute' => 'value'));
        $this->glyph->setAttribute('display', 'inline');
        $this->glyph->getBoundary()->setNext(0, 0);
        $this->glyph->addFormatterName('SomeName');

        $glyph = unserialize(serialize($this->glyph));

        $this->assertEquals($this->glyph->getEnhancementsAttributes(), $glyph->getEnhancementsAttributes());
        $this->assertEquals($this->glyph->getAttribute('display'), $glyph->getAttribute('display'));
        $this->assertEquals($this->glyph->getBoundary(), $glyph->getBoundary());
        $this->assertEquals($this->glyph->getFormattersNames(), $glyph->getFormattersNames());
    }

    /**
     * @test
     */
    public function callFormattersWhenFormatMethodHasInvoked()
    {
        $formatterName = 'someFormatter';

        $documentMock = $this->getMock('PHPPdf\Document', array('getFormatter'));

        $formatterMock = $this->getMock('PHPPdf\Formatter\Formatter', array('format'));
        $formatterMock->expects($this->once())
                      ->method('format')
                      ->with($this->glyph, $documentMock);


        $documentMock->expects($this->once())
                     ->method('getFormatter')
                     ->with($formatterName)
                     ->will($this->returnValue($formatterMock));

        $this->glyph->setFormattersNames(array($formatterName));
        $this->glyph->format($documentMock);
    }
}