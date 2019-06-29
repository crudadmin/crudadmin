<?php

namespace Admin\Eloquent\Concerns;

use Illuminate\Contracts\Validation\Factory;
use Admin\Exceptions\SluggableException;
use Admin\Models\SluggableHistory;
use Localization;
use Route;

trait Sluggable
{
    /*
     * IF slug is localized
     */
    private $has_localized_slug = null;

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
     * Return which slugs from other row has same slug values with actual editting row;
     */
    private function getLocaleDifferences($slugs, $related_slug)
    {
        if ( $this->hasLocalizedSlug() )
            return array_filter(array_intersect_assoc($slugs, (array)json_decode($related_slug)));
        else
            return array_wrap($related_slug);
    }

    /**
     * Count, increment existing slugs
     * @param  [type]  $slugs           actual slugs set
     * @param  [type]  $key             language key of slug
     * @param  [type]  $index           increment of actual row
     * @param  [type]  $without_index   slug without index
     */
    private function incrementSlug(&$slugs, $key, $index, $without_index)
    {
        $new_slug = implode('-', $without_index );

        $column = $this->hasLocalizedSlug() ?  'JSON_EXTRACT(slug, "$.'.$key.'")' : 'slug';

        $i = 1;

        //Return original slugs
        do {
            $slugs[$key] = $new_slug . '-' . ($index + $i);

            $i++;
        } while($this->where(function($query){
            if ( $this->exists )
                $query->where($this->getKeyName(), '!=', $this->getKey());
        })->whereRaw($column . ' = ?', $slugs[$key])->count() > 0);
    }

    /**
     * If slug exists in db related in other than actual row, then add index at the end into actual slug
     * @param  array  $slugs.          slug values/json slug values
     * @param  [type] $related_slug    slug from other row
     * @return [array]                 set of changed unique slugs
     */
    private function makeUnique($slugs, $related_slug)
    {
        $exists = $this->getLocaleDifferences($slugs, $related_slug);

        foreach ($exists as $key => $value) {
            $array = explode('-', $value);

            //Get incement of index
            $index = last($array);

            //If slug has no increment yet
            if ( ! is_numeric($index) || count( $array ) == 1 ){
                $index = 1;
                $without_index = $array;
            } else {
                $without_index = array_slice($array, 0, -1);
            }

            //Add unique increment into slug
            $this->incrementSlug($slugs, $key, $index, $without_index);
        }

        return array_filter($slugs);
    }

    /**
     * Set empty localization into default language slug
     * @param string $text field value
     * @return [array] set of localized/string slugs
     */
    private function setEmptySlugs($text)
    {
        if ( $text && $this->hasLocalizedSlug() )
        {
            if ( ! $text && $text != 0 )
                return $text;

            $text = (array)json_decode($text);
        } else if ( $text ) {
            $text = array_wrap($text);
        }

        return $text;
    }

    /**
     * Generate slug from field value
     * @param  string $text       field value
     * @return string             return parsed array of localized slugs, or simple slug as string
     */
    private function makeSlug($text)
    {
        $slugs = [];

        $text = $this->setEmptySlugs($text);

        //Bind translated slugs
        foreach ($text as $key => $value)
            $slugs[$key] = $this->toSlug( $value );

        //Checks if some of localized slugs in database exists in other rows
        $row = $this->where(function($query) use ($slugs){
            //If is simple string slug
            if ( ! $this->hasLocalizedSlug() )
                $query->where('slug', $slugs[0]);

            //Multilanguages slug
            else {
                $i = 0;
                foreach ($slugs as $key => $value) {
                    if ( ! $value )
                        continue;

                    $query->{ $i == 0 ? 'whereRaw' : 'orWhereRaw' }('JSON_EXTRACT(slug, "$.'.$key.'") = ?', $value);
                    $i++;
                }
            }
        })->withTrashed()->limit(1);

        //If models exists, then skip slug owner
        if ( $this->exists )
            $row->where( $this->getKeyName(), '!=', $this->getKey() );

        $row = $row->get(['slug']);

        //If new slugs does not exists, then return new generated slug
        if ( $row->count() == 0 )
            return $this->castSlug(array_filter($slugs));

        //Generate new unique slug with increment
        $unique_slug = $this->makeUnique($slugs, $row->first()->slug);

        //If slug exists, then generate unique slug
        return $this->castSlug($unique_slug);
    }

    /*
     * Return casted valeu of slug (json or string)
     */
    private function castSlug($slugs)
    {
        if ( $this->hasLocalizedSlug() ){
            if ( is_array($slugs) )
                return json_encode($slugs);

            return null;
        }

        return is_array($slugs) ? $slugs[0] : null;
    }

    /*
     * Return if is column localized
     */
    public function hasLocalizedSlug()
    {
        if ( $this->has_localized_slug !== null )
            return $this->has_localized_slug;

        $slugcolumn = $this->getProperty('sluggable');

        return $this->has_localized_slug = $this->hasFieldParam($slugcolumn, 'locale', true);
    }

    /*
     * Automatically generates slug into model by field
     */
    public function sluggable()
    {
        $array = $this->attributes;

        $slugcolumn = $this->getProperty('sluggable');

        //Set slug
        if ( array_key_exists($slugcolumn, $array) )
        {
            $slug = $this->makeSlug($array[ $slugcolumn ]);

            //If slug has been changed, then save previous slug state
            if ( $this->exists && $this->isAllowedHistorySlugs() && str_replace('": "', '":"', $this->attributes['slug']) != $slug )
                $this->slugSnapshot();

            $this->attributes['slug'] = $slug;
        }
    }

