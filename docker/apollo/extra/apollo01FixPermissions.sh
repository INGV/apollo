#!/bin/bash
BASENAME=$( basename $0 )

echo "START - ${BASENAME} -> apollo01FixPermissions.sh"
echo " Running: chown -R application:application /app/storage/"
#chown -R application:application /app
chown -R application:application /app/storage/
echo " Running: chown -R application:application /app/bootstrap/cache/"
chown -R application:application /app/bootstrap/cache/
echo "END - ${BASENAME} -> apollo01FixPermissions.sh"
echo ""
