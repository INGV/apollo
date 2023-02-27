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
echo "--A--"
docker images
echo ${?}
echo "--B--"
docker images | grep "hyp2000"
echo ${?}
echo "--C--"
while ! $( docker images | grep "hyp20000" 2>&1 >/dev/null ) && (( ${COUNT} <= ${COUNT_LIMIT} )); do
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
while ! $( docker images | grep "hyp20000" 2>&1 >/dev/null ) && (( ${COUNT} <= ${COUNT_LIMIT} )); do
    echo " ${COUNT}/${COUNT_LIMIT} - waiting pyml starts." 
    COUNT=$(( ${COUNT} + 1))
    sleep 1
done
if (( ${COUNT} >= ${COUNT_LIMIT} )); then
    docker images
    exit 1;
fi
echo "END - $( basename $0 )"