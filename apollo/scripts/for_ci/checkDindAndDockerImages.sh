#!/bin/bash


echo "START - $( basename $0 )"
#
COUNT=1
COUNT_LIMIT=20
while [[ $( docker -v 2>&1 >/dev/null ) ]] && [[ "${COUNT}" -le "${COUNT_LIMIT}" ]]; do 
    echo " ${COUNT}/${COUNT_LIMIT} - waiting docker-in-docker starts."
    COUNT=$(( ${COUNT} + 1 ))
    sleep 1
done
if (( ${COUNT} >= ${COUNT_LIMIT} )); then
    exit 1;
fi
echo ""


#
COUNT=1
COUNT_LIMIT=50
while [[ $( docker images | grep "hyp2000" 2>&1 >/dev/null) ]] && [[ "${COUNT}" -le "${COUNT_LIMIT}" ]]; do
    echo " ${COUNT}/${COUNT_LIMIT} - waiting hyp2000 starts." 
    COUNT=$(( ${COUNT} + 1))
    sleep 1
done
if (( ${COUNT} >= ${COUNT_LIMIT} )); then
    docker images
    exit 1;
fi
echo ""

#
COUNT=1;
COUNT_LIMIT=50;
while [[ $( docker images | grep "pyml" 2>&1 >/dev/null ) ]] && [[ "${COUNT}" -le "${COUNT_LIMIT}" ]]; do
    echo " ${COUNT}/${COUNT_LIMIT} - waiting pyml starts." 
    COUNT=$(( ${COUNT} + 1))
    sleep 1
done
if (( ${COUNT} >= ${COUNT_LIMIT} )); then
    docker images
    exit 1;
fi
echo "END - $( basename $0 )"