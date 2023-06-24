#!/bin/bash
BASENAME=$( basename $0 )

echo "START - ${BASENAME} -> apollo02PhpArtisanMigrate.sh"
echo " Running: php artisan migrate:fresh"
cd /app
while ! php artisan migrate:fresh ; do 
    echo "Waiting for database connection..."
    sleep 2
done
echo "END - ${BASENAME} -> apollo02PhpArtisanMigrate.sh"
echo ""
