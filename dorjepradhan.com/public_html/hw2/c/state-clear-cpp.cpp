#include <iostream>
#include <string>
#include <cstdlib>
#include <cstdio>

static const std::string COOKIE_NAME = "STATEID";
static const std::string STORE_DIR = "/tmp";
static const std::string LINK_BASE = "/hw2/c";

static std::string env_or_empty(const char* k){
  const char* v = std::getenv(k);
  return v ? std::string(v) : std::string("");
}

static std::string get_cookie_value(const std::string& raw, const std::string& name){
  size_t pos = 0;
  while(pos < raw.size()){
    while(pos < raw.size() && (raw[pos] == ' ' || raw[pos] == ';')) pos++;
    size_t eq = raw.find('=', pos);
    if(eq == std::string::npos) break;
    std::string k = raw.substr(pos, eq - pos);
    size_t end = raw.find(';', eq+1);
    std::string v = raw.substr(eq+1, (end == std::string::npos ? raw.size() : end) - (eq+1));
    if(k == name) return v;
    if(end == std::string::npos) break;
    pos = end + 1;
  }
  return "";
}

static bool is_alnum_str(const std::string& s){
  if(s.empty()) return false;
  for(char c : s){
    if(!std::isalnum((unsigned char)c)) return false;
  }
  return true;
}

static std::string path_for(const std::string& sid){
  return STORE_DIR + "/state_" + sid + ".txt";
}

int main(){
  std::string raw_cookie = env_or_empty("HTTP_COOKIE");
  std::string sid = get_cookie_value(raw_cookie, COOKIE_NAME);

  bool deleted = false;
  if(is_alnum_str(sid)){
    std::string path = path_for(sid);
    if(std::remove(path.c_str()) == 0){
      deleted = true;
    }
  }

  std::cout << "Cache-Control: no-cache\n";
  std::cout << "Content-Type: text/html\n";
  std::cout << "Set-Cookie: " << COOKIE_NAME << "=; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/\n\n";

  std::cout << "<!DOCTYPE html><html><head><title>State Clear (C/C++)</title></head><body>";
  std::cout << "<h1 align='center'>State Demo (C/C++) - Clear</h1><hr>";
  std::cout << "<p><b>Cleared cookie.</b></p>";
  std::cout << "<p><b>Deleted server-side state file:</b> " << (deleted ? "Yes" : "No") << "</p>";
  std::cout << "<br>";
  std::cout << "<a href='" << LINK_BASE << "/state-set-cpp.cgi'>Back to set page</a>";
  std::cout << "</body></html>";
  return 0;
}
