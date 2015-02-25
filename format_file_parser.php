<?php

#SYN:xgrana02

/**
 * Zpracovává a kontroluje formatovaci soubor
 * @author stepan
 */
include_once 'format_unit.php';
include_once 'html_unit.php';

final class FormatFileParser
{

  public static function parse($format_file)
  {
    $ret = array();
    while(($line = fgets($format_file)) !== false)
    {
      $tab = 0;
      if(($tab = strpos($line, "\t")) === false)
      {
        //radek neobsahuje tabulator
        throw new Exception("Unknown format of format file", 4);
      }
      $expr = substr($line, 0, $tab);
      $html = substr($line, $tab + 1);
      //echo "'$expr' '$html'\n";
      $expr = self::parseRegExpr($expr);
      $html = self::parseFormat($html);

      //escapujeme html znacky
      $expr = htmlspecialchars($expr, ENT_NOQUOTES);

      //echo $expr . "\n";

      $ret[] = new FormatUnit($expr, $html);
    }
    return $ret;
  }

  /**
   * @brief Prevede regularni vyraz na ekvivalentni PCRE
   * @param string $expr regularni vyraz
   * @return string vraci prevedeny reg. vyraz
   */
  private static function parseRegExpr($expr)
  {
    //tabulka prevodu meta znaku na obvyklou syntax
    $meta = array(
      "." => "",
      "|" => "|",
      "*" => "*",
      "+" => "+",
      "(" => "(",
      ")" => ")",
    );

    //znaky, ktere maji specialni vyznam
    $escape = array(
      "." => true,
      "|" => true,
      "*" => true,
      "+" => true,
      "(" => true,
      ")" => true,
      "{" => true,
      "}" => true,
      "[" => true,
      "]" => true,
      "^" => true,
      "$" => true,
      "?" => true,
      ":" => true,
      "/" => true,
      "\\" => true,
    );

    //znaky, ktere maji specialni vyznam, ale my je nepouzivame - musime je escapovat
    $autoEscape = array(
      "{" => true,
      "}" => true,
      "[" => true,
      "]" => true,
      "^" => true,
      "$" => true,
      "?" => true,
      ":" => true,
      "/" => true,
      "\\" => true,
    );

    //znaky, ktere maji specialni vyznam pri escapovani (ze zadani)
    $special = array(
      "s" => "\s",
      "a" => "\s\S",
      "d" => "0-9",
      "l" => "a-z",
      "L" => "A-Z",
      "w" => "a-zA-Z",
      "W" => "a-zA-Z0-9",
      "t" => "\t",
      "n" => "\n",
      "." => "\\.",
    );

    $state = "wait";
    $str = str_split($expr);
    $ret = "";

    //v pripade negace se musi upravit vystup
    $inNegation = false;

    for($i = 0; $i < count($str); $i++)
    {
      $char = $str[$i];
      //32 = znak mezery, bereme jen znaky vetsi (ze zadani)
      if(ord($char) < 32)
      {
        throw new Exception("Unsupported characters in regular expression", 4);
      }
      switch($state)
      {
        //cekame na escapovaci znak
        case "wait":
          if($char == "%")
          {
            $state = "escape";
          }
          elseif($char == "!")
          {
            $state = "negate";
          }
          elseif(isset($meta[$char]))
          {
            if($char == ")" and $inNegation)
            {
              $ret.="]";
              $meta["|"] = "|";
              $inNegation = false;
            }
            else
            {
              $ret.= $meta[$char];
            }
          }
          elseif(isset($autoEscape[$char]))
          {
            $ret.="\\" . $char;
          }
          else
          {
            $ret.=$char;
          }
          break;

        //prvadime escapovani
        case "escape":
          if(isset($special[$char]))
          {
            $ret.= "[" . $special[$char] . "]";
          }
          elseif(isset($meta[$char]))
          {
            if(in_array($char, $meta))
            {
              $ret.="\\" . $char;
            }
            else
            {
              $ret .= $char;
            }
          }
          else
          {
            if(isset($escape[$char]))
            {
              $ret.="%\\" . $char;
            }
            else
            {
              $ret .= "%" . $char;
            }
          }
          $state = "wait";
          break;

        //prevadime negaci
        case "negate":
          if($char == "%")
          {
            $state = "negGroup";
          }
          elseif($char === "(")
          {
            $ret.="[^";
            $state = "wait";
            
            //znak | se pri negaci nepouzivat
            $meta["|"] = "";
            $inNegation = TRUE;
          }
          elseif(!isset($meta[$char]) and $char != "!")
          {
            if(isset($escape[$char]))
            {
              $ret.= "[^\\" . $char . "]";
            }
            else
            {
              $ret.= "[^" . $char . "]";
            }
            $state = "wait";
          }
          else
          {
            $state = "wait";
          }
          break;

        //negace skupiny
        case "negGroup":
          if(isset($special[$char]))
          {
            $ret.= "[^" . $special[$char] . "]";
          }
          $state = "wait";
          break;
      }
    }

    $ret = "/" . $ret . "/";
    return $ret;
  }

  /**
   * 
   * @param string $args Jednotlive formatovaci znacky oddelene carkou
   * @return Array Pole Html tagu (HtmlUnit)
   * @throws Exception Pro nezname formatovaci znacky
   */
  private static function parseFormat($args)
  {
    $ret = array();

    //formatovaci znacky bez argumetu a jejich html protesky
    $tagsNoArg = array(
      "bold" => "b",
      "italic" => "i",
      "underline" => "u",
      "teletype" => "tt"
    );

    //formatovaci znacky s argumenty a odkazy na funkce,
    //ktere je zkontroluji a prevedou na spravny tvar
    $tagsWithArg = array(
      "size" => 'self::parseSizeTag',
      "color" => 'self::parseColorTag'
    );

    //rozdeleni podle carky
    $arr = explode(",", trim($args));
    foreach($arr as $arg)
    {
      //rozdeleni podle dvojtecky
      $parArr = explode(":", $arg);
      if(count($parArr) == 1)
      {
        //znacka bez argumentu
        $mark = trim($parArr[0]);
        if(isset($tagsNoArg[$mark]))
        {
          $ret[] = new HtmlUnit("<" . $tagsNoArg[$mark] . ">", "b", $tagsNoArg[$mark]);
          continue;
        }
      }
      elseif(count($parArr) == 2)
      {
        //znacka s argumetem
        $mark = trim($parArr[0]);
        $value = trim($parArr[1]);
        if(isset($tagsWithArg[$mark]))
        {
          $ret[] = call_user_func_array($tagsWithArg[$mark], array($value));
          continue;
        }
      }

      throw new Exception("Unknown format mark '" . trim($arg, " ") . "'", 4);
    }
    return $ret;
  }

  //kontroluje format velikosti pisma a prevadi ho na spravny format
  private static function parseSizeTag($param)
  {
    if(preg_match("/[1-7]/", $param))
    {
      return new HtmlUnit("<font size=$param>", "b", "font");
    }
    throw new Exception("Value '$param' of parameter 'size' is not valid", 4);
  }

  //kontroluje format barvy
  private static function parseColorTag($param)
  {
    if(preg_match("/[0-9a-fA-F]{6}/i", $param))
    {
      return new HtmlUnit("<font color=#$param>", "b", "font");
    }
    throw new Exception("Value '$param' of parameter 'color' is not valid", 4);
  }

}
