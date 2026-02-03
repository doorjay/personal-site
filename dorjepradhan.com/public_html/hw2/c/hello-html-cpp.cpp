#include <iostream>
#include <cstdlib>
#include <ctime>
#include <string>

static std::string env_or_empty(const char* k){
  const char* v = std::getenv(k);
  return v ? std::string(v) : std::string("");
}

static std::string now_string(){
  std::time_t t = std::time(nullptr);
  char buf[64];
  std::strftime(buf, sizeof(buf), "%Y-%m-%d %H:%M:%S", std::localtime(&t));
  return std::string(buf);
}

int main(){
  std::string ip = env_or_empty("REMOTE_ADDR");
  std::string now = now_string();

  std::cout << "Cache-Control: no-cache\n";
  std::cout << "Content-Type: text/html\n\n";

  std::cout << "<!DOCTYPE html><html><head><title>Hello CGI World</title></head><body>";
  std::cout << "<h1 align='center'>Hello HTML World</h1><hr/>";
  std::cout << "<p>Hello from Dorje Pradhan</p>";
  std::cout << "<p>This page was generated with the C/C++ programming language</p>";
  std::cout << "<p>This program was generated at: " << now << "</p>";
  std::cout << "<p>Your current IP Address is: " << ip << "</p>";
  std::cout << "</body></html>";

  return 0;
}
