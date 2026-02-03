#!/usr/bin/env python3
import os, sys, json, datetime, secrets, urllib.parse

COOKIE_NAME = "STATEID"
STORE_DIR = "/tmp"
LINK_BASE = "/hw2/python"

def now_string():
    return datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")

def parse_cookies():
    raw = os.environ.get("HTTP_COOKIE", "")
    cookies = {}
    parts = raw.split(";")
    for p in parts:
        p = p.strip()
        if "=" in p:
            k, v = p.split("=", 1)
            cookies[k.strip()] = v.strip()
    return cookies

def read_body():
    length = os.environ.get("CONTENT_LENGTH")
    try:
        n = int(length) if length else 0
    except ValueError:
        n = 0
    return sys.stdin.read(n) if n > 0 else ""

def parse_urlencoded(s):
    return {k: v[0] if len(v) == 1 else v
            for k, v in urllib.parse.parse_qs(s, keep_blank_values=True).items()}

def get_session_id():
    cookies = parse_cookies()
    sid = cookies.get(COOKIE_NAME)
    if sid and sid.isalnum():
        return sid
    return secrets.token_hex(16)

def store_path(sid):
    return os.path.join(STORE_DIR, f"state_{sid}.json")

def load_state(sid):
    path = store_path(sid)
    try:
        with open(path, "r", encoding="utf-8") as f:
            return json.load(f)
    except Exception:
        return {}

def save_state(sid, state):
    path = store_path(sid)
    with open(path, "w", encoding="utf-8") as f:
        json.dump(state, f)

method = os.environ.get("REQUEST_METHOD", "GET")
content_type = os.environ.get("CONTENT_TYPE", "")
query = os.environ.get("QUERY_STRING", "")
ip = os.environ.get("REMOTE_ADDR", "")
ua = os.environ.get("HTTP_USER_AGENT", "")
now = now_string()

sid = get_session_id()
state = load_state(sid)

data = {}
if method == "GET":
    data = parse_urlencoded(query) if query else {}
else:
    body = read_body()
    if "application/json" in content_type:
        try:
            data = json.loads(body) if body else {}
        except json.JSONDecodeError:
            data = {}
    else:
        data = parse_urlencoded(body) if body else {}

# If user submitted a "username" field, save it
username = ""
if "username" in data:
    username = data["username"] if isinstance(data["username"], str) else str(data["username"])
    username = username.strip()
    if username:
        state["username"] = username
        state["saved_at"] = now
        state["ip"] = ip
        state["user_agent"] = ua
        save_state(sid, state)

print("Cache-Control: no-cache")
print("Content-Type: text/html")
print(f"Set-Cookie: {COOKIE_NAME}={sid}; Path=/")
print()

print("<!DOCTYPE html><html><head><title>State Set (Python)</title></head><body>")
print("<h1 align='center'>State Demo (Python) - Set</h1><hr>")

current = state.get("username", "")
if current:
    print(f"<p><b>Currently saved username:</b> {current}</p>")
else:
    print("<p><b>Currently saved username:</b> (none)</p>")

print("<h3>Save a username</h3>")
print(f"<form method='GET' action='{LINK_BASE}/state-set-python.py'>")
print("<label>Username: <input name='username' /></label>")
print("<button type='submit'>Save</button>")
print("</form>")

print("<br>")
print(f"<a href='{LINK_BASE}/state-view-python.py'>View saved state</a><br>")
print(f"<a href='{LINK_BASE}/state-clear-python.py'>Clear saved state</a><br>")

print("</body></html>")
