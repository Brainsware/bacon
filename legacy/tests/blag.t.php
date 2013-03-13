<?php

$test = new TestMore();

$app = new Blag();
$app->setRequestType('test');
$test->isa_ok ($app, 'Blag');

$model = new \Model\Blag();
$test->isa_ok ($model, '\Model\Blag');

$test->is ($model->content = "Some Content", "Some Content", '$model->content is Some Content');
$test->is ($model->title = "Some Title", "Some Title", '$model->title is Some Title');
$test->is ($model->save(), true, 'save() successful');
$test->is ($model->find(1)->getArrayCopy(), array( "id" => "1",
  "content" => "Some Content",
  "title" => "Some Title",
  "created" => NULL, 
  "comments_enable" => NULL ), 'find(1) succesfull' );

$test->done_testing();

?>