    /*
     * Check if history slugs are allowed
     */
    public function isAllowedHistorySlugs()
    {
        return config('admin.sluggable_history', false) === true
               && $this->getProperty('sluggable_history') !== false;
    }

    /*
     * Save slug state
     */
    public function slugSnapshot()
    {
        SluggableHistory::snapshot($this);
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
        return redirect( action( '\\'.$current_controller, $binding ), 301 );
    }

    private function redirectWithWrongSlug($slug, $id, $key = null, $row)
    {
        //If is definer row where is slug saved
        if ( is_numeric($id) )
        {
            $row = $this->where($this->getKeyName(), $id)->select(['slug'])->first();

            //Compare given slug and slug from db
            if ($row && $row->slug != $slug)
            {
                throw new SluggableException( $this->buildFailedSlugResponse($row->slug, $slug, $id, $key) );
            }
        }

        if ( $this->isAllowedHistorySlugs() )
            $this->redirectWithSlugFromHistory($slug, $id, $key);
    }

    private function redirectWithSlugFromHistory($slug, $id = null, $key)
    {
        $history_model = new SluggableHistory;
        $history_model->has_localized_slug = $this->hasLocalizedSlug();

        $history_row = $history_model
                        ->where('table', $this->getTable())
                        ->whereSlug($slug, $history_model->getTable() . '.' . $history_model->getSlugColumnName($this))
                        ->whereExists(function ($query) use ($history_model) {
                            $query->select(['id'])
                                  ->from($this->getTable())
                                  ->whereRaw($history_model->getTable().'.row_id = '.$this->getTable().'.id')
                                  ->when($this->publishable, function($query){
                                        $query->where('published_at', '!=', null)->whereRAW('published_at <= NOW()');
                                  })
                                  ->where('deleted_at', null);
                        })
                        ->leftJoin($this->getTable(), $history_model->getTable().'.row_id', '=', $this->getTable().'.id')
                        ->select($this->getTable().'.slug')
                        ->first();

        if ( ! $history_row )
            return;

        $history_row->has_localized_slug = $this->hasLocalizedSlug();

        $new_slug = $history_row->getSlug();

        throw new SluggableException( $this->buildFailedSlugResponse($new_slug, $slug, $id, $key) );
    }

    /*
     * If is inserted also row of id, then will be compared slug from database and slug from url bar, if is different, automatically
     * redirect to correct route with correct and updated route
     */
    public function scopeWhereSlug($scope, $slug_value, $column = null)
    {
        if ( ! $column )
            $column = 'slug';

        if ( ! $this->hasLocalizedSlug() )
            return $scope->where($column, $slug_value);

        $lang = Localization::get();

        $default = Localization::getDefaultLanguage();

        //Find slug from selected language
        $scope->whereRaw('JSON_EXTRACT('.$column.', "$.'.$lang->slug.'") = ?', $slug_value);

        //If selected language is other than default
        if ( $lang->getKey() != $default->getKey() )
        {
            //Then search also values in default language
            $scope->orWhere(function($query) use($lang, $default, $slug_value, $column) {
                $query->whereRaw('JSON_EXTRACT('.$column.', "$.'.$lang->slug.'") is NULL')
                      ->whereRaw('JSON_EXTRACT('.$column.', "$.'.$default->slug.'") = ?', $slug_value);
            });
        }
    }

    public function scopeFindBySlug($query, $slug, $id = null, $key = null, array $columns = ['*'])
    {
        return static::findBySlug($slug, $id, $key, $columns, $query);
    }

    public function scopeFindBySlugOrFail($query, $slug, $id = null, $key = null, array $columns = ['*'])
    {
        return static::findBySlugOrFail($slug, $id, $key, $columns, $query);
    }

    /**
     * Find a model by its primary slug.
     */
    public static function findBySlug($slug, $id = null, $key = null, array $columns = ['*'], $query = null)
    {
        if ( is_array($id) )
            $columns = $id;
        else if ( ! is_string($id) )
            $id = null;

        $row = ($query ?: new static)->whereSlug($slug)->first($columns);

        if ( ! $row )
            (new static)->redirectWithWrongSlug($slug, $id, $key, $row);

        return $row;
    }

    /**
     * Find a model by its primary slug or throw an exception.
     *
     */
    public static function findBySlugOrFail($slug, $id = null, $key = null, array $columns = ['*'], $query = null)
    {
        if ( is_array($id) )
            $columns = $id;
        else if ( ! is_string($id) )
            $id = null;

        $row = static::findBySlug($slug, $id, $key, $columns, $query);

        if ( ! $row )
            abort(404);

        return $row;
    }

    public function getSlug()
    {
        if ( $this->hasLocalizedSlug() )
        {
            $slug = (array)json_decode($this->slug);

            $lang = Localization::get();

            //Return selected language slug
            if ( array_key_exists($lang->slug, $slug) && $slug[$lang->slug] )
                return $slug[$lang->slug];

            $default = Localization::getFirstLanguage();

            //Return default slug value
            if ( $default->getKey() != $lang->getKey() && array_key_exists($default->slug, $slug) && $slug[$default->slug] )
                return $slug[$default->slug];

            //Return one of set slug from any language
            foreach (Localization::getLanguages() as $lang) {
                if ( array_key_exists($lang->slug, $slug) && $slug[$lang->slug] )
                    return $slug[$lang->slug];
            }

            return null;
        }

        return $this->slug;
    }
}