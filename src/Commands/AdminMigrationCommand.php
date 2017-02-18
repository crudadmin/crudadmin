<?php

namespace Gogol\Admin\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use Admin;
use Schema;
use DB;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Schema\Blueprint;

class AdminMigrationCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:migrate';

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

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->files = new Filesystem;

        //Add json support
        \Doctrine\DBAL\Types\Type::addType('json', \Doctrine\DBAL\Types\JsonArrayType::class);

        //DB doctrine fix for enum columns
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('json', 'string');
    }

    protected function fire()
    {
        $models = Admin::getAdminModels();

        $migrations = $this->migrate( $models );
    }

    /**
     * Generate laravel migratons
     * @return [type] [description]
     */
    protected function migrate($models)
    {
        foreach ($models as $model)
        {
            $this->generateMigration($model);
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
            $this->buffer[ $model->getTable() ][] = function() use($model) {

                $model->getSchema()->table( $model->getTable() , function (Blueprint $table) use ($model) {
                    $model->onMigrate($table, $model->getSchema());
                });

            };
        }

        $this->runFromCache($model->getTable());
    }

    public function runFromCache($table)
    {
        if ( ! array_key_exists($table, $this->buffer) )
            return;

        foreach ($this->buffer[ $table ] as $function)
        {
            $function();
        }
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
                $this->setColumn( $table, $model, $key );

                //Sluggable column
                if ( $model->getProperty('sluggable') != null && $model->getProperty('sluggable') == $key )
                    $this->setSlug( $table, $model );
            }

            //Add multilanguage support
            $this->createLanguageRelationship($table, $model);

            //Order column for sorting rows
            if ( $model->getProperty('sortable') == true )
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

        $this->line('<info>Created table:</info> '.$model->getTable());
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

            foreach ($model->getFields() as $key => $value)
            {
                //Checks if table has column
                if ( $model->getSchema()->hasColumn($model->getTable(), $key) ){
                    if ( $column = $this->setColumn( $table, $model, $key ) )
                        $column->change();
                } else {
                    $column = $this->setColumn( $table, $model, $key );

                    if ( $column && $model->getSchema()->hasColumn($model->getTable(), $model->beforeFieldName($key)) )
                        $column->after( $model->beforeFieldName($key) );

                    if ( $column )
                        $this->line('<comment>+ Added column:</comment> '.$key);
                }
            }

            //Add multilanguage support
            if ( ! $model->getSchema()->hasColumn($model->getTable(), 'language_id') )
            {
                $this->createLanguageRelationship($table, $model, true);
            }

            //Order column
            if ( ! $model->getSchema()->hasColumn($model->getTable(), '_order') && $model->getProperty('sortable') == true )
            {
                $table->integer('_order')->unsigned();
                $this->line('<comment>+ Added column:</comment> _order');
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
     * Returns foreign key name
     */
    protected function getForeignKeyName($model, $key)
    {
        return $model->getTable().'_'.$key.'_foreign';
    }

    /*
     * Returns if table has index
     */
    protected function hasIndex($model, $key)
    {
        return count( $model->getConnection()->select(
            DB::raw(
                'SHOW KEYS
                FROM '.$model->getTable().'
                WHERE Key_name=\''. $this->getForeignKeyName($model, $key) . '\''
            )
        ) );
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
            if ( $model->hasFieldParam($key, 'multiple') )
                return $table->json($key);

            return $table->string($key, $model->getFieldLength($key));
        }
    }

    protected function stringColumn($table, $model, $key)
    {
        if ( $model->isFieldType($key, ['string', 'password']) )
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
        //Integer columns
        if ( $model->isFieldType($key, 'decimal') )
        {
            $column = $table->decimal($key, 8, 2);

            //Check if is integer unsigned or not
            if ($model->hasFieldParam($key, 'min') && $model->getFieldParam($key, 'min') >= 0)
                $column->unsigned();

            return $column;
        }
    }

    protected function dateColumn($table, $model, $key)
    {
        //Integer columns
        if ( $model->isFieldType($key, 'date') )
        {
            $column = $table->date($key);

            return $column;
        }
    }

    protected function selectColumn($table, $model, $key)
    {
        if ( $model->isFieldType($key, 'select') )
        {
            if ( $model->hasFieldParam($key, 'multiple') )
            {
                return $table->json($key);
            } else {
                return $table->string($key, $model->getFieldLength($key));
            }
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

            $keyExists = 0;

            if ( $model->getSchema()->hasTable( $model->getTable() ) )
            {
                $keyExists = $this->hasIndex($model, $key);
            }

            //If table has not foreign column
            if ( $keyExists == 0 )
                $table->foreign($key)->references($properties[2])->on($properties[0]);

            return $table->integer($key)->unsigned();
        }
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
                        //Add integer reference for owner table
                        $table->integer( $properties[6] )->unsigned();
                        $table->foreign( $properties[6] )->references($model->getKeyName())->on( $model->getTable() );

                        //Add integer reference for belongs to table
                        $table->integer( $properties[7] )->unsigned();
                        $table->foreign( $properties[7] )->references($properties[2])->on( $properties[0] );
                    });

                    $this->line('<info>Created table:</info> '.$properties[3]);
                } else {
                    $this->line('<info>Skipped table:</info> '.$properties[3]);
                }
            };

            return true;
        }
    }

    protected function setSlug($table, $model, $updating = false, $render = true)
    {
        $slugcolumn = $model->getProperty('sluggable');

        if ( ! $model->getField($slugcolumn) )
        {
            $this->line('<comment>+ Unknown slug column for</comment> <error>'.$slugcolumn.'</error> <comment>column</comment>');

            return;
        }

        $column = $table->string('slug', $model->getFieldLength($slugcolumn));

        if ( $updating == true )
            $column->after( $slugcolumn );

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
        $this->buffer[ $model->getTable() ][] = function() use ($model) {
            foreach ($model->all() as $row)
            {
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
    protected function setColumn($table, $model, $key)
    {
        //Registred column types
        $types = [
            'belongsTo',
            'belongsToMany',
            'stringColumn',
            'textColumn',
            'integerColumn',
            'decimalColumn',
            'fileColumn',
            'dateColumn',
            'selectColumn',
            'checkboxColumn',
        ];

        //Get column
        foreach ($types as $column) {
            if ( $column = $this->{$column}($table, $model, $key) )
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

        //If is field required
        if( $model->hasFieldParam($key, 'default') )
        {
            $column->default( $model->getFieldParam($key, 'default') );
        } else {
            $column->default(NULL);
        }

        return $column;
    }

    /*
     * Add language_id relationship
     */
    protected function createLanguageRelationship($table, $model, $updating = false)
    {
        //If is multi languages support
        if ( ! $model->isEnabledLanguageForeign() )
            return $table;

        //Get last key of fields
        $fields = $model->getFields();

        if ( $updating == true && count($fields) > 0 )
            end($fields);

        $_table = $table->integer('language_id')->unsigned()->nullable();

        if ( $updating == true && $last = key($fields) )
        {
            $_table->after( $last );
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
        $belongsToModel = $model->getProperty('belongsToModel');

        //Model without parent
        if ( $belongsToModel == null )
            return;

        if ( !is_array($belongsToModel) )
            $belongsToModel = [ $belongsToModel ];

        if ( $updating === true )
            $belongsToModel = array_reverse($belongsToModel);

        foreach ($belongsToModel as $parent)
        {
            $parent = new $parent;

            $foreign_column = $model->getForeignColumn( $parent->getTable() );

            //Check if table has column
            if ( $updating === true && $model->getSchema()->hasColumn($model->getTable(), $foreign_column) )
                continue;

            $column = $table->integer( $foreign_column )->unsigned();

            //If parent belongs to more models...
            if ( count($belongsToModel) > 1 )
                $column->nullable();

            if ( $updating === true )
            {
                $column->after('id');
                $this->line('<comment>+ Added column:</comment> '.$foreign_column);
            }

            if ( $parent->getConnection() != $model->getConnection() )
            {
                $this->line('<comment>+ Skipped foreign relationship:</comment> '.$foreign_column . ' <comment>( different db connections )</comment> ');
                continue;
            }

            $table->foreign( $foreign_column )->references( 'id' )->on( $parent->getTable() );
        }
    }

    //Returns schema with correct connection
    protected function getSchema($model)
    {
        return Schema::connection( $model->getProperty('connection') );
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
}