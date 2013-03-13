<?php

$test = new TestMore();

$app = new Comment();

$test->isa_ok ($app, 'Comment');
$app->setRequestType('test');

$test->done_testing();

?>
