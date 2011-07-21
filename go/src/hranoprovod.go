package main

import (
  "os"
  "log"
  "fmt"
  "bufio"
  "strings"
  "strconv"
)

var db = [...]Node{}[:]

type Node struct{
  name string
  elements map[string] float32
}

func mytrim(s string) string{
  return strings.Trim(s, "\t :\n");
}

func addNode(node Node){
  fmt.Println(node)
  db = append(db, node);
}

func parseFile(file_name string){
  f, err := os.Open(file_name);
  if (err != nil) {
    log.Print(err)
  }

  input := bufio.NewReader(f)

  var node Node
  node.elements = make(map[string] float32)

  for {
    line, err := input.ReadString(10)
    if err != nil {
      log.Print(err)
      break
    }

    //skip empty lines
    if (line[0] == 10){
      continue
    }

    //new nodes start at the beginning of the line
    if(line[0] != 32 && line[0] != 8){
      if node.name != ""{
        addNode(node)
      }
      node.name = strings.TrimRight(line, "\t\n\r ")
      continue
    } 
    separator := strings.LastIndexAny(line, "\t ")

    ename := mytrim(line[0:separator])
    enum, err := strconv.Atof32(mytrim(line[separator:]))

    if err != nil{
      log.Println(err)
      continue
    }
    node.elements[ename] = enum;
  }
  if (node.name != ""){
    addNode(node);
  }
  f.Close();
}


func main(){
  parseFile("food.yaml")
}
