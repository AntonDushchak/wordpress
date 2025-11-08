#!/bin/bash

WORDPRESS_URL="http://localhost:8080/wp-cron.php"

curl -s "$WORDPRESS_URL" > /dev/null 2>&1
