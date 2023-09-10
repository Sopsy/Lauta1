#!/bin/bash

# This script is used for converting SVG logos to PNG with Inkscape on Linux or Bash for Windows
# Note that inkscape command has to be in path
for file in $PWD/static/img/logo/light/*.svg; do
    filename=$(basename "$file" .svg)
    inkscape -e $PWD/static/img/logo/light/$filename.png -h 60 $file &
done
for file in $PWD/static/img/logo/dark/*.svg; do
    filename=$(basename "$file" .svg)
    inkscape -e $PWD/static/img/logo/dark/$filename.png -h 60 $file &
done