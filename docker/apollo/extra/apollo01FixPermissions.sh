#!/bin/bash
BASENAME=$( basename $0 )
DIRS_LIST="/app/storage/ /app/storage/data/ /app/storage/data/pyml/ /app/storage/data/hyp2000/"

echo "START - ${BASENAME} -> apollo01FixPermissions.sh"

#echo " Running: chown -R application:application /app/storage/"
#chown -R application:application /app/storage/

echo " Running: chown -R application:application /app/bootstrap/cache/"
chown -R application:application /app/bootstrap/cache/

for DIR in ${DIRS_LIST}; do
    if [ ! -d ${DIR} ]; then
        echo " Create dir: ${DIR}"
        mkdir ${DIR}
    fi
    echo " Running: chown application:application ${DIR}"
    chown application:application ${DIR}
done

echo "END - ${BASENAME} -> apollo01FixPermissions.sh"
echo ""
