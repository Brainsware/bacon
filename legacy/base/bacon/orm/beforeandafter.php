<?php

namespace Bacon\ORM;

trait BeforeAndAfter
{
	protected function before_save () {}
	protected function before_create () {}
	protected function before_update () {}
	protected function before_delete () {}

	protected function after_save () {}
	protected function after_create () {}
	protected function after_update () {}
	protected function after_delete () {}

	protected function validate () {}
}
