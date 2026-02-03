#!/usr/bin/env python3
import os, json

COOKIE_NAME = "STATEID"
STORE_DIR = "/tmp"
LINK_BASE = "/hw2/python"

def parse_cookies():
    raw = os.environ.get("HTTP_COOKIE", "")
    cookies = {}
    for p in raw.split(";"):
        p = p.strip()
        if "=" in p:
            k, v = p.split("=", 1)
            cookies[k.strip()] = v.strip()
    return cookies

def store_path(sid):
    return os.path.join(STORE_DIR, f"state_{sid}.json")

cookies = parse_cookies()
sid = cookies.get(COOKIE_NAME, "")
state = {}

if sid and sid.isalnum():
    try:
        with open(store_path(sid), "r", encoding="utf-8") as f:
            state = json.load(f)
    except Exception:
        state = {}

print("Cache-Control: no-cache")
print("Content-Type: text/html\n")

print("<!DOCTYPE html><html><head><title>State View (Python)</title></head><body>")
print("<h1 align='center'>State Demo (Python) - View</h1><hr>")

if not sid:
    print("<p><b>No session cookie found.</b></p>")
elif not state:
    print("<p><b>No saved state found for this session.</b></p>")
else:
    print("<p><b>Saved State:</b></p><ul>")
    for k in sorted(state.keys()):
        print(f"<li>{k} = {state[k]}</li>")
    print("</ul>")

print("<br>")
print(f"<a href='{LINK_BASE}/state-set-python.py'>Back to set page</a><br>")
print(f"<a href='{LINK_BASE}/state-clear-python.py'>Clear saved state</a><br>")
print("</body></html>")
