#!/bin/bash
cd /var/www/vhosts/beta.bauteileditor.local/httpdocs/src/elca
php scripts/runner.php Elca pdf-queue-gen.php _script.local
