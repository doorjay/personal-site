#include <iostream>
#include <string>
#include <map>
#include <cstdlib>
#include <sstream>
#include <fstream>

static int hexval(char c){
  if('0'<=c && c<='9') return c-'0';
  if('a'<=c && c<='f') return 10 + (c-'a');
  if('A'<=c && c<='F') return 10 + (c-'A');
  return 0;
}

static std::string urldecode(const std::string& s){
  std::string out;
  for(size_t i=0;i<s.size();i++){
    if(s[i]=='%'+0 && i+2<s.size()){
      out.push_back(char(hexval(s[i+1])*16 + hexval(s[i+2])));
      i+=2;
    } else if(s[i]=='+'){
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

int main(){
  std::string method = std::getenv("REQUEST_METHOD") ? std::getenv("REQUEST_METHOD") : "";
  std::string query  = std::getenv("QUERY_STRING") ? std::getenv("QUERY_STRING") : "";
  std::string ctype  = std::getenv("CONTENT_TYPE") ? std::getenv("CONTENT_TYPE") : "";
  std::string ua     = std::getenv("HTTP_USER_AGENT") ? std::getenv("HTTP_USER_AGENT") : "";
  std::string ip     = std::getenv("REMOTE_ADDR") ? std::getenv("REMOTE_ADDR") : "";
  std::string host   = std::getenv("HTTP_HOST") ? std::getenv("HTTP_HOST") : "";

  std::string body = read_body();
  std::map<std::string,std::string> parsed;

  if(method == "GET"){
    parsed = query.empty() ? std::map<std::string,std::string>() : parse_urlencoded(query);
  } else {
    // For JSON: just echo raw body; parsing JSON fully in C++ is overkill for this assignment
    if(ctype.find("application/json") != std::string::npos){
      parsed["_json_raw"] = body;
    } else {
      parsed = body.empty() ? std::map<std::string,std::string>() : parse_urlencoded(body);
    }
  }

  std::cout << "Cache-Control: no-cache\n";
  std::cout << "Content-Type: text/html\n\n";

  std::cout << "<!DOCTYPE html><html><head><title>Echo (C++)</title></head><body>";
  std::cout << "<h1 align='center'>Echo (C++)</h1><hr>";
  std::cout << "<p><b>Hostname:</b> " << host << "</p>";
  std::cout << "<p><b>IP:</b> " << ip << "</p>";
  std::cout << "<p><b>User-Agent:</b> " << ua << "</p>";
  std::cout << "<p><b>Method:</b> " << method << "</p>";
  std::cout << "<p><b>Content-Type:</b> " << ctype << "</p>";
  std::cout << "<p><b>Query String:</b> " << query << "</p>";
  std::cout << "<p><b>Raw Body:</b> " << body << "</p>";

  std::cout << "<p><b>Parsed Data:</b></p><ul>";
  for(const auto& kv : parsed){
    std::cout << "<li>" << kv.first << " = " << kv.second << "</li>";
  }
  std::cout << "</ul></body></html>";
  return 0;
}
