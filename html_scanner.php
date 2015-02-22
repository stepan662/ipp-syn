<?php

#SYN:xgrana02

/**
 * Rozdeluje html soubor na html jednotky
 * @author stepan
 */
include_once 'html_unit.php';

class HtmlScanner
{

  private $file;
  private $index = 0;

  public function __construct(&$file)
  {
    $this->file = str_split($file);
  }

  public function getUnit()
  {
    $html = "";
    $type = "t";
    $name = "";

    //jsme na konci souboru a nemame co vratit
    if($this->index >= count($this->file))
    {
      return false;
    }

    $state = "begin";
    while($state !== "done")
    {
      if($this->index >= count($this->file))
      {
        $this->index++;
        break;
      }
      
      $char = $this->file[$this->index];
      
      //echo "$state($char)\n";

      switch($state)
      {
        case "begin":
          $html .= $char;
          if($char == "<")
          {
            $state = "tagBegin";
          }
          else
          {
            $state = "text";
          }
          break;

        case "tagBegin":
          $html .= $char;
          if(!ctype_space($char))
          {
            if($char === "/")
            {
              $type = "e";  //tag je ukoncovaci
              $state = "nameBegin";
            }
            else
            {
              $type = "b";  //tag je oteviraci
              $state = "name";
              $name.=$char;
            }
          }
          break;

        case "nameBegin":
          $html .= $char;
          if(!ctype_space($char))
          {
            $state = "name";
            $name.=$char;
          }
          break;

        case "name":
          $html .= $char;
          if(!ctype_space($char) and $char !== ">")
          {
            $name.=$char;
          }
          elseif($char === ">")
          {
            $state = "done";
          }
          else
          {
            $state = "afterName";
          }
          break;

        case "afterName":
          $html .= $char;
          if($char === ">")
          {
            $state = "done";
          }
          break;

        case "text":
          if($char === "<")
          {
            $state = "done";
            $this->index--;
          }
          else
          {
            $html .= $char;
          }
          break;
      }

      $this->index++;
    }

    if($state == "done")
    {
      return new HtmlUnit($html, $type, $name);
    }
    else
    {
      return new HtmlUnit($html, "t");
    }
  }

}
