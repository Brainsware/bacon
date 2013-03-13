<?php

/**
 * @package System
 */

namespace Bacon;

/**
 * This is a collection of useful functions.
 *
 * @package System
 */
class Util
{
    public static function truncate($text, $limit = 196, $padding = '&hellip;')
    {
        $limit -= 1;

        if (strlen($text) < $limit) {
            return $text;
        }

        $truncated_text = substr($text, 0, $limit);
        $truncated_text = substr($truncated_text, 0, strrpos($text, ' '));
        $truncated_text .= $padding;

        return $truncated_text;
    }

    /**
     * Modifies a string to remove all non ASCII characters and spaces.
     */
    public static function slugify ($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        if (function_exists('iconv'))
        {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text))
        {
            return 'n-a';
        }

        return $text;
    }

    /**
     * Validates an email (covers the most common and some uncommon cases - not RFC compliant.)
     *
     * @param string $email Email address
     *
     * @return bool
     */
    public static function validateEmail ($email)
    {
        if (!eregi("^[_a-z0-9-]+(.[_a-z0-9-]+)*@[a-z0-9-]+(.[a-z0-9-]+)*(.[a-z]{2,6})$", $email)) {
            return false;
        }

        return true;
    }

	/**
	 * Merge second array into first, but only for keys present in first array
	 */
    public static function array_merge_recursive_distinct (array $array1, array $array2)
    {
        $result = $array1;

        foreach ($array2 as $key => $val) {
            if (is_array($array2[$key])) {
                $result[$key] = is_array($result[$key]) ? self::array_merge_recursive_distinct($result[$key], $array2[$key]) : $array2[$key];
            } else {
                $result[$key] = $val;
            }
        }

        return $result;
    }

    /**
     * @param $password string
     * @param $hash SHA (default), SSHA, MD5
     *
     * @return string Seeded (salted) SHA-1 password
     */
    private static function passgen ($password,	$hash = 'SHA')
    {
        switch ($hash) {
			case 'SSHA':
				$salt = pack("CCCCCCCC", mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand());
				return "{SSHA}" . base64_encode(pack("H*", sha1($password . $salt)) . $salt);

			case 'SHA':
			default:
				return '{SHA}' . base64_encode(pack('H*', sha1($pass)));
        }
    }

    /* Thanks to Matt Jones
     * http://www.mdj.us/web-development/php-programming/another-variation-on-the-time-ago-php-function-use-mysqls-datetime-field-type/
     */
    // DISPLAYS COMMENT POST TIME AS "1 year, 1 week ago" or "5 minutes, 7 seconds ago", etc...
    public static function time_ago($date, $granularity = 1) {
        $periods = array(
            'decade' => 315360000,
            'year'   => 31536000,
            'month'  => 2628000,
            'week'   => 604800,
            'day'    => 86400,
            'hour'   => 3600,
            'minute' => 60,
            'second' => 1
        );

        $date = strtotime($date);
        $difference = time() - $date;
        $retval = '';

        if ($difference < 30) { // less than 30 seconds ago, let's say "just now"
            $retval = 'just now';

            return $retval;
        } else {
            foreach ($periods as $key => $value) {
                if ($difference >= $value) {
                    $time = floor($difference / $value);
                    $difference %= $value;
                    $retval .= ($retval ? ' ' : '') . $time.' ';
                    $retval .= (($time > 1) ? $key.'s' : $key);
                    $granularity--;
                }

                if ($granularity == '0') { break; }
            }

            return ' ' . $retval . ' ago';
        }
    }
}

?>
