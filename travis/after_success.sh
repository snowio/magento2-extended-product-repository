#!/usr/bin/env bash

if [ "$CODE_COVERAGE" = 1 ]; then bash <(curl -s https://codecov.io/bash); fi
