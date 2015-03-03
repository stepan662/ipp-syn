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
    while(($line = fgets($format_file)) !== false) {
      $tab = 0;
      if(($tab = strpos($line, "\t")) === false) {
        //radek neobsahuje tabulator
        throw new Exception("Unknown format of format file", 4);
      }
      $expr = substr($line, 0, $tab);
      $html = substr($line, $tab + 1);
      //echo "'$expr' '$html'\n";
      $expr = self::parseRegExpr($expr);
      $html = self::parseFormat($html);

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

    //operatory
    $operators = array(
      "." => "",
      "|" => "|",
      "*" => "*",
      "+" => "+",
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

    $state = "char";
    $str = str_split($expr);
    $ret = "";

    //v pripade negace se musi upravit vystup
    $inNegation = false;

    for($i = 0; $i < count($str); $i++) {
      $char = $str[$i];
      //32 = znak mezery, bereme jen znaky vetsi (ze zadani)
      if(ord($char) < 32) {
        throw new Exception("Unsupported characters in regular expression", 4);
      }
      //echo $ret . "\n";
      //echo $state . "($char)\n";

      switch($state)
      {
        //cekame pouze znak nebo skupinu znaku ne operator
        case "char":
          if($char == "!") {
            $state = "negation";
          }
          elseif($char == "%") {
            $state = "escape";
          }
          elseif(isset($operators[$char])) {
            throw new Exception("Unexpected operator '$char'", 4);
          }
          else {
            $state = "charOperator";
            $ret.=self::esc($char);
          }
          break;

        //ocekavame cokoliv
        case "charOperator":
          if($char == "!") {
            $state = "negation";
          }
          elseif($char == "%") {
            $state = "escape";
          }
          elseif($char == "." or $char == "|") {
            $ret.=$operators[$char];
            $state = "char";
          }
          else {
            $ret.=self::esc($char);
          }
          break;

        //escapujeme nebo nahrazujeme specialni skupinou
        case "escape":
          if(isset($meta[$char])) {
            $state = "charOperator";
            if(isset($escape[$char])) {
              $ret.="\\" . $char;
            }
            else {
              $ret.=$char;
            }
          }
          elseif(isset($special[$char])) {
            $state = "charOperator";
            $ret.= "[" . $special[$char] . "]";
          }
          else {
            $state = "charOperator";
            $ret.="%" . self::esc($char);
          }
          break;

        //negace
        case "negation":
          if($char == "%") {
            $state = "negEscapeOne";
            $ret.="[^";
          }
          elseif($char == "(") {
            $state = "negChar";
            $ret.="[^";
          }
          else {
            $state = "charOperator";
            $ret.="[^" . self::esc($char) . "]";
          }
          break;

        //escapujeme jeden negovany znak
        case "negEscapeOne":
          if(isset($meta[$char])) {
            $state = "charOperator";
            if(isset($escape[$char])) {
              $ret.="\\" . $char . "]";
            }
            else {
              $ret.=$char . "]";
            }
          }
          elseif(isset($special[$char])) {
            $state = "charOperator";
            $ret.= $special[$char] . "]";
          }
          else {
            throw new Exception("Unexpected negation", 4);
          }
          break;

        //escapovani jednoho znaku v negovane zavorce
        case "negEscape":
          if(isset($meta[$char])) {
            $state = "negOperator";
            if(isset($escape[$char])) {
              $ret.="\\" . $char;
            }
            else {
              $ret.=$char;
            }
          }
          else {
            throw new Exception("Unexpected negation", 4);
          }
          break;

        //ocekavame znak nebo escapovany znak
        case "negChar":
          if($char == "%") {
            $state = "negEscape";
          }
          elseif(isset($operators[$char])) {
            throw new Exception("Unexpected operator", 4);
          }
          else {
            $ret.=self::esc($char);
            $state = "negOperator";
          }
          break;

        //ocekavame znak | nebo ) - jen tyto se mohou nachazet v negovane zavorce
        case "negOperator":
          if($char == "|") {
            $state = "negChar";
          }
          elseif($char == ")") {
            $state = "charOperator";
            $ret.="]";
          }
          else {
            throw new Exception("Unexpected character", 4);
          }
          break;
      }
    }

    if($state != "charOperator") {
      throw new Exception("Unexpected end of regexp", 4);
    }

    $ret = "/" . $ret . "/";
    return $ret;
  }

  private static function esc($char)
  {
    //znaky, ktere maji specialni vyznam, ale my je nepouzivame - musime je escapovat
    static $autoEscape = array(
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
    if(isset($autoEscape[$char])) {
      return "\\" . $char;
    }
    else {
      return $char;
    }
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
    foreach($arr as $arg) {
      //rozdeleni podle dvojtecky
      $parArr = explode(":", $arg);
      if(count($parArr) == 1) {
        //znacka bez argumentu
        $mark = trim($parArr[0]);
        if(isset($tagsNoArg[$mark])) {
          $ret[] = new HtmlUnit("<" . $tagsNoArg[$mark] . ">", "b", $tagsNoArg[$mark]);
          continue;
        }
      }
      elseif(count($parArr) == 2) {
        //znacka s argumetem
        $mark = trim($parArr[0]);
        $value = trim($parArr[1]);
        if(isset($tagsWithArg[$mark])) {
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
    if(preg_match("/^[1-7]$/", $param)) {
      return new HtmlUnit("<font size=$param>", "b", "font");
    }
    throw new Exception("Value '$param' of parameter 'size' is not valid", 4);
  }

  //kontroluje format barvy
  private static function parseColorTag($param)
  {
    if(preg_match("/^[0-9a-fA-F]{6}$/i", $param)) {
      return new HtmlUnit("<font color=#$param>", "b", "font");
    }
    throw new Exception("Value '$param' of parameter 'color' is not valid", 4);
  }

}
