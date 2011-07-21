package main

import (
  "os"
  "log"
  "fmt"
  "bufio"
  "strings"
)

func main(){
  f, err := os.Open("food.yaml");
  if (err != nil) {
    log.Print(err)
  }
  input := bufio.NewReader(f)
  for {
    line, err := input.ReadString(10)
    if err != nil {
      log.Print(err)
      break
    }
    fmt.Println(strings.TrimRight(line, "\t\n\r "))
  }

  //err = os.Close(f);
}
