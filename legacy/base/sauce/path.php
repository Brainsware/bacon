<?php

/**
   Copyright 2012 Brainsware

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

namespace Sauce;

class Path
{
	const delimiter = '/';

	/**
	 * TODO: document this functions parameters, what this does and how to use it.
	 */
	public static function join ()
	{
		$args = func_get_args();

		if (empty($args)) { return ''; }

		$paths = new Vector();

		foreach ($args as $arg) {
			if (is_string($arg)) {
				$arg = explode(self::delimiter, $arg);
			}

			$paths->push($arg);
		}

		$paths = $paths->select(function ($path) {
			$str = strval($path);

			return !empty($str);
		});

		$joined_path = $paths->join(self::delimiter);

		if ($args[0][0] === self::delimiter) {
			$joined_path = self::delimiter . $joined_path;
		}

		return $joined_path;
	}

    /**
     * Checks a file or directory existence and readability
     *
     * @param string $path /path/to/dir/or/file
     * @param string $fd Either 'f' for file or 'd' for directory.
     * @param string $rw Either 'w' for a check on writability or 'r' for a check on readability.
     *
     * @return bool
     */
    public static function check ($path, $fd, $rw)
    {
        if ($fd == 'f') {
            if (is_file($path) != true) {
                return false;
            }
        } elseif ($fd == 'd') {
            if (is_dir($path) != true) {
                return false;
            }
        } else {
            return false;
        }

        if ($rw == 'r') {
            if (!is_readable($path)) {
                return false;
            }
        } elseif ($rw == 'w') {
            if (!is_writable($path)) {
                return false;
            }
        } elseif ($rw == 'rw') {
            if (!is_readable($path) || !is_writable($path)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Empties a directory. Buh.
     *
     * @param string $dir Directory.
     * @return bool
     */
    public static function truncate_directory ($dir)
    {
        if (substr($dir, strlen($dir)-1, 1) != '/') {
            $dir .= '/';
        }

        if (!$fileList = scandir ($dir)) {
            return false;
        }

        foreach ($fileList as $fileInList) {
            if (is_dir($dir . $fileInList) && $fileInList != '.' && $fileInList != '..') {
                // Some more directory. Recurse!
                if (!self::rmrf($dir . $fileInList . '/')) {
                    // If false, Error message was already given by rmrf.
                    return false;
                }
            } elseif (is_file($dir . $fileInList)) {
                if (!unlink ($dir . $fileInList)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Completely deletes a directory and all its subdirectories.
     *
     * @param string $dir Directory. Nothing else.
     * @return bool
     */
    public static function rmrf ($dir)
    {
        self::truncate_directory($dir);

        return @rmdir($dir);
    }
}

?>
