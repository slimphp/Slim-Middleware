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
namespace Slim\Middleware;

use Negotiation\FormatNegotiator;

/**
 * Content Negotiation
 *
 * This is middleware for a Slim application that provides
 * Content Negotiation features.
 *
 * @package    Slim
 * @author     William Durand <william.durand1@gmail.com>
 * @since      1.6.0
 */
class ContentNegotiation extends \Slim\Middleware
{
    private $negotiator;

    public function __construct(FormatNegotiator $negotiator = null)
    {
        $this->negotiator = $negotiator ?: new FormatNegotiator();
    }

    /**
     * Call
     */
    public function call()
    {
        $env        = $this->app->environment;
        $accept     = isset($env['HTTP_ACCEPT']) ? $env['HTTP_ACCEPT'] : '';
        $priorities = isset($env['negotiation.priorities']) ? $env['negotiation.priorities'] : array();

        $env['request.best_format'] = $this->negotiator->getBestFormat($accept, $priorities);

        $this->next->call();
    }
}
