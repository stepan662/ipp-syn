<?php

#SYN:xgrana02

/**
 * Zpracovává a kontroluje argumenty programu
 * @author stepan
 */
class Arguments
{

  private $help = false;
  private $br = false;
  private $validate = false;
  private $escape = false;
  private $format_file = "";
  private $input_file = "";
  private $output_file = "";

  /**
   * @throws Exception Spatna kombinace argumetu
   */
  function __construct($argv)
  {
    array_shift($argv);           //prvni argument zahodime(nazev skriptu)
    //musime vyresit chybu s rozpojovanim argumentu (kdyz jsou v nazvu mezery)
    //$argv = $this->solveApostrofs($argv);
    //nacteme argumenty
    $argsAll = $this->arguments($argv);

    if(!empty($argsAll['other'])) {
      //var_dump($argv);
      throw new Exception("Unknown parameters", 1);
    }

    $args = $argsAll['params'];

    if(isset($args['help'])) {
      $this->help = true;
    }
    else {
      //var_dump($args);
      //nastaveni noveho radku pomoci <br />
      if(isset($args['br'])) {
        $this->br = true;
      }

      if(isset($args['nooverlap'])) {
        $this->validate = true;
      }

      if(isset($args['escape'])) {
        $this->escape = true;
      }

      //otevreni souboru input
      if(isset($args['input'])) {
        $input = str_replace(" ", "\x20", $args['input']);
        if(file_exists($input) and ( $handle = fopen($input, "r")) !== FALSE) {
          $this->input_file = $handle;
        }
        else {
          throw new Exception("Can't open input file '$input'", 2);
        }
      }
      else {
        $this->input_file = fopen('php://stdin', 'r');
      }

      //otevreni souboru output
      if(isset($args['output'])) {
        $output = str_replace(" ", "\x20", $args['output']);
        if(($handle = @fopen($output, "w")) !== FALSE) {
          $this->output_file = $handle;
        }
        else {
          throw new Exception("Can't open output file", 3);
        }
      }
      else {
        $this->output_file = fopen('php://stdout', 'w');
      }

      if(isset($args['format'])
          and file_exists(str_replace(" ", "\x20", $args['format']))) {
        $this->format_file = fopen(str_replace(" ", "\x20", $args['format']), "r");
      }
      else {
        $this->format_file = NULL;
      }
    }
  }

  public function isHelp()
  {
    return $this->help;
  }

  public function isBr()
  {
    return $this->br;
  }

  public function isValidate()
  {
    return $this->validate;
  }

  public function isEscape()
  {
    return $this->escape;
  }

  public function getFormatFile()
  {
    return $this->format_file;
  }

  public function getInputFile()
  {
    return $this->input_file;
  }

  public function getOutputFile()
  {
    return $this->output_file;
  }

  //funkce spoji parametry, ktere byly chybne rozdelene
  private function solveApostrofs($argv)
  {
    $argv = implode(" ", $argv);  //spojime do jednoho retezce
    $argArr = str_split($argv);   //prevedeme na pole
    $argv = array();

    $arg = "";
    $i = 0;
    while($i < count($argArr)) {
      if($argArr[$i] == "'") {
        $i++;
        while($i < count($argArr) and $argArr[$i] != "'") {
          $arg.=$argArr[$i];
          $i++;
        }
      }
      elseif($argArr[$i] == "\"") {
        $i++;
        while($i < count($argArr) and $argArr[$i] != "\"") {
          $arg.=$argArr[$i];
          $i++;
        }
      }
      elseif($argArr[$i] == " ") {
        if($arg != "") {
          $argv[] = $arg;
          $arg = "";
        }
      }
      else {
        $arg.=$argArr[$i];
      }
      $i++;
    }

    if($arg != "") {
      $argv[] = $arg;
    }
    return $argv;
  }

