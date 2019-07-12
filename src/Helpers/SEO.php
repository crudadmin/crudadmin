<?php

namespace Admin\Helpers;

use Gettext;
use Admin\Eloquent\AdminModel;
use Admin\Helpers\File as AdminFile;

class SEO
{
    private $model = null;

    private $default = [];

    private $modified = [];

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
            'title' => ['name', 'username'],
            'description' => ['content'],
            'image' => ['image', 'avatar'],
        ];

        $is_object = $this->model instanceof AdminModel;

        //Check for values into model
        if ($is_object && $value = $this->model->getValue($key)) {
            return $value;
        }

        //Check for values into array
        elseif ($this->model && array_key_exists($key, $this->model) && $value = $this->model[$key]) {
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
        if (! $this->model && ! $this->modified) {
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

        //Get modified changes
        if (array_key_exists($key, $this->modified)) {
            return $this->modified[$key];
        }

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
        $image = $this->getDefault('image');

        $image = $this->get('image', $image);

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
