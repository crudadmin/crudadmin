<?php

namespace Gogol\Admin\Helpers;

use Gogol\Admin\Helpers\File as AdminFile;
use Gogol\Admin\Models\Model as AdminModel;
use Gettext;

class SEO
{
    private $model = null;

    private $default = [];

    /*
     * Set model for retreiving data for SEO
     */
    public function setModel($model)
    {
        $this->model = $model;
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
        if ( array_key_exists($key, $this->default) )
            return $this->default[$key] ?: $default;

        return $default;
    }

    /*
     * Try all available model fields for getting a value
     */
    private function tryModelFields($key)
    {
        $aliases = [
            'title' => ['name', 'username'],
            'description' => ['content'],
            'images' => ['image'],
        ];

        $is_object = $this->model instanceof AdminModel;

        //Check for values into model
        if ( $is_object && $value = $this->model->getValue($key) )
            return $value;

        //Check for values into array
        else if ( array_key_exists($key, $this->model) && $value = $this->model[$key] )
            return $value;

        //Check for aliases values into model/array
        if ( array_key_exists($key, $aliases) )
        {
            foreach ($aliases[$key] as $alias) {
                if (
                    ($is_object && $value = $this->model->getValue($alias)) ||
                    (!$is_object && array_key_exists($alias, $this->model) && $value = $this->model[$alias]) )
                {
                    return $value;
                }
            }
        }

        return null;
    }

    /*
     * Seto property to SEO model
     */
    public function set($key, $value)
    {
        //If model is already set
        if ( $this->model )
        {
            $this->model[$key] = $value;
        } else {
            $this->model = [
                $key => $value
            ];
        }
    }

    /*
     * Get property from SEO model, or from parent model
     */
    public function get($key, $default = null)
    {
        if ( ! $this->model )
            return $default;

        if ( $this->model instanceof AdminModel && $this->model->seo && $value = $this->model->seo->getValue($key) )
            return $value;

        return $this->tryModelFields($key) ?: $default;
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

        return ($before ? $before . ' - ' : '') . $title;
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
        $image = $this->getDefault('image');

        $image = $this->get('images', $image);

        $items = [];

        //If is set of admin images
        if ( is_array($image) )
        {
            foreach ($image as $item)
                if ( $item instanceof AdminFile )
                    $items[] = $item->resize(1200, 630);
                else if ( is_string($item) )
                    $items[] = $item;
        }

        //If is single image
        else if ( $image instanceof AdminFile )
            $items[] = $image->resize(1200, 630);

        else if ( is_string($image) )
            $items[] = $image;

        return $items;
    }

    /*
     * Return meta tags
     */
    public function getMetaTags()
    {
        $lines = [
            '<title>'.e(SEO::getTitle()).'</title>',
            '<meta name="description" content="'.e(SEO::getDescription()).'">',
            '<meta name="keywords" content="'.e(SEO::getKeywords()).'">',
            '<meta name="author" content="'.e($this->getDefault('author')).'">',
            "",
            '<meta property="og:title" content="'.e(SEO::getDescription()).'">',
            '<meta property="og:description" content="'.e(SEO::getDescription()).'">',
            '<meta property="og:locale" content="'.Gettext::getLocale(app()->getLocale()).'" />',
            '<meta property="og:type" content="website">',
            '<meta property="og:site_name" content="Paul Lange - Oslany">',
        ];

        //Push images into meta tags
        foreach ($this->getImages() as $image)
            $lines[] = '<meta property="og:image" content="'.e($image).'">';

        /*
         * Twitter tags
         */
        $lines = array_merge($lines, [
            '<meta name="twitter:title" content="'.e(SEO::getTitle()).'" />',
            '<meta name="twitter:description" content="'.e(SEO::getDescription()).'" />',
        ]);

        foreach ($this->getImages() as $image)
            $lines[] = '<meta name="twitter:image" content="'.e($image).'">';

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

?>