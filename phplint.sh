#!/usr/bin/env bash

ROOTDIR="$(dirname "$(dirname "$0")")"
echo $ROOTDIR;
$ROOTDIR/vendor/bin/parallel-lint --no-colors --short --exclude ./vendor "$@"
