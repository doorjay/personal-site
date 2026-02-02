#!/usr/bin/env python3
import os, datetime

print("Cache-Control: no-cache")
print("Content-Type: text/html\n")

ip = os.environ.get("REMOTE_ADDR", "")
now = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")

print("<!DOCTYPE html><html><head><title>Hello CGI World</title></head><body>")
print("<h1 align=center>Hello HTML World</h1><hr/>")
print("<p>Hello from Dorje Pradhan</p>")
print("<p>This page was generated with the Python programming language</p>")
print(f"<p>This program was generated at: {now}</p>")
print(f"<p>Your current IP Address is: {ip}</p>")
print("</body></html>")
