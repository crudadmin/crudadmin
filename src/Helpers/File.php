<?php
namespace Gogol\Admin\Helpers;

class File {

    /*
     * Filename
     */
    public $filename;

    /*
     * File extension type
     */
    public $extension;

    /*
     * Source to file
     */
    public $source;

    /*
     * Absolute path to file
     */
    public $path;

    /*
     * Field name
     */
    protected $field;

    /*
     * Directory in uploads
     */
    protected $directory;

    public function __construct( $filename, $field, $directory, $subdirectory = null )
    {
        $this->filename = $filename;

        $this->field = $field;

        $this->directory = $directory;

        $this->extension = $this->getExtension($filename);

        $this->source = 'uploads/' . $directory . '/' . $field . '/' . ( $subdirectory ? $subdirectory . '/' : '' ) . $filename;

        $this->path = url( $this->source );
    }

    /**
     * Format the instance as a string using the set format
     *
     * @return string
     */
    public function __toString()
    {
        return $this->source;
    }

    public function __get($key)
    {
        //When is file type svg, then image postprocessing subdirectories not exists
        if ( $this->extension == 'svg' )
            return $this;

        return new static( $this->filename, $this->field, $this->directory, $key );
    }

    protected function getExtension($filename)
    {
        $extension = explode('.', $filename);

        return last($extension);
    }
}

?>