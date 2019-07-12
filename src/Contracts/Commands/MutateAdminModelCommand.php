<?php

namespace Admin\Contracts\Commands;

use AdminCore;
use Symfony\Component\Console\Input\InputOption;

class MutateAdminModelCommand
{
    public function register()
    {
        /*
         * Mutate model namespaces
         */
        AdminCore::event('admin.command.model.create.namespaces', function (&$namespaces, $command) {
            $namespaces = ['use Admin\Eloquent\AdminModel', 'use Admin\Fields\Group'];
        });

        /*
         * Add model parameters into admin core model generator
         */
        AdminCore::event('admin.command.model.create.parameters', function (&$parameters, $command) {
            $parameters[] = '
            /*
             * Template name
             */
            protected $name = \''.($command->option('name') ?: last(explode('/', $command->argument('name')))).'\';';

            $parameters[] = '
            /*
             * Template title
             */
            protected $title = \''.($command->option('title') ?: '').'\';';

            if ($command->option('group')) {
                $parameters[] = '
                /*
                 * Group
                 */
                protected $group = \''.$command->option('group').'\';';
            }

            if ($command->option('single')) {
                $parameters[] = '
                /*
                 * Single row in table, automatically set minimum and maximum to 1
                 */
                protected $single = true;';
            }

            if ($command->option('localization')) {
                $parameters[] = '
                /*
                 * Enable multilanguages
                 */
                protected $localization = true;';
            }

            if ($command->option('sortable')) {
                $parameters[] = '
                /*
                 * Disabled sorting of rows
                 */
                protected $sortable = false;';
            }

            if ($command->option('publishable')) {
                $parameters[] = '
                /*
                 * Disabled publishing rows
                 */
                protected $publishable = false;';
            }

            if ($command->option('minimum')) {
                $parameters[] = '
                /*
                 * Minimum page rows
                 * Default = 0
                 */
                protected $minimum = '.$this->option('minimum').';';
            }

            if ($command->option('maximum')) {
                $parameters[] = '
                /*
                 * Maximum page rows
                 * Default = 0 = ∞
                 */
                protected $maximum = '.$this->option('maximum').';';
            }

            if ($this->isGalleryModel($command)) {
                $parameters[] = '
                /*
                 * Additional model settings
                 */
                protected $settings = [
                    '."\t".'\'increments\' => false,
                ];';
            }
        });

        AdminCore::event('admin.command.model.create.fields', function (&$fields, $command) {
            $locale = config('admin.locale', 'en');

            //Mutate name field, in example model from crudadmin we want placeholder attribute
            $fields['name'] = 'name:'.trans('admin.core::fields.name', [], $locale).'|placeholder:'.trans('admin.core::fields.placeholder', [], $locale).'|required|max:90';

            //If is gallery model, then return just one field
            if ($this->isGalleryModel($command)) {
                $fields = [
                    'image' => 'name:'.trans('admin.core::fields.image', [], $locale).'|type:file|required|image|multirows',
                ];
            }
        });

        /*
         * Add model comman d parameters into admin core model generator
         */
        AdminCore::event('admin.command.model.create.options', function (&$options, $command) {
            $options = array_merge($options, [
                ['title', 't', InputOption::VALUE_OPTIONAL, 'Model title in administration'],
                ['group', 'g', InputOption::VALUE_OPTIONAL, 'Model group in administration'],
                ['single', 's', InputOption::VALUE_NONE, 'Model with one row'],
                ['localization', 'l', InputOption::VALUE_NONE, 'Model with localization mode'],
                ['sortable', '', InputOption::VALUE_NONE, 'Model with disabled sorting of rows'],
                ['publishable', 'p', InputOption::VALUE_NONE, 'Model with disabled publishing of rows'],
                ['minimum', '', InputOption::VALUE_OPTIONAL, 'Minimum restriction of rows'],
                ['maximum', '', InputOption::VALUE_OPTIONAL, 'Maximum restriction of rows'],
            ]);
        });
    }

    /*
     * Checks if is gallery model
     */
    protected function isGalleryModel($command)
    {
        $gallery = substr($command->getNameInput(), -7);

        return $gallery == 'Gallery';
    }
}
