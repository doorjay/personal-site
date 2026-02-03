#!/usr/bin/env ruby
now = Time.now
ip = ENV["REMOTE_ADDR"] || ""

puts "Cache-Control: no-cache"
puts "Content-Type: text/html\n\n"

puts "<!DOCTYPE html><html><head><title>Hello CGI World</title></head><body>"
puts "<h1 align=center>Hello HTML World</h1><hr/>"
puts "<p>Hello from Dorje Pradhan</p>"
puts "<p>This page was generated with the Ruby programming language</p>"
puts "<p>This program was generated at: #{now}</p>"
puts "<p>Your current IP Address is: #{ip}</p>"
puts "</body></html>"