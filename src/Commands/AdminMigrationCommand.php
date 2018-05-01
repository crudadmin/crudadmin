<?php

namespace Gogol\Admin\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Schema\Blueprint;
use Symfony\Component\Console\Input\InputOption;
use \Doctrine\DBAL\Types\Type as DBType;
use Localization;
use Admin;
use Schema;
use Cache;
use DB;

class AdminMigrationCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'admin:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database migrations from all admin models';

    protected $files;

    /*
     * Here will is migrations which will be booted at the end after all migrations
     */
    protected $buffer = [];

    /*
     * Here will is migrations which will be booted at the end of actual migration
     */
    protected $buffer_after = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->files = new Filesystem;

        $this->fixJsonColumns();

        //DB doctrine fix for enum columns
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    protected function fire()
    {
        $models = Admin::getAdminModels();

        $migrations = $this->migrate( $models );
    }

    /*
     * Fix json columns in doctrine dbal
     */
    protected function fixJsonColumns()
    {
        //Add json support
        if ( ! DBType::hasType('json') )
        {
            DBType::addType('json', \Doctrine\DBAL\Types\JsonArrayType::class);
            DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('json', 'string');
        }
    }

    /*
     * Return if is class updated
     */
    protected function isOutOfDate($model, $migration)
    {
        $path = (new \ReflectionClass($model))->getFileName();

        //If file class does not exists
        if ( ! file_exists($path) )
        {
            //Migrate
            call_user_func($migration);

            return false;
        }

        $namespace = 'admin_migrations.' . md5(get_class($model));

        $hash = md5_file($path);

        if ( $this->option('force') === false && Cache::get($namespace) == $hash )
            return true;

        //Migrate
        call_user_func($migration);

        //Cache model after migration done
        Cache::forever($namespace, $hash);

        return false;
    }

    /**
     * Generate laravel migratons
     * @return [type] [description]
     */
    protected function migrate($models)
    {
        $migrated = 0;

        foreach ($models as $model)
        {
            $migration = function() use ($model) {
                $this->generateMigration($model);
            };

            //Check if migration is out of date from cache
            if ( $this->isOutOfDate($model, $migration) )
                continue;

            $migrated++;
        }

        if ( $migrated === 0 )
            return $this->line('<info>Noting to migrate.</info>');

        /*
         * Run migrations from buffer
         */
        foreach ($models as $model)
        {
            $this->runFromCache($model);
        }
    }

    /**
     * Generate laravel migratons
     * @return [type] [description]
     */
    protected function generateMigration($model)
    {
        if ( $model->getSchema()->hasTable( $model->getTable() ) )
        {
            $this->updateTable( $model );
        } else {
            $this->createTable( $model );
        }

        //Checks if model has some extre migrations on create
        if ( method_exists($model, 'onMigrate') )
        {
            $this->buffer[ $model->getTable() ][] = function( $table ) use( $model ) {
                $model->onMigrate($table, $model->getSchema());
            };
        }

        //Run migrations from cache which have to be runned after actual migration
        $this->runFromCache($model, 'buffer_after');
    }

    /*
     * Run all migrations saved into buffer
     */
    public function runFromCache($model, $from = 'buffer')
    {
        $table = $model->getTable();

        if ( ! array_key_exists($table, $this->{$from}) )
            return;

        foreach ($this->{$from}[ $table ] as $function)
        {
            $model->getSchema()->table( $table , function (Blueprint $table) use ($function) {
                $function($table);
            });
        }
    }

    /*
     * Skip creating of preddefined columns
     */
    private function skipField($key, $model = null)
    {
        $columns = ['_order', 'created_at', 'published_at', 'updated_at'];

        //When slug is allowed
        if ( $model && $model->getProperty('sluggable') != null )
            $columns[] = 'slug';

        return in_array($key, $columns);
    }

    /**
     * Create table from model
     * @return void
     */
    protected function createTable($model)
    {
        $model->getSchema()->create( $model->getTable() , function (Blueprint $table) use ($model) {

            //Increment
            $table->increments('id');

            //Add relationships with other models
            $this->addRelationships($table, $model);

            foreach ($model->getFields() as $key => $value)
            {
                if ( $this->skipField($key) )
                    continue;

                $this->setColumn( $table, $model, $key );

                //Sluggable column
                if ( $model->getProperty('sluggable') != null && $model->getProperty('sluggable') == $key )
                    $this->setSlug( $table, $model );
            }

            //Add multilanguage support
            $this->createLanguageRelationship($table, $model);

            //Order column for sorting rows
            if ( $model->isSortable() )
                $table->integer('_order')->unsigned();

            //Published at column
            if ( $model->getProperty('publishable') == true)
                $table->timestamp('published_at')->nullable()->default( DB::raw( 'CURRENT_TIMESTAMP' ) );

            //Softdeletes
            $table->softDeletes();

            //Timestamps
            if ( $model->getProperty('timestamps') == true )
                $table->timestamps();
        });

        $this->line('<comment>Created table:</comment> '.$model->getTable());
    }

    /**
     * Update existing table
     * @return void
     */
    protected function updateTable($model)
    {
        $this->line('<info>Updated table:</info> '.$model->getTable());

        $model->getSchema()->table( $model->getTable() , function (Blueprint $table) use ($model) {
            //Add relationships with other models
            $this->addRelationships($table, $model, true);

            //Which columns will be added in reversed order
            $add_columns = [];

            //Which columns has been added, so next columns can not be added after this columns,
            //because this columns are not in database yet
            $except_columns = [];

            foreach ($model->getFields() as $key => $value)
            {
                if ( $this->skipField($key) )
                    continue;

                //Checks if table has column and update it if can...
                if ( $model->getSchema()->hasColumn($model->getTable(), $key) ){
                    if ( $column = $this->setColumn( $table, $model, $key, true ) )
                    {
                        $column->change();
                    }
                } else {
                    $except_columns[] = $key;

                    $add_columns[] = [
                        'key' => $key,
                        'callback' => function($except_columns) use ($table, $model, $key, $value){
                            if ( $column = $this->setColumn( $table, $model, $key ) )
                            {
                                $previous_column = $this->getPreviousColumn($model, $key, $except_columns);

                                if ( $model->getSchema()->hasColumn($model->getTable(), $previous_column) )
                                    $column->after( $previous_column );

                                //If column does not exists, then add before deleted ad column
                                else if ( $model->getSchema()->hasColumn($model->getTable(), 'deleted_at') )
                                    $column->after( 'id' );
                            }

                            return $column;
                        },
                    ];
                }
            }

            //Add columns in reversed order
            for ( $i = count($add_columns) - 1; $i >= 0; $i-- )
            {
                //if no column has been added, then remove column from array for messages
                if ( !($column = call_user_func_array($add_columns[$i]['callback'], [ $except_columns ])) )
                {
                    unset($add_columns[$i]);
                }
            }

            //Which columns has been successfully added
            foreach ($add_columns as $row)
                $this->line('<comment>+ Added column:</comment> '.$row['key']);

            //Add multilanguage support
            if ( ! $model->getSchema()->hasColumn($model->getTable(), 'language_id') )
            {
                $this->createLanguageRelationship($table, $model, true);
            }

            //Order column
            if ( ! $model->getSchema()->hasColumn($model->getTable(), '_order') && $model->isSortable() )
            {
                $table->integer('_order')->unsigned();
                $this->line('<comment>+ Added column:</comment> _order');

                //Insert default increment into order
                $this->addDefaultOrder($model);
            }

            //Sluggable column
            if ( $model->getProperty('sluggable') != null )
            {
                if ( ! $model->getSchema()->hasColumn($model->getTable(), 'slug') )
                {
                    $this->setSlug($table, $model, true, true);
                    $this->line('<comment>+ Added column:</comment> slug');
                } else {
                    if ( $setSlug = $this->setSlug($table, $model, true) )
                        $setSlug->change();
                }
            }

            //Published at column
            if ( ! $model->getSchema()->hasColumn($model->getTable(), 'published_at') && $model->getProperty('publishable') == true )
            {
                $table->timestamp('published_at')->nullable()->default( DB::raw( 'CURRENT_TIMESTAMP' ) );
                $this->line('<comment>+ Added column:</comment> published_at');
            }

            //Deleted at
            if ( ! $model->getSchema()->hasColumn($model->getTable(), 'deleted_at') )
            {
                $table->softDeletes();
                $this->line('<comment>+ Added column:</comment> deleted_at');
            }

            /**
             *  Automatic dropping columns
             */
            $base_fields = $model->getBaseFields(true);

            //Removes unneeded columns
            foreach ($model->getSchema()->getColumnListing($model->getTable()) as $column)
            {
                if ( ! in_array($column, $base_fields) && ! in_array($column, (array)$model->getProperty('skipDropping')) )
                {
                    $this->line('<comment>+ Unknown column:</comment> '.$column);

                    if ( $this->confirm('Do you want drop this column? [y|N]') )
                    {
                        if ( $this->hasIndex($model, $column) )
                        {
                            $this->dropIndex($model, $column);
                        }

                        $table->dropColumn($column);

                        $this->line('<comment>+ Dropped column:</comment> '.$column);
                    }
                }
            }
        });

    }

    /*
     * Returns field before selected field, if is selected field first, returns last field
     */
    public function getPreviousColumn($model, $find_key, $except = [])
    {
        $last = 'id';
        $i = 0;

        foreach ($model->getFields() as $key => $item)
        {
            if ( $key == $find_key )
            {
                if ( $i == 0 )
                    return 'id';
                else
                    return $last;
            }

            $i++;

            if ( !$model->hasFieldParam($key, 'belongsToMany') && !in_array($key, $except) )
                $last = $key;
        }

        return $last;
    }


    /*
     * Returns foreign key name
     */
    protected function getForeignKeyName($model, $key, $prefix = null)
    {
        return $model->getTable().'_'.$key.'_'.($prefix ? : 'foreign');
    }

    /*
     * Returns if table has index
     */
    protected function hasIndex($model, $key, $prefix = null)
    {
        return count( $model->getConnection()->select(
            DB::raw(
                'SHOW KEYS
                FROM '.$model->getTable().'
                WHERE Key_name=\''. $this->getForeignKeyName($model, $key, $prefix) . '\''
            )
        ) );
    }

    /*
     * Set json column, also check mysql version
     */
    private function setJsonColumn($table, $key, $model)
    {
        $this->checkForCorrectMysqlVersion($model, 'file');

        //Set json column, with resetted collations with platformOptions
        //Doctrine\DBAL\Schema\MySqlSchemaManager

        return $table->json($key)->platformOptions([]);
    }

    /*
     * Drops foreign key in table
     */
    protected function dropIndex($model, $key)
    {
        return $model->getConnection()->select(
            DB::raw( 'alter table `'.$model->getTable().'` drop foreign key `'.$this->getForeignKeyName($model, $key) .'`' )
        );
    }

    protected function fileColumn($table, $model, $key)
    {
        if ( $model->isFieldType($key, 'file') )
        {
            return $table->string($key, $model->getFieldLength($key));
        }
    }

    protected function jsonColumn($table, $model, $key)
    {
        if ( $model->isFieldType($key, ['json']) || $model->hasFieldParam($key, ['locale', 'multiple']) )
        {
            return $this->setJsonColumn($table, $key, $model);
        }
    }

    protected function stringColumn($table, $model, $key)
    {
        if ( $model->isFieldType($key, ['string', 'password', 'radio']) )
        {
            return $table->string($key, $model->getFieldLength($key));
        }
    }

    protected function textColumn($table, $model, $key)
    {
        //Text columns
        if ( $model->isFieldType($key, ['text', 'editor']) )
        {
            return $table->text($key);
        }
    }

    protected function longtextColumn($table, $model, $key)
    {
        //Text columns
        if ( $model->isFieldType($key, ['longtext', 'longeditor']) )
        {
            return $table->longText($key);
        }
    }

    protected function integerColumn($table, $model, $key)
    {
        //Integer columns
        if ( $model->isFieldType($key, 'integer') )
        {
            $column = $table->integer($key);

            //Check if is integer unsigned or not
            if ($model->hasFieldParam($key, 'min') && $model->getFieldParam($key, 'min') >= 0)
                $column->unsigned();

            return $column;
        }
    }

    protected function decimalColumn($table, $model, $key)
    {
        //Decimal columns
        if ( $model->isFieldType($key, 'decimal') )
        {
            $column = $table->decimal($key, 8, 2);

            //Check if is integer unsigned or not
            if ($model->hasFieldParam($key, 'min') && $model->getFieldParam($key, 'min') >= 0)
                $column->unsigned();

            return $column;
        }
    }

    protected function datetimeColumn($table, $model, $key, $update = false)
    {
        //Timestamp columns
        if ( $model->isFieldType($key, ['date', 'datetime', 'time']) )
        {
            //Check for correct values
            if ( $update === true )
            {
                $type = $model->getConnection()->getDoctrineColumn($model->getTable(), $key)->getType()->getName();

                //If previoius column has not been datetime and has some value
                if ( $type != 'datetime' )
                    $model->getConnection()->table($model->getTable())->update([ $key => null ]);
            }

            $column = $table->datetime($key)->nullable();

            return $column;
        }
    }

    protected function selectColumn($table, $model, $key)
    {
        if ( $model->isFieldType($key, 'select') )
        {
            return $table->string($key, $model->getFieldLength($key));
        }
    }

    protected function checkboxColumn($table, $model, $key)
    {
        if ( $model->isFieldType($key, 'checkbox') )
        {
            $default = $model->hasFieldParam($key, 'default') ? $model->getFieldParam($key, 'default') : 0;

            return $table->boolean($key)->default( $default );
        }
    }

    /*
     * Add relationship for column created by developer
     */
    public function belongsTo($table, $model, $key)
    {
        if ( $model->hasFieldParam($key, 'belongsTo') )
        {
            $properties = $model->getRelationProperty($key, 'belongsTo');

            $parent = Admin::getModelByTable($properties[0]);

            //If table in belongsTo relation does not exists
            if ( ! $parent )
            {
                $this->line('<error>Table '.$properties[0].' does not exists.</error>');
                die;
            }

            //If foreign key in table exists
            $keyExists = 0;

            if ( $tableExists = $model->getSchema()->hasTable( $model->getTable() ) )
            {
                $keyExists = $this->hasIndex($model, $key);
            }

            //If table has not foreign column
            if ( $keyExists == 0 && $parent->getSchema()->hasTable( $parent->getTable() ) )
            {
                if ( $tableExists === true && $model->count() > 0 )
                {
                    //Checks if table has already inserted rows which won't allow insert foreign key without NULL value
                    if ( $model->hasFieldParam($key, 'required', true) )
                    {
                        $this->checkForReferenceTable($model, $key, $properties[0]);
                    }
                }

                $this->buffer[ $model->getTable() ][] = function( $table ) use ( $key, $properties, $model )
                {
                    $table->foreign($key)->references($properties[2])->on($properties[0]);
                };
            }

            return $table->integer($key)->unsigned();
        }
    }

    //Checks if table has already inserted rows which won't allow insert foreign key without NULL value
    protected function checkForReferenceTable($model, $key, $reference_table)
    {
        $this->line('<comment>+ Cannot add foreign key for</comment> <error>'.$key.'</error> <comment>column into</comment> <error>'.$model->getTable().'</error> <comment>table with reference on</comment> <error>'.$reference_table.'</error> <comment>table.</comment>');
        $this->line('<comment>  Because table has already inserted rows. But you can insert value for existing rows for this</comment> <error>'.$key.'</error> <comment>column.</comment>');

        $ids_in_reference_table = Admin::getModelByTable($reference_table)->take(10)->select('id')->pluck('id');

        if ( count($ids_in_reference_table) > 0 )
        {
            $this->line('<comment>+ Here are some ids from '.$reference_table.' table:</comment> '.implode($ids_in_reference_table->toArray(), ', '));

            //Define ids for existing rows
            do {
                $requested_id = $this->ask('Which id would you like define for existing rows?');

                if ( !is_numeric($requested_id) )
                    continue;

                if ( Admin::getModelByTable($reference_table)->where('id', $requested_id)->count() == 0 )
                {
                    $this->line('<error>Id #'.$requested_id.' does not exists.</error>');
                    $requested_id = false;
                }
            } while( ! is_numeric($requested_id) );

            $this->buffer_after[ $model->getTable() ][] = function() use ( $model, $key, $requested_id )
            {
                DB::connection($model->getConnectionName())->table($model->getTable())->update([ $key => $requested_id ]);
            };
        } else {
            $this->line('<error>+ You have to insert at least one row into '.$reference_table.' reference table or remove all existing data in actual '.$model->getTable().' table:</error>');
            die;
        }
    }

    protected function makeForeignIndexForBelongsToMany($table, $key)
    {
        $table_index = '';

        $table = preg_replace('/_+/', '_', $table);

        foreach((array)explode('_', $table) as $t)
        {
            $table_index .= $t[0];
        }

        return 'fk_'.$table_index.'_'.$key;
    }

    /*
     * Add relationship for column created by developer
     */
    public function belongsToMany($table, $model, $key)
    {
        if ( $model->hasFieldParam($key, 'belongsToMany') )
        {
            $this->buffer[ $table->getTable() ][] = function() use($table, $model, $key) {
                $properties = $model->getRelationProperty($key, 'belongsToMany');

                //If pivot table non exists
                if ( ! $model->getSchema()->hasTable( $properties[3] ) )
                {
                    //Create pivot table
                    $model->getSchema()->create( $properties[3] , function (Blueprint $table) use ( $model, $properties ) {
                        //Increment
                        $table->increments('id');

                        //Add integer reference for owner table
                        $table->integer( $properties[6] )->unsigned();
                        $table->foreign( $properties[6], $this->makeForeignIndexForBelongsToMany($properties[3], $properties[6]) )->references($model->getKeyName())->on( $model->getTable() );

                        //Add integer reference for belongs to table
                        $table->integer( $properties[7] )->unsigned();
                        $table->foreign( $properties[7], $this->makeForeignIndexForBelongsToMany($properties[3], $properties[7]) )->references($properties[2])->on( $properties[0] );
                    });

                    $this->line('<comment>Created table:</comment> '.$properties[3]);
                } else {
                    $this->line('<info>Checked table:</info> '.$properties[3]);

                    if ( ! $model->getSchema()->hasColumn($properties[3], 'id') )
                    {
                        $model->getSchema()->table( $properties[3] , function (Blueprint $table) use ( $model, $properties ) {
                            //Increment
                            $table->increments('id')->first();
                        });

                        $this->line('<comment>+ Added column:</comment> id');
                    }

                }
            };

            return true;
        }
    }

    protected function setSlug($table, $model, $updating = false, $render = true)
    {
        $slugcolumn = $model->getProperty('sluggable');

        if ( ! ($field = $model->getField($slugcolumn)) )
        {
            $this->line('<comment>+ Unknown slug column for</comment> <error>'.$slugcolumn.'</error> <comment>column</comment>');

            return;
        }

        //Set locale slug or normal
        if ( $has_locale = $model->hasFieldParam($slugcolumn, 'locale', true) )
            $column = $this->setJsonColumn($table, 'slug', $model);
        else
            $column = $table->string('slug', $model->getFieldLength($slugcolumn));

        if ( $updating == true )
        {
            $column->after( $slugcolumn );
        }

        //If is creating new table or when slug index is missing
        if ( !$has_locale && ($updating == false || ! $this->hasIndex($model, 'slug', 'index')) )
            $column->index();

        //If is field required
        if( ! $model->hasFieldParam( $slugcolumn , 'required') )
            $column->nullable();

        //If was added column to existing table, then reload sluggs
        if ( $render == true )
        {
            $this->updateSlugs($model);
        }

        return $column;
    }

    //Resave all rows in model for updating slug if needed
    protected function updateSlugs($model)
    {
        $this->buffer_after[ $model->getTable() ][] = function() use ($model) {

            //Get empty slugs
            $empty_slugs = $model->where(function($query) use ($model) {
                //If some of localized slug value is empty
                if ( $model->hasLocalizedSlug() )
                {
                    $languages = Localization::getLanguages(true);

                    //Check all available languages slugs
                    foreach ($languages as $key => $lang)
                    {
                        $query->{ $key == 0 ? 'where' : 'orWhere' }(function($query) use($model, $lang) {
                            //If row has defined localized value, but slug is missing
                            $query->whereRaw('JSON_EXTRACT(slug, "$.'.$lang->slug.'") is NULL')
                                  ->whereRaw('JSON_EXTRACT('.$model->getProperty('sluggable').', "$.'.$lang->slug.'") is NOT NULL');
                        });
                    }
                }

                //If simple slug is empty
                else {
                    $query->whereNull('slug')->orWhere('slug', '');
                }

            })->orWhere('slug', null);

            //If has been found some empty slugs
            if ( $empty_slugs->count() > 0 )
            {
                //Re-save models, and regenerate new slugs
                foreach ($empty_slugs->select([$model->getKeyName(), 'slug', $model->getProperty('sluggable')])->get() as $row)
                    $row->save();
            }
        };
    }

    //Resave all rows in model for updating slug if needed
    protected function addDefaultOrder($model)
    {
        $this->buffer_after[ $model->getTable() ][] = function() use ($model) {
            $i = 0;

            foreach ($model->get() as $row)
            {
                $row->_order = $i++;
                $row->save();
            }
        };
    }

    /**
     * Set all properties of column into migration
     * @param [object] $table
     * @param [object] $model
     * @param [string] $key
     */
    protected function setColumn($table, $model, $key, $update = false)
    {
        //Registred column types
        $types = [
            'belongsTo',
            'belongsToMany',
            'jsonColumn',
            'stringColumn',
            'textColumn',
            'longtextColumn',
            'integerColumn',
            'decimalColumn',
            'fileColumn',
            'datetimeColumn',
            'selectColumn',
            'checkboxColumn',
        ];

        //Get column
        foreach ($types as $column) {
            if ( $column = $this->{$column}($table, $model, $key, $update) )
                break;
        }

        //Unknown column type
        if ( !$column )
            $this->line('<comment>+ Unknown field type</comment> <error>'.$model->getFieldType($key).'</error> <comment>in field</comment> <error>'.$key.'</error>');

        if ( !$column || $column === true )
        {
            return;
        }

        //If is field required
        if( ! $model->hasFieldParam($key, 'required') )
            $column->nullable();

        //If field is index
        if( $model->hasFieldParam($key, 'index') &&
            (
                !$model->getSchema()->hasTable( $model->getTable() ) ||
                !$this->hasIndex($model, $key, 'index')
            )
        ){
            $column->index();
        }

        //If is field required
        if( $this->canSetDefault($model, $key) )
        {
            $default = $model->getFieldParam($key, 'default');

            //Set default timestamp
            if ( $default && $model->isFieldType($key, ['date', 'datetime', 'time']) )
            {
                if ( strtoupper($default) == 'CURRENT_TIMESTAMP' )
                    $default = DB::raw('CURRENT_TIMESTAMP');
                else
                    $default = null;
            }

            $column->default( $default );
        } else {
            $column->default(NULL);
        }

        return $column;
    }

    /*
     * If default value can be set in db
     */
    private function canSetDefault($model, $key)
    {
        return $model->hasFieldParam($key, 'default') && ! $model->isFieldType($key, ['belongsTo', 'belongsToMany']);
    }

    /*
     * Add language_id relationship
     */
    protected function createLanguageRelationship($table, $model, $updating = false)
    {
        //If is multi languages support
        if ( ! $model->isEnabledLanguageForeign() )
            return $table;

        $_table = $table->integer('language_id')->unsigned()->nullable();

        if ( $updating == true )
        {
            $_table->after('id');
        }

        $table->foreign('language_id')->references('id')->on('languages');
    }

    /**
     * Check or add relationships with other admin models
     * @param [object] $table
     * @param [object] $model
     */
    public function addRelationships($table, $model, $updating = false)
    {
        $belongsToModel = $model->getBelongsToRelation();

        $count = count($belongsToModel);

        //Model without parent
        if ( $count == 0 )
            return;

        if ( $updating === true )
            $belongsToModel = array_reverse($belongsToModel);

        foreach ($belongsToModel as $parent)
        {
            $is_recursive = class_basename(get_class($model)) == class_basename($parent);

            //If is recursive model, then do not create new same instance, because of bug when parent model
            //is overidded and has relationship on itself in package with other namespace
            $parent = $is_recursive ? $model : new $parent;

            $foreign_column = $model->getForeignColumn( $parent->getTable() );

            $column = $table->integer( $foreign_column )->unsigned();

            //If parent belongs to more models, or just itself
            if ( count($belongsToModel) > 1 || $model->getProperty('withoutParent') == true || $is_recursive )
                $column->nullable();

            //If foreign key does not exists in table
            if ( ! $model->getSchema()->hasColumn($model->getTable(), $foreign_column) )
            {
                //If column does not exists in already created table, then create it after id
                if ( $updating === true )
                {
                    $column->after('id');

                    //If is one foreign column, this columns is not null
                    //so if some rows exists, we need push values into this row
                    if ( count($belongsToModel) == 1 && $model->count() > 0 )
                        $this->checkForReferenceTable($model, $foreign_column, $parent->getTable());

                    $this->line('<comment>+ Added column:</comment> '.$foreign_column);
                }
            } else if ( $updating === true ) {
                $column->change();
                continue;
            }

            if ( $parent->getConnection() != $model->getConnection() )
            {
                $this->line('<comment>+ Skipped foreign relationship:</comment> '.$foreign_column . ' <comment>( different db connections )</comment> ');
                continue;
            }

            $this->buffer[ $model->getTable() ][] = function( $table ) use ($foreign_column, $parent) {
                $table->foreign( $foreign_column )->references( 'id' )->on( $parent->getTable() );
            };
        }
    }

    //Returns schema with correct connection
    protected function getSchema($model)
    {
        return Schema::connection( $model->getProperty('connection') );
    }

    /*
     * Checks if DB supports mysql columns
     */
    public function checkForCorrectMysqlVersion($model, $type = null)
    {
        $pdo     = $model->getConnection()->getPdo();
        $version = $pdo->query('select version()')->fetchColumn();

        (float)$version = mb_substr($version, 0, 6);

        //Compare of mysql versions
        if (version_compare($version, '5.7.0', '<')) {
            $this->line('<error>Sorry, but JSON columns are not supported in your MySQL '.$version.' database.</error>');
            $this->line('<comment>You need minimum MySQL 5.7.0 for supporting multiple '.($type == 'select' ? 'select columns' : 'upload files').'.<comment>');
            die;
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->fire();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Forced migration of all models'],
        ];
    }
}