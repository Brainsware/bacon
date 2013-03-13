#!/bin/bash

DIR="$( cd -P "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

cd $DIR/../lib
./phrocco/bin/phrocco -i ../bacon -o ../docs/bacon
