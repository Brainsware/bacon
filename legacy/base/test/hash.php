<?php

$nothing = new Hash();
dump($nothing, "new Hash()");

$string = new Hash('string');
dump($string, 'new Hash(\'string\')');

$array = new Hash(array('a', 'b', 'c', 1, 2, 3));
dump($array, "new Hash(array('a', 'b', 'c', 1, 2, 3))");

$hash = new Hash(array('a' => 1, 'b' => 2));
dump($hash, "new Hash(array('a' => 1, 'b' => 2))");

$hash_appended = new Hash(array('a' => 1, 'b' => 2));
$hash_appended = $hash_appended->append(true);
dump($hash_appended, "new Hash(array('a' => 1, 'b' => 2)) ->append(true)");

$hash_excluded = $hash->exclude('a');
dump($hash_excluded, "new Hash(array('a' => 1, 'b' => 2)) ->exclude('a')");

$hash_mapped = $hash->map(function ($key, $value) { return $value; });
dump($hash_mapped, "new Hash(array('a' => 1, 'b' => 2)) ->map(function (\$key, \$value) { return \$value; })");

$hash_keys = $hash->keys();
dump($hash_keys, "new Hash(array('a' => 1, 'b' => 2)) ->keys()");

$hash_values = $hash->values();
dump($hash_values, "new Hash(array('a' => 1, 'b' => 2)) ->values()");

$hash_has_key = $hash->has_key('a');
dump($hash_has_key, "new Hash(array('a' => 1, 'b' => 2)) ->has_key('a')");

$hash_has_key_not = $hash->has_key('z');
dump($hash_has_key_not, "new Hash(array('a' => 1, 'b' => 2)) ->has_key('z')");

$hash_has_value = $hash->has_value(1);
dump($hash_has_value, "new Hash(array('a' => 1, 'b' => 2)) ->has_value(1)");

$hash_has_value_not = $hash->has_value(10);
dump($hash_has_value_not, "new Hash(array('a' => 1, 'b' => 2)) ->has_value(10)");


?>
