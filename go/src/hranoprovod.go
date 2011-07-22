package main

import (
  "fmt"
)

func main(){
  var db NodeList
  db.ParseFile("food.yaml")
  fmt.Print(db);
}
