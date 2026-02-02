#!/usr/bin/env python3
import os

print("Cache-Control: no-cache")
print("Content-Type: text/html\n")

print("<!DOCTYPE html><html><head><title>Environment Variables</title></head><body>")
print("<h1 align='center'>Environment Variables</h1><hr>")

for k in sorted(os.environ.keys()):
    v = os.environ.get(k, "")
    print(f"<b>{k}:</b> {v}<br />")

print("</body></html>")
