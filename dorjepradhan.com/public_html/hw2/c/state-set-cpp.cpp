#include <iostream>
#include <string>
#include <map>
#include <cstdlib>
#include <cstdio>
#include <ctime>
#include <sstream>
#include <fstream>

static const std::string COOKIE_NAME = "STATEID";
static const std::string STORE_DIR = "/tmp";
static const std::string LINK_BASE = "/hw2/c";

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

static int hexval(char c){
  if('0'<=c && c<='9') return c-'0';
  if('a'<=c && c<='f') return 10 + (c-'a');
  if('A'<=c && c<='F') return 10 + (c-'A');
  return 0;
}

static std::string urldecode(const std::string& s){
  std::string out;
  for(size_t i=0;i<s.size();i++){
    if(s[i] == '%' && i+2 < s.size()){
      out.push_back(char(hexval(s[i+1])*16 + hexval(s[i+2])));
      i += 2;
    } else if(s[i] == '+'){
      out.push_back(' ');
    } else {
      out.push_back(s[i]);
    }
  }
  return out;
}

static std::map<std::string,std::string> parse_urlencoded(const std::string& s){
  std::map<std::string,std::string> out;
  std::stringstream ss(s);
  std::string pair;
  while(std::getline(ss, pair, '&')){
    auto eq = pair.find('=');
    if(eq == std::string::npos) continue;
    std::string k = urldecode(pair.substr(0, eq));
    std::string v = urldecode(pair.substr(eq+1));
    out[k] = v;
  }
  return out;
}

static std::string read_body(){
  const char* cl = std::getenv("CONTENT_LENGTH");
  if(!cl) return "";
  int n = std::atoi(cl);
  if(n <= 0) return "";
  std::string body(n, '\0');
  std::cin.read(&body[0], n);
  return body;
}

static std::string get_cookie_value(const std::string& raw, const std::string& name){
  // very simple cookie parser: "a=b; c=d"
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

static std::string gen_id(){
  // Read 16 bytes from /dev/urandom and hex-encode
  std::ifstream ur("/dev/urandom", std::ios::binary);
  if(ur){
    unsigned char buf[16];
    ur.read((char*)buf, 16);
    std::ostringstream oss;
    const char* hex = "0123456789abcdef";
    for(int i=0;i<16;i++){
      oss << hex[(buf[i]>>4)&0xF] << hex[buf[i]&0xF];
    }
    return oss.str();
  }
  // fallback
  return std::to_string(std::time(nullptr)) + "12345";
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

static void save_state(const std::string& sid, const std::map<std::string,std::string>& st){
  std::ofstream f(path_for(sid), std::ios::trunc);
  for(const auto& kv : st){
    f << kv.first << "=" << kv.second << "\n";
  }
}

int main(){
  std::string method = env_or_empty("REQUEST_METHOD");
  std::string query  = env_or_empty("QUERY_STRING");
  std::string ctype  = env_or_empty("CONTENT_TYPE");
  std::string ip     = env_or_empty("REMOTE_ADDR");
  std::string ua     = env_or_empty("HTTP_USER_AGENT");
  std::string now    = now_string();

  std::string raw_cookie = env_or_empty("HTTP_COOKIE");
  std::string sid = get_cookie_value(raw_cookie, COOKIE_NAME);
  if(!is_alnum_str(sid)) sid = gen_id();

  auto state = load_state(sid);

  std::map<std::string,std::string> data;
  if(method == "GET"){
    data = query.empty() ? std::map<std::string,std::string>() : parse_urlencoded(query);
  } else {
    std::string body = read_body();
    // For state demo, treat non-JSON as urlencoded
    if(ctype.find("application/json") != std::string::npos){
      // keep simple: store raw json if present
      if(!body.empty()){
        data["json_raw"] = body;
      }
    } else {
      data = body.empty() ? std::map<std::string,std::string>() : parse_urlencoded(body);
    }
  }

  auto it = data.find("username");
  if(it != data.end()){
    std::string username = it->second;
    if(!username.empty()){
      state["username"] = username;
      state["saved_at"] = now;
      state["ip"] = ip;
      state["user_agent"] = ua;
      save_state(sid, state);
    }
  }

  std::cout << "Cache-Control: no-cache\n";
  std::cout << "Content-Type: text/html\n";
  std::cout << "Set-Cookie: " << COOKIE_NAME << "=" << sid << "; Path=/\n\n";

  std::cout << "<!DOCTYPE html><html><head><title>State Set (C/C++)</title></head><body>";
  std::cout << "<h1 align='center'>State Demo (C/C++) - Set</h1><hr>";

  std::string current = state.count("username") ? state["username"] : "";
  std::cout << "<p><b>Currently saved username:</b> " << (current.empty() ? "(none)" : current) << "</p>";

  std::cout << "<h3>Save a username</h3>";
  std::cout << "<form method='GET' action='" << LINK_BASE << "/state-set-cpp.cgi'>";
  std::cout << "<label>Username: <input name='username' /></label>";
  std::cout << "<button type='submit'>Save</button>";
  std::cout << "</form>";

  std::cout << "<br>";
  std::cout << "<a href='" << LINK_BASE << "/state-view-cpp.cgi'>View saved state</a><br>";
  std::cout << "<a href='" << LINK_BASE << "/state-clear-cpp.cgi'>Clear saved state</a><br>";

  std::cout << "</body></html>";
  return 0;
}
