#!/usr/bin/env python3
import os, sys, json, datetime, urllib.parse, socket

def read_body():
    length = os.environ.get("CONTENT_LENGTH")
    try:
        n = int(length) if length else 0
    except ValueError:
        n = 0
    return sys.stdin.read(n) if n > 0 else ""

def parse_urlencoded(s):
    return {k: v[0] if len(v)==1 else v for k, v in urllib.parse.parse_qs(s, keep_blank_values=True).items()}

method = os.environ.get("REQUEST_METHOD", "")
query = os.environ.get("QUERY_STRING", "")
content_type = os.environ.get("CONTENT_TYPE", "")
ua = os.environ.get("HTTP_USER_AGENT", "")
ip = os.environ.get("REMOTE_ADDR", "")
host = os.environ.get("HTTP_HOST", socket.gethostname())
now = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")

body = read_body()
parsed = {}

# GET data comes from query string; body for POST/PUT/DELETE
if method == "GET":
    parsed = parse_urlencoded(query) if query else {}
else:
    if "application/json" in content_type:
        try:
            parsed = json.loads(body) if body else {}
        except json.JSONDecodeError:
            parsed = {"_json_error": "invalid JSON"}
    else:
        parsed = parse_urlencoded(body) if body else {}

print("Cache-Control: no-cache")
print("Content-Type: text/html\n")

print("<!DOCTYPE html><html><head><title>Echo (Python)</title></head><body>")
print("<h1 align='center'>Echo (Python)</h1><hr>")
print(f"<p><b>Hostname:</b> {host}</p>")
print(f"<p><b>Time:</b> {now}</p>")
print(f"<p><b>IP:</b> {ip}</p>")
print(f"<p><b>User-Agent:</b> {ua}</p>")
print(f"<p><b>Method:</b> {method}</p>")
print(f"<p><b>Content-Type:</b> {content_type}</p>")
print(f"<p><b>Query String:</b> {query}</p>")
print(f"<p><b>Raw Body:</b> {body}</p>")

print("<p><b>Parsed Data:</b></p><ul>")
for k in sorted(parsed.keys()):
    print(f"<li>{k} = {parsed[k]}</li>")
print("</ul>")

print("</body></html>")
