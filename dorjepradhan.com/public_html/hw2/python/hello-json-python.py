#!/usr/bin/env python3
import os, jspon, datetime

print("Cache-Control: no-cache")
print("Content-Type: application/json\n")

ip = os.environ.get("REMOTE_ADDR", "")
now = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")

message = {
    "title": "Hello, Python!",
    "heading": "Hello, Python!",
    "message": "Hello from Dorje Pradhan in Python",
    "time": now,
    "ip": ip
}

print(json.dumps(message))