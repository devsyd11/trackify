#!/bin/bash
# Simple link extractor - just strips ANSI codes and finds https://

cat sendlink | sed 's/\x1b\[[0-9;]*m//g' | grep -o 'https://[^ ]*'
