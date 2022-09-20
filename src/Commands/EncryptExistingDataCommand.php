<?php

namespace Admin\Commands;

use Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/*
 * Thanks to
 * https://gist.github.com/ivanvermeyen/b72061c5d70c61e86875
 */
class EncryptExistingDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:encrypt-existing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encrypt existing data.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Running encryptor command.');

        $this->runAllModels();
    }

    public function runAllModels()
    {
        $models = Admin::getAdminModels();

        foreach ($models as $model) {
            $encryptedFields = [];

            foreach ($model->getFields() as $key => $field) {
                if ( $model->hasFieldParam($key, 'encrypted') ){
                    $encryptedFields[] = $key;
                    break;
                }
            }

            if ( count($encryptedFields) > 0 ){
                $this->processModel($model, $encryptedFields);
            }
        }
    }

    private function processModel($model, $fields)
    {
        $this->line('<info>--- Processing: '.$model->getTable().'</info>');

        $columns = array_merge([
            $model->getKeyName(),
        ], $fields);

        $rows = DB::table($model->getTable())->select($columns)->get();

        $this->line('Total: '.count($rows));

        $unecryptedRows = $this->getUnecryptedRows($model, $rows, $fields);

        $this->line('Found unecrypted rows: '.count($unecryptedRows));

        foreach ($unecryptedRows as $key => $row) {
            $entry = $model
                        ->newInstance([], true)
                        ->forceFill($row);

            $entry->save();
        }
    }

    private function getUnecryptedRows($model, $rows, $fields)
    {
        $unecryptedRows = [];

        foreach ($rows as $row) {
            $unecryptedRow = [];
            foreach ($fields as $key) {
                $value = $row->{$key};

                if ( $value && is_object(json_decode(base64_decode($value))) === false ){
                    $unecryptedRow[$key] = $value;
                }
            }

            if ( count($unecryptedRow) ){
                $unecryptedRow[$model->getKeyName()] = $row->{$model->getKeyName()};

                $unecryptedRows[] = $unecryptedRow;
            }
        }

        return $unecryptedRows;
    }
}
