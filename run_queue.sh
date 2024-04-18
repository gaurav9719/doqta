#!/bin/bash

# Change directory to your Laravel project
cd /home1/dbttebmy/public_html/doqta

# Run the queue worker continuously
while true; do
    php artisan queue:work  --tries=3
    # Add any additional options as needed
    sleep 5  # Optional: Sleep for a few seconds between each loop iteration
done
