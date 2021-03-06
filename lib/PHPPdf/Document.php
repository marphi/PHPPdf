<?php

namespace PHPPdf;

use PHPPdf\Glyph\Glyph,
    PHPPdf\Formatter as Formatters,
    PHPPdf\Glyph\Page,
    PHPPdf\Glyph\PageCollection,
    PHPPdf\Enhancement\EnhancementBag,
    PHPPdf\Font\Registry as FontRegistry,
    PHPPdf\Enhancement\Factory as EnhancementFactory,
    PHPPdf\Exception\DrawingException;

/**
 * @author Piotr Śliwa <peter.pl7@gmail.com>
 */
class Document
{
    const ATTR_PAGE_SIZE = 1;
    const SIZE_A4 = \Zend_Pdf_Page::SIZE_A4;
    
    const DRAWING_PRIORITY_BACKGROUND1 = 60;
    const DRAWING_PRIORITY_BACKGROUND2 = 50;
    const DRAWING_PRIORITY_BACKGROUND3 = 40;

    const DRAWING_PRIORITY_FOREGROUND1 = 10;
    const DRAWING_PRIORITY_FOREGROUND2 = 20;
    const DRAWING_PRIORITY_FOREGROUND3 = 30;
    
    private $attributes = array(
        self::ATTR_PAGE_SIZE => self::SIZE_A4,
    );
    private $processed = false;

    /** @var Zend_Pdf */
    private $pdfEngine;

    private $enhancementFactory = null;

    private $fontRegistry = null;

    private $formatters = array();

    public function __construct()
    {
        $this->initialize();
    }

    public function getEnhancements(EnhancementBag $bag)
    {
        $enhancements = array();

        if($this->enhancementFactory !== null)
        {
            foreach($bag->getAll() as $id => $parameters)
            {
                if(!isset($parameters['name']))
                {
                    throw new \InvalidArgumentException('"name" attribute is required.');
                }

                $name = $parameters['name'];
                unset($parameters['name']);
                $enhancements[] = $this->enhancementFactory->create($name, $parameters);
            }
        }

        return $enhancements;
    }

    public function setEnhancementFactory(EnhancementFactory $enhancementFactory)
    {
        $this->enhancementFactory = $enhancementFactory;
    }

    public function initialize()
    {
        $this->processed = false;
        $this->pdfEngine = new \Zend_Pdf();
    }
    
    public function getFontRegistry()
    {
        if($this->fontRegistry === null)
        {
            throw new \PHPPdf\Exception\Exception(sprintf('Font registry isn\'t set in class "%s".', __CLASS__));
        }

        return $this->fontRegistry;
    }

    public function setFontRegistry(FontRegistry $registry)
    {
        $this->fontRegistry = $registry;
    }
    
    public function setAttribute($attribute, $value)
    {
        $attribute = (int) $attribute;
        
        $this->attributes[$attribute] = $value;
    }

    public function getAttribute($attribute)
    {
        $attribute = (int) $attribute;

        if(!isset($this->attributes[$attribute]))
        {
            throw new \InvalidArgumentException(sprintf('Attribute %d dosn\'t exist.', $attribute));
        }

        return $this->attributes[$attribute];
    }

    /**
     * Invokes drawing procedure.
     *
     * Formats each of glyph, retreive drawing tasks and execute them.
     *
     * @param array $pages Array of pages to draw
     */
    public function draw($pages)
    {
        if($this->isProcessed())
        {
            throw new \LogicException(sprintf('Pdf has alredy been drawed.'));
        }

        $this->processed = true;


        if(is_array($pages))
        {
            $pageCollection = new PageCollection();

            foreach($pages as $page)
            {
                if(!$page instanceof Page)
                {
                    throw new DrawingException(sprintf('Not all elements of passed array are PHPPdf\Glyph\Page type. One of them is "%s".', get_class($page)));
                }

                $pageCollection->add($page);
            }
        }
        elseif($pages instanceof PageCollection)
        {
            $pageCollection = $pages;
        }
        else
        {
            throw new \InvalidArgumentException(sprintf('Argument of draw method must be an array of pages or PageCollection object, "%s" given.', get_class($pages)));
        }


        $pageCollection->format($this);
        
        $tasks = $pageCollection->getDrawingTasks($this);

        $this->invokeTasks($tasks);
    }

    public function invokeTasks(array $tasks)
    {
        //SplPriorityQueue and SplMaxHeap arent't deterministic for elements with the same priority - inserting order isn't queue order
        $priorityQueue = array();
        $heap = new \PHPPdf\Util\DrawingTaskHeap();
        foreach($tasks as $task)
        {
            $heap->insert($task);
        }

        foreach($heap as $task)
        {
            $task->invoke();
        }
    }

    public function addDrawingTask(Callable $callable, $priority = self::DRAWING_PRIORITY_FOREGROUND3)
    {
        $this->drawingTasksQueue->insert($callable, $priority);
    }

    public function isProcessed()
    {
        return $this->processed;
    }

    /**
     * @return Zend_Pdf
     */
    public function getDocumentEngine()
    {
        return $this->pdfEngine;
    }

    /**
     * @param string $className Formatter class name
     * @return PHPPdf\Formatter\Formatter
     */
    public function getFormatter($className)
    {
        if(!isset($this->formatters[$className]))
        {
            $this->formatters[$className] = $this->createFormatter($className);
        }

        return $this->formatters[$className];
    }

    private function createFormatter($className)
    {
        try
        {
            $class = new \ReflectionClass($className);
            $formatter = $class->newInstance();

            if(!$formatter instanceof Formatters\Formatter)
            {
                throw new \PHPPdf\Exception\Exception(sprintf('Class "%s" dosn\'t implement PHPPdf\Formatrer\Formatter interface.', $className));
            }

            return $formatter;
        }
        catch(\ReflectionException $e)
        {
            throw new \PHPPdf\Exception\Exception(sprintf('Class "%s" dosn\'t exist or haven\'t default constructor.', $className), 0, $e);
        }
    }

    public function render()
    {
        return $this->getDocumentEngine()->render();
    }
}