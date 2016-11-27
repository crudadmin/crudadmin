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
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;

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
        if ( $this->getSchema($model)->hasTable( $model->getTable() ) )
        {
            $this->updateTable( $model );
            $this->runFromCache( $model->getTable() );

            return;
        }

        $this->createTable( $model );
        $this->runFromCache( $model->getTable() );
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
        $this->getSchema($model)->create( $model->getTable() , function (Blueprint $table) use ($model) {

            //Increment
            $table->increments('id');

            //Add relationships with other models
            $this->addRelationships($table, $model);
            foreach ($model->getFields() as $key => $value)
            {
                $this->setColumn( $table, $model, $key );
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

        $this->getSchema($model)->table( $model->getTable() , function (Blueprint $table) use ($model) {
            //Add relationships with other models
            $this->addRelationships($table, $model, true);

            foreach ($model->getFields() as $key => $value)
            {
                //Checks if table has column
                if ( $this->getSchema($model)->hasColumn($model->getTable(), $key) ){
                    if ( $column = $this->setColumn( $table, $model, $key ) )
                        $column->change();
                } else {
                    $column = $this->setColumn( $table, $model, $key );

                    if ( $column && $this->getSchema($model)->hasColumn($model->getTable(), $model->beforeFieldName($key)) )
                        $column->after( $model->beforeFieldName($key) );

                    if ( $column )
                        $this->line('<comment>+ Added column:</comment> '.$key);
                }
            }

            //Add multilanguage support
            if ( ! $this->getSchema($model)->hasColumn($model->getTable(), 'language_id') )
            {
                $this->createLanguageRelationship($table, $model, true);
            }

            //Order column
            if ( ! $this->getSchema($model)->hasColumn($model->getTable(), '_order') && $model->getProperty('sortable') == true )
            {
                $table->integer('_order')->unsigned();
                $this->line('<comment>+ Added column:</comment> _order');
            }

            //Published at column
            if ( ! $this->getSchema($model)->hasColumn($model->getTable(), 'published_at') && $model->getProperty('publishable') == true )
            {
                $table->timestamp('published_at')->nullable()->default( DB::raw( 'CURRENT_TIMESTAMP' ) );
                $this->line('<comment>+ Added column:</comment> published_at');
            }

            //Deleted at
            if ( ! $this->getSchema($model)->hasColumn($model->getTable(), 'deleted_at') )
            {
                $table->softDeletes();
                $this->line('<comment>+ Added column:</comment> deleted_at');
            }

            /**
             *  Automatic dropping columns
             */
            if ( $model->getProperty('skipDroppingColumn') == false )
            {
                $base_fields = $model->getBaseFields(true);

                //Removes unneeded columns
                foreach ($this->getSchema($model)->getColumnListing($model->getTable()) as $column)
                {
                    if ( ! in_array($column, $base_fields) )
                    {
                        $this->line('<comment>+ Unknown column:</comment> '.$column);

                        if ( $this->confirm('Do you want drop this column? [y|N]') )
                        {
                            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                            $table->dropColumn($column);

                            $this->line('<comment>+ Dropped column:</comment> '.$column);
                        }
                    }
                }
            }

        });

    }

    protected function stringColumn($table, $model, $key)
    {
        if ( $model->isFieldType($key, ['string', 'file', 'password']) )
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

            if ( $this->getSchema($model)->hasTable( $model->getTable() ) )
            {
                $keyExists = count($model->getConnection()->select(
                    DB::raw(
                        'SHOW KEYS
                        FROM '.$model->getTable().'
                        WHERE Key_name=\''.$model->getTable().'_'.$key.'_foreign\''
                    )
                ));
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
                if ( ! $this->getSchema($model)->hasTable( $properties[3] ) )
                {
                    //Create pivot table
                    $this->getSchema($model)->create( $properties[3] , function (Blueprint $table) use ( $model, $properties ) {
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
            'dateColumn',
            'selectColumn',
            'checkboxColumn',
        ];

        //Get column
        foreach ($types as $column) {
            if ( $column = $this->{$column}($table, $model, $key) )
                break;
        }

        if ( !$column || $column === true )
            return;


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

        $parent = new $belongsToModel;

        $foreign_column = $model->getForeignColumn();

        //Check if table has column
        if ( $updating === true && $this->getSchema($model)->hasColumn($model->getTable(), $foreign_column) )
            return;

        $row = $table->integer( $foreign_column )->unsigned();

        if ( $updating === true )
        {
            $row->after('id');
            $this->line('<comment>+ Added column:</comment> '.$foreign_column);
        }

        if ( $parent->getConnection() != $model->getConnection() )
        {
            return $this->line('<comment>+ Skipped foreign relationship:</comment> '.$foreign_column . ' <comment>( different db connections )</comment> ');
        }

        $table->foreign( $foreign_column )->references( 'id' )->on( $parent->getTable() );
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