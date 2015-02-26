<?php

#SYN:xgrana02

/**
 * Hlavni soubor projektu ipp syn
 * Provadi nektere kontroly a spousti dalsi moduly
 * @author stepan
 */
include_once 'arguments.php';
include_once 'format_file_parser.php';
include_once 'source_formatter.php';
include_once 'html_parser.php';

function print_help()
{
  echo "tisknu napovedu\n";
}

main($argv);

function main($argv)
{
  mb_internal_encoding("utf-8");

  $STDERR = fopen('php://stderr', 'w+');

  //zpracovani argumentu programu
  $args;
  try
  {
    $args = new Arguments($argv);
  }
  catch(Exception $e)
  {
    fwrite($STDERR, $e->getMessage() . "\n");
    exit($e->getCode());
  }

  //vypis napovedy
  if($args->isHelp()) {
    print_help();
    return;
  }


  //zpracovani formatovaciho souboru
  //v regularnich vyrazech jsou take escapovany html znacky
  $formatArr = array();
  if($args->getFormatFile() !== null) {
    try
    {
      $formatArr = FormatFileParser::parse($args->getFormatFile());
    }
    catch(Exception $e)
    {
      fwrite($STDERR, $e->getMessage() . "\n");
      exit($e->getCode());
    }
  }


  //nacteni celeho zdrojoveho souboru a esapovani html znacek
  $file = "";
  $handle = $args->getInputFile();
  while(($line = fgets($handle)) !== false) {
    $file.= htmlspecialchars($line, ENT_NOQUOTES);
  }


  //formatovani souboru podle formatovaciho pole
  $sourceFormatter = new SourceFormatter($formatArr, $file);
  $out = $file;
  if($args->getFormatFile() !== null) {
    try
    {
      $out = $sourceFormatter->getSource();
    }
    catch(Exception $e)
    {
      fwrite($STDERR, $e->getMessage() . "\n");
      exit(4);
    }
  }

  //validace html znacek
  if($args->isValidate()) {
    $out = HtmlParser::validate($out);
  }

  //prevedeni html znacek zpet, v pripade ze nemaji byt escapovany
  if(!$args->isEscape()) {
    $out = htmlspecialchars_decode($out, ENT_NOQUOTES);
  }

  //vlozeni html novych radku
  if($args->isBr()) {
    $out = str_replace("\n", "<br />\n", $out);
  }

  //vypis do souboru
  fwrite($args->getOutputFile(), $out);


  //timto take zavreme otevrene soubory
  unset($args);
}
