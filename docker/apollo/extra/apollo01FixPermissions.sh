#!/bin/bash
BASENAME=$( basename $0 )

echo "START - ${BASENAME} -> apolloFixPermissions.sh"
echo " Running: chown -R application:application /app"
chown -R application:application /app
echo "END - ${BASENAME} -> apolloFixPermissions.sh"
echo ""
