<?php

$test = new TestMore();
$app = new Application();

$test->isa_ok ($app, 'Application');
$app->setRequestType('test');
$test->is($app->gravatar('i.galic@brainsware.org'),
	'<img src="http://www.gravatar.com/avatar/eeafaae6e61a7193e0134e3b39c3ba34" alt="Gravatar for i.galic@brainsware.org" />',
	'Gravatar generation');

$test->done_testing();

?>
