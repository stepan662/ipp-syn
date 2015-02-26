<?php

#SYN:xgrana02

/**
 * Naformatuje zdrojovy soubor podle predlohy
 *
 * @author stepan
 */
include_once 'html_inserter.php';

class SourceFormatter
{

  private $format;
  private $in;

  /**
   * Nastavime formatovaci a vstupni soubor
   * @param aray $format formatovaci soubor
   * @param &string $in vstupni soubor
   */
  public function __construct(&$format, &$in)
  {
    $this->format = &$format;
    $this->in = &$in;
  }

  /**
   * Provede formatovani a vrati vysledek
   * @return string
   */
  public function getSource()
  {
    $out = $this->in;
    $inserter = new HtmlInserter($out);

    foreach($this->format as $fUnit) {
      //zachytime chybu privykonavani regularniho vyrazu
      $errRep = error_reporting(0);

      //vyhledani odpovidajicich retezcu pomoci regularniho vyrazu
      if(preg_match_all($fUnit->getRegExpr() . "u", $this->in, $matches, PREG_OFFSET_CAPTURE) === false) {
        $msg = error_get_last();
        throw new Exception("Regular expression is "
        . "not valid\n" . $msg['message'], 4);
      }
      error_reporting($errRep);

      //vynulovani indexu inserteru
      $inserter->resetCounters();

      //echo $fUnit->getRegExpr() . "\n";
      //projdeme vyhledane retezce a predame inserteru indexy
      if(isset($matches[0])) {
        foreach($matches[0] as $match) {
          if($match[0] != "") {
            $inserter->insert($fUnit, $match[1], strlen($match[0]));
          }
        }
      }
    }



    return $out;
  }

}
