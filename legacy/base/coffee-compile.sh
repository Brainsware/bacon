#!/bin/sh

APP_DIR=$(dirname $(readlink -f $0))/../
COFFEE_DIR=$APP_DIR/coffee
JS_DIR=$APP_DIR/htdocs/scripts

for f in "$COFFEE_DIR/*.coffee"
do
	echo
	echo "Compiling $f:"
	coffee -b -c -o $JS_DIR $f 2>&1 | while read line; do echo ">	$line"; done
done

echo
