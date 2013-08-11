<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Twig\Tests\Extension;

use Symfony\Bridge\Twig\Extension\StopwatchExtension;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Bridge\Twig\Tests\TestCase;

class StopwatchExtensionTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (!class_exists('Symfony\Component\Stopwatch\Stopwatch')) {
            $this->markTestSkipped('The "Stopwatch" component is not available');
        }
    }

    /**
     * @expectedException \Twig_Error_Syntax
     */
    public function testFailIfNameAlreadyExists()
    {
        $this->testTiming('{% stopwatch foo %}{% endstopwatch %}{% stopwatch foo %}{% endstopwatch %}', array());
    }

    /**
     * @expectedException \Twig_Error_Syntax
     */
    public function testFailIfStoppingWrongEvent()
    {
        $this->testTiming('{% stopwatch foo %}{% endstopwatch bar %}', array());
    }

    /**
     * @dataProvider getTimingTemplates
     */
    public function testTiming($template, $events)
    {
        $twig = new \Twig_Environment(new \Twig_Loader_String(), array('debug' => true, 'cache' => false, 'autoescape' => true, 'optimizations' => 0));
        $twig->addExtension(new StopwatchExtension($this->getStopwatch($events)));

        try {
            $nodes = $twig->render($template);
        } catch (\Twig_Error_Runtime $e) {
            throw $e->getPrevious();
        }
    }

    public function getTimingTemplates()
    {
        return array(
            array('{% stopwatch foo %}something{% endstopwatch %}', 'foo'),
            array('{% stopwatch foo %}symfony2 is fun{% endstopwatch %}{% stopwatch bar %}something{% endstopwatch %}', array('foo', 'bar')),
            array('{% stopwatch foo %}something{% endstopwatch foo %}', 'foo'),
            array('{% stopwatch "foo.bar" %}something{% endstopwatch %}', 'foo.bar'),
        );
    }

    protected function getStopwatch($events = array())
    {
        $events = is_array($events) ? $events : array($events);
        $stopwatch = $this->getMock('Symfony\Component\Stopwatch\Stopwatch');

        $i = -1;
        foreach ($events as $eventName) {
            $stopwatch->expects($this->at(++$i))
                ->method('start')
                ->with($this->equalTo($eventName), 'template')
            ;
            $stopwatch->expects($this->at(++$i))
                ->method('stop')
                ->with($this->equalTo($eventName))
            ;
        }

        return $stopwatch;
    }
}
