<?php

namespace Bacon\Upload;

class Form extends \stdClass implements Handler
{
    public function __construct ($log, $input, $size = 0, $type, $original_name)
    {
		$this->log   = $log;
        $this->input = $input;
        $this->size  = $size;
        $this->type  = $type;
        $this->name  = $original_name;

        $this->checksum = sha1_file($this->input);
    }

    public function store ($directory, $name)
    {
        $path = $directory . '/' . $name;

        if ((!isset($this->overwrite_existing) || !$this->overwrite_existing) && file_exists($path)) {
            $this->log->error('Trying to overwrite existing file (' . $path . '), but overwriting is disabled!');

            throw new \Exception('You already uploaded this image!');
        }

        return move_uploaded_file($this->input, $path);
    }

    public function type ()
    {
        return $this->type;
    }

    public function size ()
    {
        return $this->size;
    }

    public function error ()
    {
    }
}
