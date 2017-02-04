<?php

namespace Gogol\Admin\Traits;

use Illuminate\Contracts\Validation\Factory;
use Gogol\Admin\Exceptions\SluggableException;
use Route;

trait Sluggable
{
    /*
     * Makes from text nice url
     */
    private function toSlug($url) {
        $rules = ['´'=>'','ˇ'=>'','ä'=>'a','Ä'=>'A','á'=>'a','Á'=>'A','à'=>'a','À'=>'A','ã'=>'a','Ã'=>'A','â'=>'a','Â'=>'A','č'=>'c','Č'=>'C','ć'=>'c','Ć'=>'C','ď'=>'d','Ď'=>'D','ě'=>'e','Ě'=>'E','é'=>'e','É'=>'E','ë'=>'e','è'=>'e','È'=>'E','ê'=>'e','Ê'=>'E','í'=>'i','Í'=>'I','ï'=>'i','Ï'=>'I','ì'=>'i','Ì'=>'I','î'=>'i','Î'=>'I','ľ'=>'l','Ľ'=>'L','ĺ'=>'l','Ĺ'=>'L','ń'=>'n','Ń'=>'N','ň'=>'n','Ň'=>'N','ñ'=>'n','Ñ'=>'N','ó'=>'o','Ó'=>'O','ö'=>'o','Ö'=>'O','ô'=>'o','Ô'=>'O','ò'=>'o','Ò'=>'O','õ'=>'o','Õ'=>'O','ő'=>'o','Ő'=>'O','ř'=>'r','Ř'=>'R','ŕ'=>'r','Ŕ'=>'R','š'=>'s','Š'=>'S','ś'=>'s','Ś'=>'S','ť'=>'t','Ť'=>'T','ú'=>'u','Ú'=>'U','ů'=>'u','Ů'=>'U','ü'=>'u','Ü'=>'U','ù'=>'u','Ù'=>'U','ũ'=>'u','Ũ'=>'U','û'=>'u','Û'=>'U','ý'=>'y','Ý'=>'Y','ž'=>'z','Ž'=>'Z','ź'=>'z','Ź'=>'Z'];

        $url = trim($url);
        $url = strtr($url, $rules);
        $url = mb_strtolower($url, 'utf8');
        $url = preg_replace('/[^\-a-z0-9_.]+/', '-', $url);
        $url = preg_replace('[^-*|-*$]', '', $url);
        $url = preg_replace('~(-+)~', '-', $url);
        return $url;
    }

    /*
     * If slug is in db, then add index at the end
     */
    private function makeUnique($slug, $exists)
    {
        $array = explode('-', $exists);

        //Get incement of index
        $index = last($array);

        //If slug has not increment yet, then return with first increment
        if ( ! is_numeric($index) || count( $array ) == 1 )
            return $this->makeSlug(null, $slug . '-1');

        $without_index = array_slice($array, 0, -1);
        $new_slug = implode('-', $without_index );

        //Return old generated slug, with bigger increment index
        return $this->makeSlug(null, $new_slug . '-' . ($index + 1));
    }

    /*
     * Generating steps of slug
     */
    private function makeSlug($text, $slug = null)
    {
        //If is not slug, make first slug from text
        if ( !$slug )
            $slug = $this->toSlug( $text );

        //Checks into database if slug exists
        $row = $this->where('slug', $slug)->withTrashed()->limit(1);

        //If models exists, then skip slug owner
        if ( $this->exists )
            $row->where( $this->getKeyName(), '!=', $this->getKey() );

        $row = $row->get(['slug']);

        //If slug not exists, then return new generated slug
        if ( $row->count() == 0 )
            return $slug;

        //If slug exists, then generate unique slug
        return $this->makeUnique($slug, $row->first()->slug);
    }

    /*
     * Automatically generates slug into model by field
     */
    public function sluggable()
    {
        $array = $this->attributes;

        $slugcolumn = $this->getProperty('sluggable');

        if ( array_key_exists($slugcolumn, $array) )
        {
            $this->attributes['slug'] = $this->makeSlug($array[ $slugcolumn ]);
        }
    }

    /*
     * Returns correct url adress with correct slug
     */
    protected static function buildFailedSlugResponse($slug, $wrong, $id, $key)
    {
        $route = Route::current();

        $current_controller = Route::currentRouteAction();

        $parameters = $route->parameters();

        $binding = [];

        //If is avaiable route key binding, and not exists in actual route
        if ( $key && ! array_key_exists($key, $parameters) )
        {
            abort(500, 'Unknown route identifier: '.$key);
        }

        //Rewrite wrong slug to correct from db
        foreach ($parameters as $k => $value)
        {
            if ( $key == $k || (!$key && $value != $id && $value == $wrong ) )
            {
                $binding[] = $slug;
            } else {
                $binding[] = $value;
            }
        }

        //Returns redirect
        return redirect( action( '\\'.$current_controller, $binding ) );
    }

    private function redirectWithWrongSlug($slug, $id, $key = null)
    {
        //If is definer row where is slug saved
        if ( is_numeric($id) )
        {
            $row = $this->where($this->getKeyName(), $id)->first();

            //Compare given slug and slug from db
            if ($row && $row->slug != $slug)
            {
                throw new SluggableException( $this->buildFailedSlugResponse($row->slug, $slug, $id, $key) );
            }
        }
    }

    /*
     * If is inserted also row of id, then will be compared slug from database and slug from url bar, if is different, automatically
     * redirect to correct route with correct and updated route
     */
    public function scopeWhereSlug($scope, $slug)
    {
        return $scope->where('slug', $slug);
    }


    /**
     * Find a model by its primary slug.
     */
    public function scopefindBySlug($query, $slug, $id = null, $key = null, array $columns = ['*'])
    {
        if ( is_array($id) )
            $columns = $id;
        else if ( ! is_string($id) )
            $id = null;

        $row = $query->whereSlug($slug, $id, $key, $key)->first($columns);

        if ( ! $row )
            $this->redirectWithWrongSlug($slug, $id, $key);

        return $row;
    }

    /**
     * Find a model by its primary slug or throw an exception.
     *
     */
    public function scopefindBySlugOrFail($query, $slug, $id = null, $key = null, array $columns = ['*'])
    {
        if ( is_array($id) )
            $columns = $id;
        else if ( ! is_string($id) )
            $id = null;

        $row = $this->findBySlug($slug, $id, $key, $columns);

        if ( ! $row )
            abort(404);

        return $row;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}