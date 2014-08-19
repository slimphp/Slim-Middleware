# Slim Framework CSRF (XSRF) Guard Middleware

Based on [CsrfGuard](https://github.com/codeguy/Slim-Extras/blob/develop/Middleware/CsrfGuard.php) middleware from Slim-Extras.

Provides <a href="https://www.owasp.org/index.php/Cross-Site_Request_Forgery_(CSRF)_Prevention_Cheat_Sheet">CSRF (XSRF) protection</a> for your forms and ajax requests.

Implements cookie set + http header check for ajax requests and CSRF_token (hidden input) for forms.

If your are familiar with Python, this implementation for Slim framework
is similar to [implementation for Django](https://docs.djangoproject.com/en/dev/ref/contrib/csrf/)

Compatible out of the box with [AngularJS $http provider](https://docs.angularjs.org/api/ng/service/$http#cross-site-request-forgery-xsrf-protection), no additional configuration required.



## How to Use

Add middleware:

    $app = new \Slim\Slim();
    $app->add( new \Slim\Middleware\CsrfGuardNeue() );

You can configure hidden input name like this:

    $csrfGuard = new \Slim\Middleware\CsrfGuardNeue('myCustomName');
    $app->add( $csrfGuard );

Or configure other parameters passing array with key-value options instead of one string argument
(in this code sample also provided default option values):

    $csrfGuard = new \Slim\Middleware\CsrfGuardNeue( array(
        'field'  => 'csrfmiddlewaretoken', // Input name
        'cookie' => 'XSRF-TOKEN',          // Cookie name
        'header' => 'X-Xsrf-Token',        // Header name
        // Action on CSRF token validation failure.
        // Should be callable function. $app will be passed as function parameter.
        // $this will be changed to CsrfGuardNeue object (PHP 5.4+ only).
        'action' => function(\Slim\Slim $app) {
            if ( $this->isAcceptJSON() || $this->isXHR() ) {
                $app->contentType("application/json");
                // Double quoted for JSON-safe response.
                $app->halt(400, '"Invalid or missing CSRF token"');
            }
            $app->halt(400, 'Invalid or missing CSRF token');
        }
    ) );
    $app->add( $csrfGuard );


In your view template add this hidden input inside every of web forms with action="POST" you have created:

    <input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>" />

Or, if you are using Twig template engine, you can use provided extension.


### TwigCsrfHelpers extension usage

1.  Add extension to your stack

        $app->view->parserExtensions = array(
            new \Slim\Views\TwigExtension(),
            new \Slim\Middleware\CsrfGuardNeue\TwigCsrfHelpers()
        );

2.  Insert CSRF token hidden input inside all of your web forms with this simple function:

        <form ...> {{ CSRF() }} ...</form>

    Which will be translated to:

        <form ...> <input type="hidden" name="{{ csrf_key }}" value="{{ csrf_token }}" /> ...</form>

3.  jQuery users needed simple additional configuration. Just add this line before closing `</body>` tag:

        {{ jQcsrf() }}

    or, if you want no `<script></script>` wrapping, just use:

        {{ jQcsrf(false) }}

    This will create required jQuery handler to copy CSRF token value from cookie to request header
    to be verified by CsrfGuardNeue for every POST, PUT, PATCH and DELETE ajax requests.


### jQuery users (when Twig is not used)

If you get data for all of your ajax POST requests from `$("form").serialize()` or `$.serializeArray()` (<http://api.jquery.com/serializeArray/>),
you need nothing else to configure.

Just make sure you put hidden input in every form (see above).

For any other ajax POST, PUT or DELETE requests you will need some additional setup.

This needed to copy CSRF token value from cookie to request header for every unsafe request

    <script>
    function getCookie(cname) {
        var name = cname + '=';
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = $.trim(ca[i]);
            if (c.indexOf(name) != -1) return c.substring(name.length,c.length);
        }
        return null;
    }
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            if (this.crossDomain) return;
            // These HTTP methods do not require CSRF protection, BUT!
            // Only if you are not changing any data using these methods in ajax!
            if ( -1 < (['GET','HEAD','OPTIONS','TRACE']).indexOf(settings.type) ) return;
            var csrftoken = $.cookie ? $.cookie('XSRF-TOKEN') : getCookie('XSRF-TOKEN');
            xhr.setRequestHeader("X-XSRF-TOKEN", csrftoken);
        }
    });
    </script>


## Limitations

Any sub-domains of your site will be able to get and set cookies (client-side) for the whole domain.
In this case Cookie+Header protection is useless.

If you have untrusted sub-domains, disable cookie assignment by setting cookie option to false:

    $csrfGuard = new \Slim\Middleware\CsrfGuardNeue( array('cookie' => false) );
    $app->add( $csrfGuard );

You will need then to reconfigure ajax calls accordingly, for example, by copying hidden input value
to request header for every ajax request:

    xhr.setRequestHeader("X-XSRF-TOKEN", csrftoken);

If you wish to disable header check, set header option to false:

    $csrfGuard = new \Slim\Middleware\CsrfGuardNeue( array('cookie' => false, 'header' => false) );

In this case token value from POST data will be checked only (yes, for XHR calls too).


### Other security tips

Learn about [Session fixation](https://www.owasp.org/index.php/Session_fixation).

Do not forget to check and properly set (when needed) response headers related
to Same Origin Policy, like `Access-Control-Allow-Origin`, `Access-Control-Allow-Methods` and `X-Frame-Options`:

1. <https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS>
2. <https://developer.mozilla.org/en-US/docs/Web/HTTP/X-Frame-Options>



## Author and credits

CsrfGuardNeue provided by [Stepan Legachev](https://www.sib.li).

Thank to [Mikhail Osher](https://github.com/miraage), author of original CsrfGuard middleware,
and [all of contributors](https://github.com/codeguy/Slim-Extras/commits/develop/Middleware/CsrfGuard.php).

Thanks to [Josh Lockhart](https://github.com/codeguy) for [Slim PHP framework](http://www.slimframework.com/).

Thanks to [Fabien Potencier](https://github.com/fabpot/) for [Twig](http://twig.sensiolabs.org/) templates.



## License

All code in this repository is released under the MIT public license.

<http://www.slimframework.com/license>