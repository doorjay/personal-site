#include <iostream>
#include <string>
#include <map>
#include <cstdlib>
#include <fstream>

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

static std::map<std::string,std::string> load_state(const std::string& sid){
  std::map<std::string,std::string> st;
  std::ifstream f(path_for(sid));
  if(!f) return st;
  std::string line;
  while(std::getline(f, line)){
    auto eq = line.find('=');
    if(eq == std::string::npos) continue;
    st[line.substr(0, eq)] = line.substr(eq+1);
  }
  return st;
}

int main(){
  std::string raw_cookie = env_or_empty("HTTP_COOKIE");
  std::string sid = get_cookie_value(raw_cookie, COOKIE_NAME);

  std::map<std::string,std::string> state;
  if(is_alnum_str(sid)){
    state = load_state(sid);
  }

  std::cout << "Cache-Control: no-cache\n";
  std::cout << "Content-Type: text/html\n\n";

  std::cout << "<!DOCTYPE html><html><head><title>State View (C/C++)</title></head><body>";
  std::cout << "<h1 align='center'>State Demo (C/C++) - View</h1><hr>";

  if(sid.empty()){
    std::cout << "<p><b>No session cookie found.</b></p>";
  } else if(state.empty()){
    std::cout << "<p><b>No saved state found for this session.</b></p>";
  } else {
    std::cout << "<p><b>Saved State:</b></p><ul>";
    for(const auto& kv : state){
      std::cout << "<li>" << kv.first << " = " << kv.second << "</li>";
    }
    std::cout << "</ul>";
  }

  std::cout << "<br>";
  std::cout << "<a href='" << LINK_BASE << "/state-set-cpp.cgi'>Back to set page</a><br>";
  std::cout << "<a href='" << LINK_BASE << "/state-clear-cpp.cgi'>Clear saved state</a><br>";
  std::cout << "</body></html>";
  return 0;
}
