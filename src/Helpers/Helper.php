<?php
namespace Gogol\Admin\Helpers;

use Route;

class Helper
{

    public static function controllerName($route, $method=true)
    {
        $route = str_replace('App\Http\Controllers', '', $route);
        $route = trim($route, '\\');

        if ( $method == true )
            return $route;
        else {
            $route = explode('@', $route);

            return $route[0];
        }
    }

    /**
     * Returning current controller name
     * @param  boolean $return_array if is this parram type true, response will be with controller name and method
     * @return string
     */
    public static function currentRoute($return_array=false)
    {
        $route = Route::getCurrentRoute();

        if ( ! $route )
            return false;

        $route = Route::getCurrentRoute()->getActionName();
        $route = self::controllerName($route);

        return $return_array ? explode('@', $route) : $route;
    }

    /**
     * Returning state if is current route in given routes list
     * @param  string/array  $routes
     * @param  string/boolean
     * @return boolean
     */
    public static function isActive($routes, $text = false)
    {
        if (is_array($routes))
            $result = ( in_array(self::currentRoute(), $routes) || in_array(self::currentRoute(true)[0], $routes) );
        else
            $result = ( $routes == self::currentRoute() || $routes == self::currentRoute(true)[0] );

        if ( $text == false )
            return $result;
        else
            return $result ? $text : '';

    }

    /**
     * Returning <a href="" class="active"></a> element also with url and content
     *
     * @param string/array $routes zoznam
     * @param string $name
     * @param string $active
     *
     */
    public static function link($routes, $name, $active = 'class="active"')
    {
        $action = action( is_array($routes) ? $routes[0] : $routes );

        $active_class = self::isActive($routes)==true ? $active : '';

        return '<li '.$active_class.'><a href="'.$action.'" title="'.strip_tags($name).'">'.strip_tags($name).'</a></li>';
    }

    /**
     * Method is returning error message with html content
     * @param  object $errors
     * @param  string $method method name
     * @return string         html response
     */
    static function error($errors, $method)
    {

        if ( $errors->has( $method ) )
        {
            return '<p class="requiredInfo">' . $errors->first( $method ) . '</p>';
        }

        return '';
    }

    static function priceFormat($number, $currency = '€')
    {
        return number_format($number, 2, '.', ' ') . ' ' . $currency;
    }

    static function invoiceFormat( $number )
    {
        return str_pad($number, 10, 0, STR_PAD_LEFT);
    }

}