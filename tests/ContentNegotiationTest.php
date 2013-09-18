<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.3.0
 * @package     Slim
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

use Slim\Middleware\ContentNegotiation;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class ContentNegotiationTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        ob_start();
    }

    public function tearDown()
    {
        ob_end_clean();
    }

    public function testBestFormatIsSet()
    {
        \Slim\Environment::mock(array(
            'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ));

        $s = new \Slim\Slim();
        $s->add(new ContentNegotiation());
        $s->run();

        $env = \Slim\Environment::getInstance();
        $bestFormat = $env['request.best_format'];

        $this->assertNotNull($bestFormat);
        $this->assertEquals('html', $bestFormat);
    }

    public function testBestFormatWithPriorities()
    {
        \Slim\Environment::mock(array(
            'ACCEPT'                 => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'negotiation.priorities' => array('xml'),
        ));

        $s = new \Slim\Slim();
        $s->add(new ContentNegotiation());
        $s->run();

        $env = \Slim\Environment::getInstance();
        $bestFormat = $env['request.best_format'];

        $this->assertNotNull($bestFormat);
        $this->assertEquals('xml', $bestFormat);
    }
}
