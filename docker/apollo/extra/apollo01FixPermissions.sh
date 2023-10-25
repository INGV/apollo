#!/bin/bash
BASENAME=$( basename $0 )

echo "START - ${BASENAME} -> apollo01FixPermissions.sh"

#echo " Running: chown -R application:application /app/storage/"
#chown -R application:application /app/storage/

echo " Running: chown application:application /app/storage/"
chown application:application /app/storage/

echo " Running: chown application:application /app/storage/data/"
chown application:application /app/storage/data/

echo " Running: chown application:application /app/storage/data/pyml/"
chown application:application /app/storage/data/pyml

echo " Running: chown application:application /app/storage/data/hyp2000/"
chown application:application /app/storage/data/hyp2000

echo " Running: chown -R application:application /app/bootstrap/cache/"
chown -R application:application /app/bootstrap/cache/

echo "END - ${BASENAME} -> apollo01FixPermissions.sh"
echo ""
