#!/bin/bash
# Trivikratech POS 1-Click Mac Launcher
cd "$(dirname "$0")"

PORT=8000
for p in 8000 8080 8082 8085 8888 8090 8099; do
    if ! lsof -Pi :$p -sTCP:LISTEN -t >/dev/null ; then
        PORT=$p
        break
    fi
done

echo "=========================================================="
echo "  🚀 Starting Trivikratech Jewellery POS & ERP System...   "
echo "  🌐 URL: http://localhost:$PORT                          "
echo "=========================================================="

# Open default browser automatically in 1 second
(sleep 1 && open "http://localhost:$PORT") &

# Start PHP Built-in Server
/opt/homebrew/bin/php -S 127.0.0.1:$PORT
