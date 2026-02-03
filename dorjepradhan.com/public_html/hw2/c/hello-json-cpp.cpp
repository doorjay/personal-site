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

// Minimal JSON escaping (enough for typical ASCII inputs)
static std::string json_escape(const std::string& s){
  std::string out;
  out.reserve(s.size());
  for(char c : s){
    switch(c){
      case '\\': out += "\\\\"; break;
      case '"':  out += "\\\""; break;
      case '\n': out += "\\n"; break;
      case '\r': out += "\\r"; break;
      case '\t': out += "\\t"; break;
      default:   out += c; break;
    }
  }
  return out;
}

int main(){
  std::string ip = env_or_empty("REMOTE_ADDR");
  std::string now = now_string();

  std::cout << "Cache-Control: no-cache\n";
  std::cout << "Content-Type: application/json\n\n";

  std::cout << "{";
  std::cout << "\"title\":\"Hello, C/C++!\",";
  std::cout << "\"heading\":\"Hello, C/C++!\",";
  std::cout << "\"message\":\"This page was generated with the C/C++ programming language\",";
  std::cout << "\"time\":\"" << json_escape(now) << "\",";
  std::cout << "\"IP\":\"" << json_escape(ip) << "\"";
  std::cout << "}\n";

  return 0;
}
