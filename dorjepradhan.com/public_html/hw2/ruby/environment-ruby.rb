#!/usr/bin/env ruby
puts "Cache-Control: no-cache"
puts "Content-Type: text/html\n\n"

puts "<!DOCTYPE html><html><head><title>Environment Variables</title></head><body>"
puts "<h1 align='center'>Environment Variables</h1><hr>"

ENV.keys.sort.each do |k|
  puts "<b>#{k}:</b> #{ENV[k]}<br />"
end

puts "</body></html>"