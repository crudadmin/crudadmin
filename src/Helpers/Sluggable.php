<?php
namespace Gogol\Admin\Helpers;

class Sluggable
{
    protected $slug = null;

    public function __construct($attributes = [], $model)
    {
        $this->slug = $this->addSlug( $attributes, $model );
    }

    /*
     * Makes from text nice url
     */
    public function toSlug($url) {
        $rules = ['´'=>'','ˇ'=>'','ä'=>'a','Ä'=>'A','á'=>'a','Á'=>'A','à'=>'a','À'=>'A','ã'=>'a','Ã'=>'A','â'=>'a','Â'=>'A','č'=>'c','Č'=>'C','ć'=>'c','Ć'=>'C','ď'=>'d','Ď'=>'D','ě'=>'e','Ě'=>'E','é'=>'e','É'=>'E','ë'=>'e','è'=>'e','È'=>'E','ê'=>'e','Ê'=>'E','í'=>'i','Í'=>'I','ï'=>'i','Ï'=>'I','ì'=>'i','Ì'=>'I','î'=>'i','Î'=>'I','ľ'=>'l','Ľ'=>'L','ĺ'=>'l','Ĺ'=>'L','ń'=>'n','Ń'=>'N','ň'=>'n','Ň'=>'N','ñ'=>'n','Ñ'=>'N','ó'=>'o','Ó'=>'O','ö'=>'o','Ö'=>'O','ô'=>'o','Ô'=>'O','ò'=>'o','Ò'=>'O','õ'=>'o','Õ'=>'O','ő'=>'o','Ő'=>'O','ř'=>'r','Ř'=>'R','ŕ'=>'r','Ŕ'=>'R','š'=>'s','Š'=>'S','ś'=>'s','Ś'=>'S','ť'=>'t','Ť'=>'T','ú'=>'u','Ú'=>'U','ů'=>'u','Ů'=>'U','ü'=>'u','Ü'=>'U','ù'=>'u','Ù'=>'U','ũ'=>'u','Ũ'=>'U','û'=>'u','Û'=>'U','ý'=>'y','Ý'=>'Y','ž'=>'z','Ž'=>'Z','ź'=>'z','Ź'=>'Z'];

        $url = trim($url);
        $url = strtr($url, $rules);
        $url = mb_strtolower($url, 'utf8');
        $url = preg_replace('/[^\-a-z0-9_.]+/', '-', $url);
        $url = preg_replace('[^-*|-*$]', '', $url);
        $url = preg_replace('~(-+)~', '-', $url);
        return $url;
    }

    public function makeUnique($slug, $exists, $model)
    {
        $array = explode('-', $exists);

        //Get incement of index
        $index = last($array);

        //If slug has not increment yet, then return with first increment
        if ( ! is_numeric($index) || count( $array ) == 1 )
            return $this->makeSlug(null, $model, $slug . '-1');

        $without_index = array_slice($array, 0, -1);
        $new_slug = implode('-', $without_index );

        //Return old generated slug, with bigger increment index
        return $this->makeSlug(null, $model, $new_slug . '-' . ($index + 1));
    }

    public function makeSlug($text, $model, $slug = null)
    {
        //If is not slug, make first slug from text
        if ( !$slug )
            $slug = $this->toSlug( $text );

        //Checks into database if slug exists
        $row = $model->where('slug', $slug)->withTrashed()->limit(1);

        //If models exists, then skip slug owner
        if ( $model->exists )
            $row->where( $model->getKeyName(), '!=', $model->getKey() );

        $row = $row->get(['slug']);

        //If slug not exists, then return new generated slug
        if ( $row->count() == 0 )
            return $slug;

        //If slug exists, then generate unique slug
        return $this->makeUnique($slug, $row->first()->slug, $model);
    }

    public function addSlug($array, $model)
    {
        $slugcolumn = $model->getProperty('sluggable');

        if ( array_key_exists($slugcolumn, $array) )
        {
            $array['slug'] = $this->makeSlug($array[ $slugcolumn ], $model);
        }

        return $array;
    }

    public function get()
    {
        return $this->slug;
    }
}