  private function arguments($args)
  {
    $longSwithes = array("help", "br", "nooverlap", "escape");
    $longParams = array("input", "output", "format");

    $shortSwithes = array("h" => "help", "b" => "br",
      "n" => "nooverlap", "e" => "escape");
    $shortParams = array("i" => "input", "o" => "output", "f" => "format");

    $ret = array(
      'params' => array(),
      'other' => array()
    );
    while(!empty($args)) {
      $par = $args[0];
      if(strpos($par, '--') === 0) {
        if(!$this->checkLongParam($ret['params'], $par, $longParams)) {
          if(!$this->checkLongSwitch($ret['params'], $par, $longSwithes)) {
            $ret['other'][$par] = true;
          }
        }
      }
      elseif(strpos($par, '-') === 0) {
        if(!$this->checkShortParam($ret['params'], $par, $shortParams)) {
          if(!$this->checkShortSwitch($ret['params'], $par, $shortSwithes)) {
            $ret['other'][$par] = true;
          }
        }
      }
      else {
        $ret['other'][$par] = true;
      }
      array_shift($args);
    }
    return $ret;
  }

  private function checkLongParam(&$params, $par, $allowed)
  {
    if(strlen($par) < 4) {
      return false;
    }

    //jde o dlouhy prepinac
    if(($iEql = strpos($par, "=")) !== false) {
      //jde o dlouhy prepinac s parametrem
      $paramName = substr($par, 2, $iEql - 2);
      if(in_array($paramName, $allowed)) {
        if(!isset($params[$paramName])) {
          $paramValue = trim(substr($par, $iEql + 1, strlen($par) - $iEql - 1), "'\"");
          $params[$paramName] = $paramValue;
          return true;
        }
        else {
          throw new Exception("Duplicate parameter '" . $paramName . "'", 1);
        }
      }
      else {
        throw new Exception("Unknown parameter '$paramName'", 1);
      }
    }
    return false;
  }

  private function checkLongSwitch(&$params, $arg, $allowed)
  {
    if(strlen($arg) < 3) {
      return false;
    }

    //jde o dlouhy prepinac bez parametru
    $paramName = substr($arg, 2, strlen($arg) - 2);
    if(in_array($paramName, $allowed)) {
      if(!isset($params[$paramName])) {
        $params[$paramName] = true;
        return true;
      }
      else {
        throw new Exception("Duplicate argument '" . $paramName . "'", 1);
      }
    }
    else {
      throw new Exception("Unknown argument '$paramName'", 1);
    }
    return false;
  }

  private function checkShortParam(&$params, $par, $allowed)
  {
    if(strlen($par) < 2) {
      return false;
    }

    if(($iEql = strpos($par, "=")) !== false) {
      //jde o kratky prepinac s parametrem
      $paramName = substr($par, 1, $iEql - 1);
      if(isset($allowed[$paramName])) {
        $paramName = $allowed[$paramName];
        if(!isset($params[$paramName])) {
          $paramValue = substr($par, $iEql + 1, strlen($par) - $iEql - 1);
          $params[$paramName] = trim($paramValue, "'\"");
          return true;
        }
        else {
          throw new Exception("Duplicate parameter '" . $paramName . "'", 1);
        }
      }
      else {
        throw new Exception("Unknown short parameter '$paramName'", 1);
      }
    }
    return false;
  }

  private function checkShortSwitch(&$params, $arg, $allowed)
  {
    if(strlen($arg) != 2) {
      //nejde o prepinac
      return false;
    }

    $paramChar = substr($arg, 1, 1);
    if(isset($allowed[$paramChar])) {
      $paramName = $allowed[$paramChar];
      if(!isset($params[$paramName])) {
        $params[$paramName] = true;
        return true;
      }
      else {
        throw new Exception("Duplicate argument '" . $paramChar . "'", 1);
      }
    }
    else {
      throw new Exception("Unknown argument '$paramChar'", 1);
    }
    return false;
  }

  private function safeCloseFile($file)
  {
    if($file != NULL) {
      fclose($file);
    }
  }

  function __destruct()
  {
    $this->safeCloseFile($this->getInputFile());
    $this->safeCloseFile($this->getOutputFile());
    $this->safeCloseFile($this->getFormatFile());
  }

}
