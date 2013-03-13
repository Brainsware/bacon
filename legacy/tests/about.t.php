<?php

$test = new TestMore();

$app = new About();

$test->isa_ok ($app, 'About');
$app->setRequestType('test');

$test->done_testing();

?>
