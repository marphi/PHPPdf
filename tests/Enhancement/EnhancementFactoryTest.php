<?php

require_once __DIR__.'/../Stub/Enhancement/EnhancementStub.php';

use PHPPdf\Enhancement\Factory as EnhancementFactory;

class EnhancementFactoryTest extends TestCase
{
    private $factory;

    public function setUp()
    {
        $this->factory = new EnhancementFactory();
    }

    /**
     * @test
     */
    public function setDefinitionOfEnhancement()
    {
        $this->assertFalse($this->factory->hasDefinition('stub'));
        $this->factory->addDefinition('stub', 'EnhancementStub');
        $this->assertTrue($this->factory->hasDefinition('stub'));
    }

    /**
     * @test
     */
    public function getParameterNames()
    {
        $this->factory->addDefinition('stub', 'EnhancementStub');
        $parameters = $this->factory->getParameters('stub');

        $this->assertEquals(array('color', 'someParameter'), $parameters);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function requiredParametersMustBePassed()
    {
        $this->factory->addDefinition('stub', 'EnhancementStub');
        $this->factory->create('stub', array());
    }

    /**
     * @test
     */
    public function createUsingValidParameters()
    {
        $this->factory->addDefinition('stub', 'EnhancementStub');
        $enhancement = $this->factory->create('stub', array('color' => '#bbbbbb', 'someParameter' => 'dupa'));

        $this->assertNotNull($enhancement);
        $this->assertInstanceOf('EnhancementStub', $enhancement);
    }

    /**
     * @test
     * @expectedException PHPPdf\Enhancement\Exception\DefinitionNotFoundException
     */
    public function throwExceptionIfDefinitionDosntFound()
    {
        $this->factory->create('stub');
    }

    /**
     * @test
     */
    public function unserializedFactoryIsCopyOfSerializedFactory()
    {
        $this->factory->addDefinition('stub1', 'EnhancementStub');
        $this->factory->addDefinition('stub2', 'EnhancementStub');
        
        $this->factory->create('stub1', array('color' => '#ffffff'));

        $unserializedFactory = unserialize(serialize($this->factory));

        $unserializedDefinitions = $this->invokeMethod($unserializedFactory, 'getDefinitions');
        $definitions = $this->invokeMethod($this->factory, 'getDefinitions');

        $this->assertEquals($definitions, $unserializedDefinitions);
    }
}