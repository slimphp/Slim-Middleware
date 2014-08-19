<?php
/**
 * Created by PhpStorm.
 * User: www.sib.li
 * Date: 13.08.14
 * Time: 13:29
 */

namespace Slim\Middleware;

/**
 * Class CsrfGuardNeue
 *
 * Based on CsrfGuard middleware from Slim-Extras
 * https://github.com/codeguy/Slim-Extras/blob/develop/Middleware/CsrfGuard.php
 *
 *
 * Provides CSRF(XSRF) protection for your forms and ajax requests.
 * https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)_Prevention_Cheat_Sheet
 *
 *
 * Implements cookie set + http header check for ajax requests
 * and csrf_token (hidden input) for forms.
 * ( if your are familiar with Django, check here:
 *   https://docs.djangoproject.com/en/dev/ref/contrib/csrf/ )
 *
 * Compatible with AngularJS $http provider:
 * https://docs.angularjs.org/api/ng/service/$http#cross-site-request-forgery-xsrf-protection
 *
 *
 * Usage:
 * $app = new \Slim\Slim();
 * $app->add( new \Slim\Middleware\CsrfGuardNeue() );
 *
 * You can configure hidden input name like this:
 * $csrfGuard = new \Slim\Middleware\CsrfGuardNeue('myCustomName');
 * $app->add( $csrfGuard );
 *
 * Or configure other parameters passing array (here provided their default values)
 * $csrfGuard = new \Slim\Middleware\CsrfGuardNeue( array(
 *     'field'  => 'csrfmiddlewaretoken', // Input name
 *     'cookie' => 'XSRF-TOKEN',          // Cookie name
 *     'header' => 'X-Xsrf-Token',        // Header name
 *     // Action on CSRF validation failure
 *     // Should be callable function. $app will be passed inside when called.
 *     'action' => function(\Slim\Slim $app) {
 *         $app->halt(400, '"Invalid or missing CSRF token"');
 *     }
 * ) );
 * $app->add( $csrfGuard );
 *
 *
 * In your view template add this input inside every of web forms you have created:
 * <input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
 *
 * Or, with Twig, you can use provided extension:
 * $app->view->parserExtensions = array(
 *     new \Slim\Views\TwigExtension(),
 *     new \Slim\Middleware\CsrfGuardNeue\TwigCsrfHelpers()
 * );
 * And insert inside forms:
 * <form ...> {{ CSRF() }} ...</form>
 * This will be translated to:
 * <form ...> <input type="hidden" name="{{ csrf_key }}" value="{{ csrf_token }}" /> ...</form>
 *
 *
 *
 * For jQuery users:
 * If you get data for all of your ajax POST requests from $("form").serialize() or $.serializeArray(),
 * you need nothing else to configure, just make sure you put hidden input in every form (see above).
 * See also: http://api.jquery.com/serializeArray/
 *
 * For any other ajax POST, PUT or DELETE requests you will need some additional setup:
 * (this needed to copy CSRF token value from cookie to request header for every unsafe request):
 * <script>
 * function getCookie(cname) {
 *     var name = cname + '=';
 *     var ca = document.cookie.split(';');
 *     for (var i = 0; i < ca.length; i++) {
 *         var c = $.trim(ca[i]);
 *         if (c.indexOf(name) != -1) return c.substring(name.length,c.length);
 *     }
 *     return null;
 * }
 * $.ajaxSetup({
 *     beforeSend: function(xhr, settings) {
 *         if (this.crossDomain) return;
 *         // These HTTP methods do not require CSRF protection, BUT!
 *         // Only if you are not changing any data using these methods in ajax!
 *         if ( -1 < (['GET','HEAD','OPTIONS','TRACE']).indexOf(settings.type) ) return;
 *         var csrftoken = $.cookie ? $.cookie('XSRF-TOKEN') : getCookie('XSRF-TOKEN');
 *         xhr.setRequestHeader("X-XSRF-TOKEN", csrftoken);
 *     }
 * });
 * </script>
 *
 * If you are using Twig template engine, add provided extension (see above), and place somewhere
 * before closing </body> this line:
 * {{ jQcsrf() }}
 * That will be translated to the jQuery code from above.
 *
 *
 * Limitations
 * Any sub-domains of your site will be able to get and set cookies (client-side) for the whole domain.
 * In this case Cookie+Header protection is useless.
 * If you have untrusted sub-domains, disable cookie assignment by setting cookie option to false:
 * $csrfGuard = new \Slim\Middleware\CsrfGuardNeue( array('cookie' => false) );
 * $app->add( $csrfGuard );
 * You will need to reconfigure ajax calls accordingly, for example, by copying hidden input value
 * to header.
 * If you wish to disable header check, use header option:
 * $csrfGuard = new \Slim\Middleware\CsrfGuardNeue( array('cookie' => false, 'header' => false) );
 * In this case only POST value will be checked (for XHR calls too!)
 *
 *
 * See also: https://www.owasp.org/index.php/Session_fixation
 *
 * Also do not forget to check and properly set (when needed) response headers related
 * to Same Origin Policy, like Access-Control-Allow-Origin, Access-Control-Allow-Methods and X-Frame-Options:
 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
 * https://developer.mozilla.org/en-US/docs/Web/HTTP/X-Frame-Options
 *
 *
 * @package Slim\Middleware
 * @author  Stepan Legachev, www.sib.li
 */
class CsrfGuardNeue extends \Slim\Middleware
{

    /**
     * Form field name.
     *
     * @var string
     */
    protected $fieldName = 'csrfmiddlewaretoken';

    /**
     * Cookie name.
     *
     * @var string
     */
    protected $cookieName = null;

