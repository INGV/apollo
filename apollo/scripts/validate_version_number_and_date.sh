#!/bin/bash

# Go to base dir
cd $( git rev-parse --show-toplevel )

#
echo "Validate date version:"
DATE_VERSION_PUBLICCODE=$( grep "releaseDate:" publiccode.yml | awk '{print $2}' )
DATE_VERSION_HISTORY=$( grep "* Release" HISTORY | head -n 1 | awk -F'(' '{print $2}' | awk -F')' '{print $1}' )
echo " DATE_VERSION_PUBLICCODE=${DATE_VERSION_PUBLICCODE}" ;
echo " DATE_VERSION_HISTORY=${DATE_VERSION_HISTORY}"
if [[ "${DATE_VERSION_PUBLICCODE}" == "${DATE_VERSION_HISTORY}" ]]; then 
	echo "  ok"
else 
	echo "  error!"
	exit 1 
fi
echo "Done"
echo ""

echo "Validate version number:"
VERSION_PUBLICCODE=$( grep "softwareVersion:" publiccode.yml | awk '{print $2}' )
VERSION_SWAGGER=$( grep "version:" public/api/0.0.2/openapi.yaml | awk '{print $2}' | grep '^[0-9]' )
VERSION_HISTORY=$( grep "* Release" HISTORY | head -n 1 | sed -e "s/* Release//" | awk '{print $1}' )
VERSION_VERSION=$( cat VERSION )
echo " VERSION_PUBLICCODE=${VERSION_PUBLICCODE}"
echo " VERSION_HISTORY=${VERSION_HISTORY}"
echo " VERSION_SWAGGER=${VERSION_SWAGGER}"
echo " VERSION_VERSION=${VERSION_VERSION}"
if [[ "${VERSION_PUBLICCODE}" == "${VERSION_HISTORY}" ]] && [[ "${VERSION_HISTORY}" == "${VERSION_SWAGGER}" ]] && [[ "${VERSION_SWAGGER}" == "${VERSION_VERSION}" ]]; then
	echo "  ok"
else
	echo "  error!"
	exit 1
fi
echo "Done"
echo ""
