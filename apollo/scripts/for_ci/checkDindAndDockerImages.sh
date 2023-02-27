#!/bin/bash


echo "START - $( basename $0 )"
COUNT=1
COUNT_LIMIT=20
while [[ $( docker -v 2>&1 >/dev/null ) ]] && [[ "${COUNT}" -le "${COUNT_LIMIT}" ]]; do 
    echo " ${COUNT}/${COUNT_LIMIT} - waiting docker-in-docker starts."
    COUNT=$(( ${COUNT} + 1 ))
    sleep 1
done
if [[ ! $( docker -v 2>&1 >/dev/null ) ]]; then
    echo "A"
    exit 1
else
    echo "B"
    exit 1
fi
echo "END - $( basename $0 )"