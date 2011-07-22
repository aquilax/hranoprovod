package main

import (
  "container/vector" 
)


type Node struct{
  name string
  elements map[string] float32
}

type NodeList struct{
  items vector.Vector
}
