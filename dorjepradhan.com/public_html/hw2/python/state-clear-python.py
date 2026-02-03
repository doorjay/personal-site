#!/usr/bin/env python3
import os

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

cookies = parse_cookies()
sid = cookies.get(COOKIE_NAME, "")

deleted = False
if sid and sid.isalnum():
    path = os.path.join(STORE_DIR, f"state_{sid}.json")
    try:
        os.remove(path)
        deleted = True
    except Exception:
        deleted = False

print("Cache-Control: no-cache")
print("Content-Type: text/html")
print(f"Set-Cookie: {COOKIE_NAME}=; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/")
print()

print("<!DOCTYPE html><html><head><title>State Clear (Python)</title></head><body>")
print("<h1 align='center'>State Demo (Python) - Clear</h1><hr>")
print("<p><b>Cleared cookie.</b></p>")
print("<p><b>Deleted server-side state file:</b> " + ("Yes" if deleted else "No") + "</p>")
print("<br>")
print(f"<a href='{LINK_BASE}/state-set-python.py'>Back to set page</a>")
print("</body></html>")
