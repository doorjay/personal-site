#!/usr/bin/env ruby
require "json"
require "securerandom"
require "uri"

COOKIE_NAME = "STATEID"
STORE_DIR = "/tmp"
LINK_BASE = "/hw2/ruby"

def parse_cookies
  raw = ENV["HTTP_COOKIE"] || ""
  cookies = {}
  raw.split(";").each do |p|
    p = p.strip
    next unless p.include?("=")
    k, v = p.split("=", 2)
    cookies[k.strip] = v.strip
  end
  cookies
end

def read_body
  len = (ENV["CONTENT_LENGTH"] || "0").to_i
  return "" if len <= 0
  STDIN.read(len)
end

def parse_urlencoded(s)
  out = {}
  URI.decode_www_form(s).each { |k, v| out[k] = v }
  out
end

def store_path(sid)
  File.join(STORE_DIR, "state_#{sid}.json")
end

def load_state(sid)
  path = store_path(sid)
  JSON.parse(File.read(path))
rescue
  {}
end

def save_state(sid, state)
  File.write(store_path(sid), JSON.generate(state))
end

method = ENV["REQUEST_METHOD"] || "GET"
ctype  = ENV["CONTENT_TYPE"] || ""
query  = ENV["QUERY_STRING"] || ""
ip     = ENV["REMOTE_ADDR"] || ""
ua     = ENV["HTTP_USER_AGENT"] || ""
now    = Time.now.to_s

cookies = parse_cookies
sid = cookies[COOKIE_NAME]
sid = nil unless sid && sid.match?(/\A[a-zA-Z0-9]+\z/)
sid ||= SecureRandom.hex(16)

state = load_state(sid)

data = {}
if method == "GET"
  data = query.empty? ? {} : parse_urlencoded(query)
else
  body = read_body
  if ctype.include?("application/json")
    begin
      data = body.empty? ? {} : JSON.parse(body)
    rescue
      data = {}
    end
  else
    data = body.empty? ? {} : parse_urlencoded(body)
  end
end

username = (data["username"] || "").to_s.strip
if !username.empty?
  state["username"] = username
  state["saved_at"] = now
  state["ip"] = ip
  state["user_agent"] = ua
  save_state(sid, state)
end

puts "Cache-Control: no-cache"
puts "Content-Type: text/html"
puts "Set-Cookie: #{COOKIE_NAME}=#{sid}; Path=/"
puts

current = state["username"] || ""

puts "<!DOCTYPE html><html><head><title>State Set (Ruby)</title></head><body>"
puts "<h1 align='center'>State Demo (Ruby) - Set</h1><hr>"
puts "<p><b>Currently saved username:</b> #{current.empty? ? "(none)" : current}</p>"

puts "<h3>Save a username</h3>"
puts "<form method='GET' action='#{LINK_BASE}/state-set-ruby.rb'>"
puts "<label>Username: <input name='username' /></label>"
puts "<button type='submit'>Save</button>"
puts "</form>"

puts "<br>"
puts "<a href='#{LINK_BASE}/state-view-ruby.rb'>View saved state</a><br>"
puts "<a href='#{LINK_BASE}/state-clear-ruby.rb'>Clear saved state</a><br>"
puts "</body></html>"
