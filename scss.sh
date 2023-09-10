#!/usr/bin/env bash

set -e
set -u

find static/css/ -iname '*.scss' -not -iname '_*' -execdir bash -c 'sassc --style=compact {} ../$(basename {} .scss).css' \;