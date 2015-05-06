<?php
namespace Blimp\Http;

use Blimp\Http\HttpEventSubscriber as HttpEventSubscriber;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\HttpKernel;

class HttpServiceProvider implements ServiceProviderInterface {
    public function register(Container $api) {
        $api['http.utils'] = function ($api) {
            return new HttpUtils($api);
        };

        $api['http.dispatcher'] = function ($api) {
            return new EventDispatcher();
        };

        $api['http.request'] = function () {
            return Request::createFromGlobals();
        };

        $api['http.resolver'] = function ($api) {
            return new HttpControllerResolver($api, $api['blimp.logger']);
        };

        $api['http.request_stack'] = function () {
            return new RequestStack();
        };

        $api['http.kernel'] = function ($api) {
            return new HttpKernel($api['http.dispatcher'], $api['http.resolver'], $api['http.request_stack']);
        };

        $api['http.listener'] = function ($api) {
            return new HttpEventSubscriber($api);
        };

        $api['http.session'] = function () {
            $session = new Session();
            $session->start();
            return $session;
        };

        $api['http.process'] = $api->protect(function () use ($api) {
            $request = $api['http.request'];

            $response = $api['http.kernel']->handle($request);

            $response->prepare($request);

            $response->send();

            $api['http.kernel']->terminate($request, $response);
        });

        $api->extend('blimp.init', function ($status, $api) {
            if ($status) {
                $api['http.dispatcher']->addSubscriber($api['http.listener']);
            }

            return $status;
        });

        $api->extend('blimp.process', function ($results, $api) {
            $results[] = $api['http.process']();

            return $results;
        });
    }
}

function proper_parse_str($str) {
    # result array
    $arr = array();

    # split on outer delimiter
    $pairs = explode('&', $str);

    # loop through each pair
    foreach ($pairs as $i) {
        # split into name and value
        $parts = explode('=', $i, 2);

        $name = !empty($parts[0]) ? urldecode($parts[0]) : '';
        $value = !empty($parts[1]) ? urldecode($parts[1]) : '';

        # if name already exists
        if (isset($arr[$name])) {
            # stick multiple values into an array
            if (is_array($arr[$name])) {
                $arr[$name][] = $value;
            } else {
                $arr[$name] = array($arr[$name], $value);
            }
        }
        # otherwise, simply stick it in a scalar
        else {
            $arr[$name] = $value;
        }
    }

    # return result array
    return $arr;
}

if (!empty($_SERVER['QUERY_STRING'])) {
    $_GET = proper_parse_str($_SERVER['QUERY_STRING']);
}
