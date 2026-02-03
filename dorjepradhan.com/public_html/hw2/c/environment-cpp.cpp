#include <iostream>
#include <cstdlib>

extern char **environ;

int main(){
  std::cout << "Cache-Control: no-cache\n";
  std::cout << "Content-Type: text/html\n\n";

  std::cout << "<!DOCTYPE html><html><head><title>Environment Variables</title></head><body>";
  std::cout << "<h1 align='center'>Environment Variables (C/C++)</h1><hr>";

  // Each entry is "KEY=VALUE"
  for(char **env = environ; *env != nullptr; env++){
    std::cout << *env << "<br />\n";
  }

  std::cout << "</body></html>";
  return 0;
}
