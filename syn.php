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
  echo "Projekt do předmětu IPP - zvýrazňování syntaxe\n\n"
      ."Volitelné argumenty:\n"
      ."\t--format=filename určení formátovacího souboru.\n"
      ."\t\tSoubor musí být v následujícím formátu:\n"
      ."\t\t\t<regularni vyraz><tabulator><formatovani><novy radek>\n"
      ."\t\t\t...\n"
      ."\t--input=filename určení vstupního souboru\n"
      ."\t--output=filename určení výstupního souboru\n"
      ."\t--br přidá element <br /> na konec každého řádku\n"
      ."\t--nooverlap validuje křížení html značek\n"
      ."\t--escape escapuje html značky, které jsou ve vstupním souboru\n";
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
    $file.= $line;
  }


  //formatovani souboru podle formatovaciho pole
  $sourceFormatter = new SourceFormatter($formatArr, $file);
  $out = $file;
  if($args->getFormatFile() !== null) {
    try
    {
      $out = $sourceFormatter->getSource($args->isEscape());
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

  //vlozeni html novych radku
  if($args->isBr()) {
    $out = str_replace("\n", "<br />\n", $out);
  }

  //vypis do souboru
  fwrite($args->getOutputFile(), $out);


  //timto take zavreme otevrene soubory
  unset($args);
}
