#!/bin/bash

PORT="8080"
cd frontend
echo "Starting Webserver on port: $PORT"
python3 -m http.server $PORT
