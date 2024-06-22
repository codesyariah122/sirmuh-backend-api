#!/bin/bash
exec > /var/log/sirmuh-api-start.log 2>&1
set -x
php -S 192.168.1.10:4041 -t public