    /**
     * Header name.
     * To this header JS should copy cookie value.
     *
     * @var string
     */
    protected $headerName = null;

    /**
     * Action to perform on token check failure.
     *
     * @var \Closure|callable
     */
    public $action = null;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    public $unsafeMethods = array(
        \Slim\Http\Request::METHOD_POST,
        \Slim\Http\Request::METHOD_PUT,
        \Slim\Http\Request::METHOD_PATCH,
        \Slim\Http\Request::METHOD_DELETE
    );


    /**
     *
     * @param array $settings
     * @throws \OutOfBoundsException
     */
    public function __construct($settings = array())
    {

        $defaults = array(
            'field'  => 'csrfmiddlewaretoken',
            'cookie' => 'XSRF-TOKEN',
            'header' => 'X-Xsrf-Token',
            'action' => array($this, 'defaultAction')
        );

        // Backwards compatibility.
        if ( is_string($settings) ) {
            $settings = array('field' => $settings);
        }

        if ( is_array($settings) ) {
            $this->settings = array_merge($defaults, $settings);
        } else {
            $this->settings = $defaults;
        }

        $fieldName  = is_string($this->settings['field']) ? trim($this->settings['field']) : false;
        $cookieName = is_string($this->settings['cookie']) ? trim($this->settings['cookie']) : false;
        $headerName = is_string($this->settings['header']) ? trim($this->settings['header']) : false;

        if ( empty($fieldName) || preg_match('/[^a-zA-Z0-9\-\_]/', $fieldName) ) {
            throw new \OutOfBoundsException('Invalid CSRF token field name "' . $fieldName . '"');
        }

        if ( empty($cookieName) ) {
            $cookieName = false;
        } else if ( !is_string($cookieName) || preg_match('/[^a-zA-Z0-9\-\_]/', $cookieName) ) {
            throw new \OutOfBoundsException('Invalid CSRF token cookie name "' . $cookieName . '"');
        }

        if ( empty($headerName) ) {
            $headerName = false;
        } else if ( !is_string($headerName) || preg_match('/[^a-zA-Z0-9\-\_]/', $headerName) ) {
            throw new \OutOfBoundsException('Invalid CSRF token header name "' . $headerName . '"');
        }

        if ( !is_callable($this->settings['action']) ) {
            $this->action = array($this, 'defaultAction');
        } else {
            $this->action = $this->settings['action'];
            if ( method_exists($this->action, 'bindTo') ) {
                $this->action = $this->action->bindTo($this, $this);
            }

        }

        $this->fieldName  = $fieldName;
        $this->cookieName = $cookieName;
        $this->headerName = $headerName;

    } // constructor


    /**
     * Call middleware.
     *
     * @return void
     */
    public function call()
    {

        // Attach as hook.
        $this->app->hook('slim.before', array($this, 'check'));

        // Call next middleware.
        $this->next->call();

    } // call


    /**
     * Default action on token verification fail.
     *
     * @param $app \Slim\Slim
     */
    protected function defaultAction(\Slim\Slim $app)
    {
        if ( $this->isAcceptJSON() || $this->isXHR() ) {
            $app->contentType("application/json");
            // Double quoted for JSON-safe response.
            $app->halt(400, '"Invalid or missing CSRF token"');
        }
        $app->halt(400, 'Invalid or missing CSRF token');
    } // defaultAction


    /**
     * Check CSRF token is valid.
     * Sets cookie and session variable.
     *
     */
    public function check()
    {

        // Check sessions are enabled.
        if (session_id() === '') {
            throw new \Exception('Sessions are required to use the CSRF Guard middleware.');
        }

        // @todo Also check referrer and origin

        if ( empty($_SESSION[$this->fieldName]) ) {

            // Slightly modified implementation from https://www.owasp.org/index.php/PHP_CSRF_Guard
            if ( function_exists("hash_algos") && in_array("sha256", hash_algos()) ) {
                $token = hash( "sha256", mt_rand() );
            } else {
                $token = '';
                for ($i = 0; $i < 64; ++$i) {
                    $token .= dechex( mt_rand(0, 15) );
                }
            }

            $_SESSION[$this->fieldName] = $token;

        } else {
            $token = $_SESSION[$this->fieldName];
        }


        // Validate the CSRF token.
        if ( in_array($this->app->request()->getMethod(), $this->unsafeMethods) ) {

            $userToken = $this->app->request()->post($this->fieldName, false);

            if ( empty($userToken) && $this->headerName !== false ) {
                $userToken = $this->app->request->headers->get( $this->headerName );
            }

            if ($token !== $userToken) {
                call_user_func( $this->action, $this->app );
            }

        } else {
            if ($this->cookieName !== false) {
                // if ( is_null( $app->getCookie($this->cookieName) ) )
                $this->app->setCookie($this->cookieName, $token, '2 days');
            }
        }

        // Assign CSRF token key names and value to view.
        $this->app->view()->set('csrf_key',   $this->fieldName);
        $this->app->view()->set('csrf_token', $token);

        if ($this->cookieName !== false) {
            $this->app->view()->set('csrf_cookie', $this->cookieName);
        }

        if ( $this->headerName !== false ) {
            $this->app->view()->set('csrf_header', $this->headerName);
        }

    } // check


    public function isAcceptJSON()
    {
        $accept = $this->app->request->headers->get('Accept');
        return ( stripos($accept, 'application/json') !== false );
    } // isAcceptJSON

    public function isXHR()
    {
        $reqWith = $this->app->request->headers->get('X-Requested-With');
        return strtolower($reqWith) == strtolower('XMLHttpRequest');
    } // isXHR


} // CsrfGuardNeue class
