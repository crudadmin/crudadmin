<?php

namespace Admin\Helpers;

use Admin;
use Admin\Models\RoutesSeo;
use Localization;

class SEOService
{
    public function loadAllSeoRoutes()
    {
        $routes = app('router')->getRoutes()->getRoutes();
        $routes = collect($routes)->filter(function($route) {
            return in_array('GET', $route->methods) && isset($route->action['seo']);
        });

        $arrayRoutes = [];

        foreach ($routes as $route) {
            $arrayRoutes[$this->toPathInfoFormat($route->uri)] = $route;
        }

        return $arrayRoutes;
    }

    public function rebuildTree()
    {
        $routes = $this->loadAllSeoRoutes();

        $existingRoutes = RoutesSeo::select(['id', 'url', 'group', 'controller'])->get();

        foreach ($routes as $routeUri => $route) {
            $dbRoute = $existingRoutes->filter(function($row) use ($route, $routeUri) {
                $group = @$route->action['seo']['group'];
                $controller = @$route->action['controller'];

                //If url has been found
                if ( $row->url == $routeUri ){
                    return true;
                }

                //If controller has been found
                if ( $row->controller == $controller ){
                    return true;
                }

                //If group is same?
                if ( ($group && $row->getValue('group') === $group) ) {
                    return true;
                }

                return false;
            })->first();

            //If route does exists
            if ( $dbRoute ) {
                $this->updateSeoRoute($route, $routeUri, $dbRoute);
            }

            //Create new seo route
            else {
                $this->createSeoRoute($route, $routeUri);
            }
        }
    }

    public function createSeoRoute($route, $routeUri)
    {
        return RoutesSeo::create([
            'url' => $routeUri,
            'group' => @$route->action['seo']['group'],
            'controller' => @$route->action['controller'],
        ]);
    }

    public function updateSeoRoute($route, $routeUri, $dbRoute)
    {
        $group = @$route->action['seo']['group'];

        if ( $group != $dbRoute->getValue('group') || $routeUri !== $dbRoute->url ) {
            $dbRoute->update([
                'url' => $routeUri,
                'group' => $group
            ]);
        }
    }

    public function toPathInfoFormat($url)
    {
        $url = trim_end($url, '/');

        if ( $url && $url[0] == '/' ) {
            return $url;
        }

        return '/'.$url;
    }
}

?>