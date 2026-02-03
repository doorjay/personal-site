#!/usr/bin/env ruby

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

def store_path(sid)
  File.join(STORE_DIR, "state_#{sid}.json")
end

cookies = parse_cookies
sid = cookies[COOKIE_NAME] || ""

deleted = false
if sid.match?(/\A[a-zA-Z0-9]+\z/)
  begin
    File.delete(store_path(sid))
    deleted = true
  rescue
    deleted = false
  end
end

puts "Cache-Control: no-cache"
puts "Content-Type: text/html"
puts "Set-Cookie: #{COOKIE_NAME}=; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/"
puts

puts "<!DOCTYPE html><html><head><title>State Clear (Ruby)</title></head><body>"
puts "<h1 align='center'>State Demo (Ruby) - Clear</h1><hr>"
puts "<p><b>Cleared cookie.</b></p>"
puts "<p><b>Deleted server-side state file:</b> #{deleted ? "Yes" : "No"}</p>"
puts "<br>"
puts "<a href='#{LINK_BASE}/state-set-ruby.rb'>Back to set page</a>"
puts "</body></html>"
