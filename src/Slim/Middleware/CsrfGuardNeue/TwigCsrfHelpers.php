<?php
/**
 * Created by PhpStorm.
 * User: www.sib.li
 * Date: 17.08.14
 * Time: 12:48
 */

namespace Slim\Middleware\CsrfGuardNeue;

/**
 * Class TwigCsrfHelpers
 *
 * Twig helper extension for CsrfGuardNeue middleware.
 *
 * Usage:
 *
 * 1. Add extension to your stack:
 * $app->view->parserExtensions = array(
 *     new \Slim\Views\TwigExtension(),
 *     new \Slim\Middleware\CsrfGuardNeue\TwigCsrfHelpers()
 * );
 *
 *
 * 2. Insert inside any form with action="POST":
 * <form ...> {{ CSRF() }} ...</form>
 *
 * Which will be translated to:
 * <form ...> <input type="hidden" name="{{ csrf_key }}" value="{{ csrf_token }}" /> ...</form>
 *
 *
 * 3. jQuery users need to add this line before closing </body> tag:
 * {{ jQcsrf() }}
 *
 * or, if you want no <script></script> wrapping, just:
 * {{ jQcsrf(false) }}
 *
 * This will create required jQuery handler to copy CSRF token value from cookie to request header
 * to be verified by CsrfGuardNeue for every POST, PUT, PATCH and DELETE ajax requests.
 *
 *
 * 4. AngularJS $http provider do not require any additional configuration!
 *
 *
 *
 * @package Slim\Middleware\CsrfGuardNeue
 * @author  Stepan Legachev, www.sib.li
 */
class TwigCsrfHelpers extends \Twig_Extension {

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'slim-csrf';
    } // getName

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFunctions()
    {

        return array(
            new \Twig_SimpleFunction('CSRF', array($this, 'showInput'), array(
                'is_safe' => array('html'),
                'needs_context' => true
            )),

            new \Twig_SimpleFunction('jQcsrf', array($this, 'jQueryHelper'), array(
                'is_safe' => array('html', 'js'),
                'needs_context' => true
            ))
        );

    } // getFunctions

    public function showInput($context)
    {
        return '<input class="hide" type="hidden" name="'.$context['csrf_key'].'" value="'.$context['csrf_token'].'" />';
    } //showInput

    public function jQueryHelper($context, $addScriptTag = true)
    {

        if ( empty($context['csrf_cookie']) ) {
            return '';
        }
        if ( empty($context['csrf_header']) ) {
            return '';
        }
        $cookieName = $context['csrf_cookie'];
        $headerName = $context['csrf_header'];

        $js = <<<RAWJS
(function($, undefined) {
    function getCookie(cname) {
        var name = cname + '=';
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = $.trim(ca[i]);
            if ( c.indexOf(name) != -1 ) return c.substring(name.length, c.length);
        }
        return null;
    }
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            if (this.crossDomain) return;
            if ( -1 < (['GET','HEAD','OPTIONS','TRACE']).indexOf(settings.type) ) return;
            var csrftoken = $.cookie ? $.cookie('{$cookieName}') : getCookie('{$cookieName}');
            xhr.setRequestHeader('{$headerName}', csrftoken);
        }
    });
} )(jQuery);
RAWJS;

        if ($addScriptTag) {
            return "<script>\n{$js}\n</script>";
        } else {
            return $js;
        }

    } // jQueryHelper


} // TwigCsrfHelpers class

