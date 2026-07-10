#!/usr/bin/env bash
set -e
cd "$(dirname "$0")/web_demo"
python3 -m http.server 8088 &
SERVER_PID=$!
trap 'kill $SERVER_PID 2>/dev/null || true' EXIT
if command -v xdg-open >/dev/null; then xdg-open http://127.0.0.1:8088 >/dev/null 2>&1 || true; fi
wait $SERVER_PID
