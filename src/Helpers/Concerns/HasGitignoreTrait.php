<?php

namespace Admin\Helpers\Concerns;

use Admin\Core\Helpers\Storage\AdminFile;
trait HasGitignoreTrait
{
    public function addGitignoreFiles($directories = null)
    {
        $directories = $directories ?: [
            public_path(self::getAdminAssetsPath()),
        ];

        $directories = $this->getCastedGitignorePaths($directories);

        $this->createGitignoreDirectoriesAndFiles($directories);

        $this->addPathsIntoMainGitignoreFile($directories);
    }

    private function getCastedGitignorePaths($directories)
    {
        // Cast paths
        foreach ($directories as $path => $createGitIgnore) {
            if ( !is_string($path) || is_bool($createGitIgnore) === false ){
                unset($directories[$path]);

                $directories[$createGitIgnore] = true;
            }
        }

        return $directories;
    }

    private function createGitignoreDirectoriesAndFiles($directories)
    {
        $gitignore = "*\n!.gitignore";

        // Skip creating gitignore files for directories that are not created
        foreach ($directories as $path => $createGitIgnore) {
            if ( $createGitIgnore === false ){
                continue;
            }

            if ( file_exists($path.'/.gitignore') ) {
                continue;
            }

            AdminFile::makeDirs($path);

            file_put_contents($path.'/.gitignore', $gitignore);
        }
    }

    private function addPathsIntoMainGitignoreFile($directories)
    {
        // Add CrudAdmin paths to main .gitignore file
        $rootGitIgnorePath = base_path('.gitignore');
        $rootGitIgnoreData = file_get_contents($rootGitIgnorePath);
        $prefix = '# CrudAdmin';

        $directoriesWithoutBasePath = array_map(function ($dir) {
            return str_replace(base_path(), '', $dir);
        }, array_keys($directories));

        $directoriesWithoutBasePath = array_filter($directoriesWithoutBasePath, function ($dir) use ($rootGitIgnoreData) {
            return strpos($rootGitIgnoreData, $dir) === false;
        });

        if ( count($directoriesWithoutBasePath) > 0 ) {
            if ( strpos($rootGitIgnoreData, $prefix) === false ) {
                file_put_contents($rootGitIgnorePath, "\n".$prefix."\n", FILE_APPEND);
            }

            foreach ($directoriesWithoutBasePath as $dir) {
                file_put_contents($rootGitIgnorePath, $dir."\n", FILE_APPEND);
            }
        }
    }
}