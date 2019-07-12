<?php

namespace Admin\Commands;

use Admin;
use ImageCompressor;
use Admin\Helpers\File;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class AdminCompressUploadsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'admin:compress';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compress all data in uploads folder';

    protected $files;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->files = new Filesystem;

        parent::__construct();
    }

    /*
     * Get uploads path
     */
    private function getUploadsPath()
    {
        return public_path('uploads');
    }

    /*
     * Check if image is allowed for compression
     */
    private function canSkipFile($path)
    {
        $extension = explode('.', $path);
        $extension = end($extension);

        return ! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg']);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        //Get all files from all directories
        $files = $this->files->allFiles($this->getUploadsPath());

        //Get already compressed files and their original filesizes
        $compressed = ImageCompressor::getCompressedFiles();

        //List of compressed filesizes
        $compressed_filesizes = [];

        $total_size = 0;

        //Compress all uncompressed paths
        foreach ($files as $path) {
            //Get filesize of original/compressed file
            $total_size += $filesize = ImageCompressor::getFilesize($path);

            //Skipping other files than images
            if ($this->canSkipFile($path)) {
                continue;
            }

            $relative_path = $path->getRelativePathName();

            //Compress image if is not compressed already
            if (! array_key_exists($relative_path, $compressed)) {
                $compressed[$relative_path] = $filesize;

                ImageCompressor::tryShellCompression($path);
            }

            //Get filesize after compression
            $compressed_filesizes[$relative_path] = ImageCompressor::getFilesize($path);
        }

        //Calculate filesize differents
        $original_size = 0;
        $compressed_size = 0;
        foreach ($compressed as $path => $size) {
            if (! array_key_exists($path, $compressed_filesizes)) {
                continue;
            }

            $original_size += $size;
            $compressed_size += $compressed_filesizes[$path];
        }

        $this->line(
            '<info>Total size of all uploaded resources is <comment>'.File::formatFilesizeNumber($total_size * 1024).'</comment>'."\n".
            'Original size of image resource was</info> <comment>'.File::formatFilesizeNumber($original_size * 1024).'</comment>'."\n".
            '<info>Compressed size of all resources is </info><comment>'.File::formatFilesizeNumber($compressed_size * 1024).'</comment><info>, reduction </info><comment>'.File::formatFilesizeNumber(round($original_size - $compressed_size) * 1024, 2).' ('.round(100 - (100 / $original_size * $compressed_size), 2).'%).</comment>'
        );
    }
}
