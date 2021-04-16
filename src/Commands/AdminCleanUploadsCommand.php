<?php

namespace Admin\Commands;

use Admin;
use Admin\Core\Helpers\File as AdminFile;
use File;
use Illuminate\Console\Command;

class AdminCleanUploadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete uneccessary files from uploads folder.';

    private $stats = [];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->performStats();

        $this->showDeepStats();
    }

    private function performStats()
    {
        $this->error('******** Calculating... ********');

        foreach (Admin::getAdminModels() as $model) {
            $this->buildModelStats($model);
        }

        $this->sortStatsBySize();

        foreach ($this->stats as $table => $data) {
            $this->showModelStats($table);
        }
    }

    private function askForDeleting($table)
    {
        $model = Admin::getModelByTable($table);
        $fileFields = $this->getModelFileFields($model);

        $field = $this->choice('Which field do you want to perform operations?', array_merge(['all'], array_keys($fileFields)), 0);

        $operation = $this->choice('What do you want to delete from this field?', [
            'nothing' => 'Nothing',
            'missing' => 'Files which are not present in database anymore',
            'trashed' => 'Files which are saved in trashed rows',
            'both' => 'Missing and trashed',
        ], 'nothing');

        if ( $operation == 'nothing' ){
            return;
        }

        $this->line('Removing...');

        $removed = 0;

        foreach ($fileFields as $key => $option) {
            //Skip all uneccessary fields
            if ( $field != 'all' && $key != $field  ){
                continue;
            }

            //Remove missing files. (Does not exists in db)
            if ( in_array($operation, ['missing', 'both']) ){
                foreach (@$this->stats[$table]['uneccessaryFiles'][$key] ?: [] as $path) {
                    @unlink($path);
                    $removed++;
                }
            }

            //Remove files with trashed rows in db
            if ( in_array($operation, ['trashed', 'both']) ){
                foreach (@$this->stats[$table]['removedFiles'][$key] ?: [] as $path) {
                    @unlink($path);
                    $removed++;
                }
            }

        }

        $this->line($removed.' files has been removed');
    }

    private function showModelStats($table, $full = false)
    {
        $data = $this->stats[$table];

        $model = Admin::getModelByTable($table);

        $info = [
            'Total' => AdminFile::formatFilesizeNumber(array_sum($data['totalBytes'])),
            'Missing in DB' => AdminFile::formatFilesizeNumber(array_sum($data['uneccessaryBytes'])),
            'Trashed rows' => AdminFile::formatFilesizeNumber(array_sum($data['removedBytes'])),
        ];

        $this->line('<info>'.$table.' ('.$model->getProperty('name').')</info>'.(array_sum($info) == 0 ? ' is empty' : ''));

        //If model has no files
        if ( array_sum($info) == 0 ){
            return;
        }

        foreach ($info as $key => $value) {
            $this->line($key.': <comment>'.$value.'</comment>');
        }

        //We want full response
        if ( $full == true ){
            $fileFields = $this->getModelFileFields($model);

            foreach ($fileFields as $key => $options) {
                $this->line('<info>+ '.$key.'</info>');
                $this->line('  Total: <comment>'.AdminFile::formatFilesizeNumber($data['totalBytes'][$key]).'</comment>');
                $this->line('  Missing in DB: <comment>'.AdminFile::formatFilesizeNumber($data['uneccessaryBytes'][$key]).'</comment>');
                $this->line('  Trashed rows: <comment>'.AdminFile::formatFilesizeNumber($data['removedBytes'][$key]).'</comment>');
            }
        }

        $this->line('');
    }

    private function showDeepStats()
    {
        $table = $this->choice('Which model do you want perform operations?', array_keys($this->stats));

        $this->showModelStats($table, true);

        $this->askForDeleting($table);
    }

    private function sortStatsBySize()
    {
        uasort($this->stats, function($a, $b){
            return array_sum(@$b['totalBytes'] ?: []) - array_sum(@$a['totalBytes'] ?: []);
        });
    }

    private function getModelFileFields($model)
    {
        return array_filter($model->getFields(), function($field){
            return @$field['type'] == 'file';
        });
    }

    private function buildStatsTree($table, $fileFields)
    {
        $defaultNullArray = array_combine(array_keys($fileFields), array_fill(0, count($fileFields), 0));
        $defaultEmptyArray = array_combine(array_keys($fileFields), array_fill(0, count($fileFields), []));

        $this->stats[$table] = [
            'totalBytes' => $defaultNullArray,
            'totalFilesCount' => $defaultNullArray,
            'uneccessaryBytes' => $defaultNullArray,
            'uneccessaryFiles' => $defaultEmptyArray,
            'removedBytes' => $defaultNullArray,
            'removedFiles' => $defaultEmptyArray,
        ];
    }

    private function getExistingRows($model, $fileFields)
    {
        return $model->withoutGlobalScopes()->when($model->hasSoftDeletes(), function($query){
            $query->withoutTrashed();
        })->select(
            array_merge(['id'], array_keys($fileFields))
        )->get();
    }

    private function getTrashedRows($model, $fileFields)
    {
        return $model->withoutGlobalScopes()->when($model->hasSoftDeletes(), function($query){
            $query->onlyTrashed();
        })->select(
            array_merge(['id'], array_keys($fileFields))
        )->get();
    }

    public function buildModelStats($model)
    {
        $table = $model->getTable();

        $fileFields = $this->getModelFileFields($model);

        $this->buildStatsTree($table, $fileFields);

        $existingRows = $this->getExistingRows($model, $fileFields);

        $trashedRows = $this->getTrashedRows($model, $fileFields);

        //Calculate all data
        foreach ($fileFields as $key => $options) {
            $path = $model->filePath($key);

            $existingFiles = $existingRows->pluck($key)->filter()->toArray();
            $removedFiles = $trashedRows->pluck($key)->filter()->toArray();

            //If directory does not exists
            if ( !file_exists($path) ){
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                $this->stats[$table]['totalBytes'][$key] += $file->getSize();
                $this->stats[$table]['totalFilesCount'][$key]++;

                //Files which are in deleted rows
                //but are not also in existing rows
                if (
                    in_array($file->getFileName(), $removedFiles) === true
                    && in_array($file->getFileName(), $existingFiles) === false
                ) {
                    $this->stats[$table]['removedBytes'][$key] += $file->getSize();
                    $this->stats[$table]['removedFiles'][$key][] = $file->getPathname();
                }

                //Files which does not exists in existing database rows
                else if ( in_array($file->getFileName(), $existingFiles) === false ) {
                    $this->stats[$table]['uneccessaryBytes'][$key] += $file->getSize();
                    $this->stats[$table]['uneccessaryFiles'][$key][] = $file->getPathname();
                }
            }
        }
    }
}
