#!/usr/bin/env ruby
require "json"
require "uri"

def read_body
  len = ENV["CONTENT_LENGTH"].to_i
  return "" if len <= 0
  STDIN.read(len)
end

def parse_urlencoded(s)
  out = {}
  URI.decode_www_form(s).each do |k, v|
    out[k] = v
  end
  out
end

method = ENV["REQUEST_METHOD"] || ""
query  = ENV["QUERY_STRING"] || ""
ctype  = ENV["CONTENT_TYPE"] || ""
ua     = ENV["HTTP_USER_AGENT"] || ""
ip     = ENV["REMOTE_ADDR"] || ""
host   = ENV["HTTP_HOST"] || ""
now    = Time.now.to_s

body = read_body
parsed = {}

if method == "GET"
  parsed = query.empty? ? {} : parse_urlencoded(query)
else
  if ctype.include?("application/json")
    begin
      parsed = body.empty? ? {} : JSON.parse(body)
    rescue
      parsed = {"_json_error" => "invalid JSON"}
    end
  else
    parsed = body.empty? ? {} : parse_urlencoded(body)
  end
end

puts "Cache-Control: no-cache"
puts "Content-Type: text/html\n\n"

puts "<!DOCTYPE html><html><head><title>Echo (Ruby)</title></head><body>"
puts "<h1 align='center'>Echo (Ruby)</h1><hr>"
puts "<p><b>Hostname:</b> #{host}</p>"
puts "<p><b>Time:</b> #{now}</p>"
puts "<p><b>IP:</b> #{ip}</p>"
puts "<p><b>User-Agent:</b> #{ua}</p>"
puts "<p><b>Method:</b> #{method}</p>"
puts "<p><b>Content-Type:</b> #{ctype}</p>"
puts "<p><b>Query String:</b> #{query}</p>"
puts "<p><b>Raw Body:</b> #{body}</p>"

puts "<p><b>Parsed Data:</b></p><ul>"
parsed.keys.sort.each do |k|
  puts "<li>#{k} = #{parsed[k]}</li>"
end
puts "</ul>"

puts "</body></html>"
