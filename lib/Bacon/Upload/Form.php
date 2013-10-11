<?php

/**
   Copyright 2012-2013 Brainsware

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

*/

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
            $this->log->error("Trying to overwrite existing file ({$path}), but overwriting is disabled!");

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
