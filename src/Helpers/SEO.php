<?php

namespace Admin\Helpers;

use Admin\Core\Helpers\File as AdminFile;
use Admin\Eloquent\AdminModel;
use Facades\Admin\Helpers\SEOService;
use Admin\Models\RoutesSeo;
use Localization;
use Gettext;
use Route;
use Admin;

class SEO
{
    private $model = null;

    private $default = [];

    private $modified = [];

    private $defaultSeoRow = null;

    private $seoRow = null;

    private $seoRows = [];

    /*
     * Set model for retreiving data for SEO
     */
    public function setModel($model)
    {
        $this->model = clone $model;
    }

    /*
     * Set default values
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /*
     * Return default value
     */
    public function getDefault($key, $default = null)
    {
        if (array_key_exists($key, $this->default)) {
            return $this->default[$key] ?: $default;
        }

        return $default;
    }

    /*
     * Try all available model fields for getting a value
     */
    private function tryModelFields($key)
    {
        $aliases = [
            'title' => ['meta_title', 'title', 'name', 'username'],
            'description' => ['meta_description', 'description', 'content'],
            'image' => ['meta_image', 'image', 'images', 'avatar'],
            'keywords' => ['meta_keywords', 'keywords'],
        ];

        $is_object = $this->model instanceof AdminModel;

        //Check for values into array
        if (is_array($this->model) && array_key_exists($key, $this->model) && $value = $this->model[$key]) {
            return $value;
        }

        //Check for aliases values into model/array
        if ($this->model && array_key_exists($key, $aliases)) {
            foreach ($aliases[$key] as $alias) {
                if (
                    ($is_object && $value = $this->model->getValue($alias)) ||
                    (! $is_object && array_key_exists($alias, $this->model) && $value = $this->model[$alias])) {
                    return $value;
                }
            }
        }
    }

    /*
     * Seto property to SEO model
     */
    public function set($key, $value)
    {
        $this->modified[$key] = $value;
    }

    /*
     * Get property from SEO model, or from parent model
     */
    public function get($key, $default = null)
    {
        //Load seo rows from database
        $this->loadSeoRow();

        if (! $this->model && ! $this->modified && count($this->seoRows) === 0 ) {
            return $default;
        }

        //If model has seo model with specific value
        if (
            $this->model
            && $this->model instanceof AdminModel
            && $this->model->seo
            && $value = $this->model->seo->getValue($key)
        ) {
            return $value;
        }

        //Get value from seo row for current route
        if ( $value = $this->getValueFromSeoTable($key, true) ) {
            return $value;
        }

        //Get modified changes
        if (array_key_exists($key, $this->modified)) {
            return $this->modified[$key];
        }

        //Try data from inserted model
        if ( $modelValue = $this->tryModelFields($key) ) {
            return $modelValue;
        }

        //Get value from seo row for given seo group
        if ( $value = $this->getValueFromSeoTable($key, false) ) {
            return $value;
        }

        //Get value from default root seo row
        if ( $this->defaultSeoRow && ($value = $this->defaultSeoRow->getValue($key)) ) {
            return $value;
        }

        return $default;
    }

    public function loadSeoRow()
    {
        //If seo table is not enabled
        if ( Admin::isSeoEnabled() === false ) {
            return;
        }

        //If seo row has been loaded
        if ( $this->seoRow || $this->seoRow === false ) {
            return $this->seoRow ?: null;
        }

        $this->seoRows = RoutesSeo::select(['url', 'group', 'title', 'keywords', 'description', 'image'])
                            ->where(function($query){
                                $query->where('url', $this->withoutLocalizedSlug($this->getRouteUrl()))
                                      ->orWhere('url', $this->withoutLocalizedSlug($this->getPathInfo()));
                            })
                            ->when($this->getSeoGroup(), function($query, $group){
                                $query->orWhere('group', $group);
                            })
                            ->orWhere('url', '/')
                            ->get();

        $this->defaultSeoRow = $this->seoRows->where('url', '/')->first() ?: false;

        $this->seoRow = $this->seoRows->where('url', '!=', '/')->first() ?: false;

        return $this->seoRow;
    }

    private function withoutLocalizedSlug($url)
    {
        $parts = explode('/', trim($url, '/'));

        if ( Localization::isValidSegment() ){
            $parts = array_slice($parts, 1);
        }

        return '/'.implode('/', $parts);
    }

    public function getPathInfo()
    {
        return SEOService::toPathInfoFormat(url()->getRequest()->getPathInfo());
    }

