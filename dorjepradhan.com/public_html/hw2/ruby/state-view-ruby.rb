#!/usr/bin/env ruby
require "json"

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
state = {}

if sid.match?(/\A[a-zA-Z0-9]+\z/)
  begin
    state = JSON.parse(File.read(store_path(sid)))
  rescue
    state = {}
  end
end

puts "Cache-Control: no-cache"
puts "Content-Type: text/html\n\n"

puts "<!DOCTYPE html><html><head><title>State View (Ruby)</title></head><body>"
puts "<h1 align='center'>State Demo (Ruby) - View</h1><hr>"

if sid.empty?
  puts "<p><b>No session cookie found.</b></p>"
elsif state.empty?
  puts "<p><b>No saved state found for this session.</b></p>"
else
  puts "<p><b>Saved State:</b></p><ul>"
  state.keys.sort.each do |k|
    puts "<li>#{k} = #{state[k]}</li>"
  end
  puts "</ul>"
end

puts "<br>"
puts "<a href='#{LINK_BASE}/state-set-ruby.rb'>Back to set page</a><br>"
puts "<a href='#{LINK_BASE}/state-clear-ruby.rb'>Clear saved state</a><br>"
puts "</body></html>"
