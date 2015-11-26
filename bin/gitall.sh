#!/usr/bin/env bash

echo -e "\n$(tput setaf 4)--- FRAMEWORK-BUNDLE ---$(tput sgr0)"
path="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
git $@

echo -e "\n$(tput setaf 4)--- CORE-BUNDLE ---$(tput sgr0)"
cd ${path}/../vendor/smartbox/core-bundle && git $@
