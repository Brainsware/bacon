<?php

namespace Bacon\Upload;

class XHR extends \stdClass implements Handler
{
    public function __construct ($log, $input = 'php://input', $size = 0, $type, $name)
    {
		$this->log   = $log;
        $this->input = $input;
        $this->size  = $size;
        $this->type  = $type;
        $this->name  = $name;

        $this->source = false;
        $this->target = false;
        $this->temporary = false;
        $this->temporary_name = false;

        $this->prepare();
        $this->deduct_type();
    }

    public function __destruct() {
        if ($this->source)    fclose($this->source);
        if ($this->temporary) fclose($this->temporary);
        if ($this->target)    fclose($this->target);

        //unlink($this->temporary_name);
    }

    public function prepare()
    {
        $this->source = fopen($this->input, 'r');

        if ($this->source === false) {
            throw new \Exception('Could not open input stream (' . $this->input . ')');
        }

        $this->temporary_name = tempnam(UPLOAD_DIR, 'brainswear-design-');

        if (empty($this->temporary_name)) {
            throw new \Exception('Could not create proper name for temporary file - permissions? Check your warnings (open_base_dir)!');
        }

        $this->temporary = fopen($this->temporary_name, 'w');

        if ($this->temporary === false) {
            throw new \Exception('Could not create temporary file (' . $this->temporary_name . ')');
        }

        $real_size = stream_copy_to_stream($this->source, $this->temporary);

        if ($real_size != $this->size) {
            throw new \Exception('Actual size differs from client sent size.');
        }

        $this->checksum = sha1_file($this->temporary_name);
    }

    public function store ($directory, $name)
    {
        if ($this->temporary === false) {
            throw new \Exception('No temporary file present, call prepare() first!');
        }

        $path = $directory . '/' . $name;

        if ((!isset($this->overwrite_existing) || !$this->overwrite_existing) && file_exists($path)) {
            throw new \Exception('Trying to overwrite existing file (' . $path . '), but overwriting is disabled!');
        }

        if (!copy($this->temporary_name, $path)) {
            throw new \Exception('Could not copy temporary file (' . $this->temporary_name . ') to destination (' . $path . ')');
        }
    }

    public function type ()
    {
        return $this->type;
    }

    public function error ()
    {
    }

    public function size ()
    {
        return $this->size;
    }

    private function deduct_type()
    {
        $info = getimagesize($this->temporary_name);
        $this->type = $info['mime'];
    }
}

?>
