<?php

namespace Bacon\Upload;

interface Handler
{
    public function store ($directory, $name);
    public function size ();
    public function type ();
    public function error();
}

?>
