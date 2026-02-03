#!/usr/bin/env ruby
require "json"

now = Time.now
ip = ENV["REMOTE_ADDR"] || ""

puts "Cache-Control: no-cache"
puts "Content-Type: application/json\n\n"

msg = {
  "title" => "Hello, Ruby!",
  "heading" => "Hello, Ruby!",
  "message" => "This page was generated with the Ruby programming language",
  "time" => now.to_s,
  "IP" => ip
}

puts JSON.generate(msg)