    /**
     * Get value from seos table
     *
     * @param  string  $key
     * @param  bool  $onlyFromActulRoute
     * @return mixed
     */
    public function getValueFromSeoTable($key, $onlyFromActulRoute = false)
    {
        $this->loadSeoRow();

        if ( $this->seoRow ) {
            //Want meta data values, only if is current route selected
            //So we dont care about route group
            if ( $onlyFromActulRoute === true ) {
                //Or if is same route url address with seo row
                if ( $this->seoRow->url === $this->getPathInfo() ) {
                    return $this->seoRow->getValue($key);
                }

                return;
            }

            return $this->seoRow->getValue($key);
        }
    }

    /*
     * Check string
     */
    private function checkString($string, $limit)
    {
        $string = strip_tags($string);
        $string = str_limit($string, 300);
        $string = trim($string);

        return $string;
    }

    /*
     * Return page title
     */
    public function getTitle()
    {
        $title = $this->getDefault('title', env('APP_NAME'));

        $before = $this->get('title');

        return ($before ? $before.' - ' : '').$title;
    }

    /*
     * Return page title
     */
    public function getAuthor($default = null)
    {
        $author = $this->getDefault('author', $default);

        $author = $this->get('author', $author);

        return $author;
    }

    /*
     * Return page description
     */
    public function getDescription()
    {
        $description = $this->getDefault('description');
        $description = $this->get('description', $description);

        return $this->checkString($description, 300);
    }

    /*
     * Return page keywords
     */
    public function getKeywords()
    {
        $keywords = $this->getDefault('keywords');
        $keywords = $this->get('keywords', $keywords);

        return $this->checkString($keywords, 300);
    }

    /*
     * Return page keywords
     */
    public function getImages()
    {
        $defaultImage = $this->getDefault('image');

        $image = $this->get('image', $defaultImage);

        $items = [];

        //If is set of admin images
        if (is_array($image)) {
            foreach ($image as $item) {
                if ($item instanceof AdminFile) {
                    $items[] = $item->resize(1200, 630);
                } elseif (is_string($item)) {
                    $items[] = $item;
                }
            }
        }

        //If is single image
        elseif ($image instanceof AdminFile) {
            $items[] = $image->resize(1200, 630);
        } elseif (is_string($image)) {
            $items[] = $image;
        }

        return $items;
    }

    public function getSeoGroup()
    {
        return @$this->modified['seogroup'];
    }

    public function getRouteUrl()
    {
        $route = Route::getCurrentRoute();

        return $route ? SEOService::toPathInfoFormat($route->uri) : null;
    }

    public function getRouteGroup()
    {
        $route = Route::getCurrentRoute();

        return $route ? @$route->action['seo']['group'] : null;
    }

    private function secure($string)
    {
        $string = strip_tags($string);

        return e($string);
    }

    /*
     * Return meta tags
     */
    public function getMetaTags()
    {
        $lines = [
            '<title>'.$this->secure(self::getTitle()).'</title>',
            '<meta name="description" content="'.$this->secure(self::getDescription()).'">',
            '<meta name="keywords" content="'.$this->secure(self::getKeywords()).'">',
            '',
            '<!-- Hello, -->',
            '<meta name="author" content="'.$this->secure(self::getAuthor('Marek Gogoľ - marekgogol.sk')).'">',
            '',
            '<meta property="og:title" content="'.$this->secure(self::getTitle()).'">',
            '<meta property="og:description" content="'.$this->secure(self::getDescription()).'">',
            '<meta property="og:locale" content="'.Gettext::getLocale(app()->getLocale()).'" />',
            '<meta property="og:type" content="website">',
            '<meta property="og:site_name" content="'.$this->secure(env('APP_NAME')).'">',
        ];

        //Push images into meta tags
        foreach ($this->getImages() as $image) {
            $lines[] = '<meta property="og:image" content="'.$this->secure($image).'">';
        }

        /*
         * Twitter tags
         */
        $lines = array_merge($lines, [
            '<meta name="twitter:title" content="'.$this->secure(self::getTitle()).'" />',
            '<meta name="twitter:description" content="'.$this->secure(self::getDescription()).'" />',
        ]);

        foreach ($this->getImages() as $image) {
            $lines[] = '<meta name="twitter:image" content="'.$this->secure($image).'">';
        }

        return $lines;
    }

    /*
     * Render HTML reponse
     */
    public function render($default = [], $echo = true)
    {
        $this->setDefault($default);

        echo "\n\t\t".implode("\n\t\t", $this->getMetaTags())."\n";
    }
}
