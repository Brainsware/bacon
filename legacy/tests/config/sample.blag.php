<?php

namespace Config;

class Blag
{

	public static $enable_comments = true;
	public static $enable_recaptcha = true;
	# https://www.google.com/recaptcha/admin/create
	# These keys were generated for http://localhost
	public static $recaptcha_public_key = '6LcxHsYSAAAAAMrpXHIpFYgwroT9H9PJsdEflwtJ';
	public static $recaptcha_private_key = '6LcxHsYSAAAAAHEIdJGKhEQUzDTe2vhETgvQkjUT';
}

?>
