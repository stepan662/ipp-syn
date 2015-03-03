<?php

#SYN:xgrana02

/**
 * Naformatuje zdrojovy soubor podle predlohy
 *
 * @author stepan
 */
include_once 'format_mark.php';

class SourceFormatter
{

  private $format;
  private $in;
  private $marks = array();

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
  public function getSource($escHtml)
  {
    $marks = array();

    $wave = 0;

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


      //echo $fUnit->getRegExpr() . "\n";
      //projdeme vyhledane retezce a predame inserteru indexy
      if(isset($matches[0])) {
        foreach($matches[0] as $match) {
          if($match[0] != "") {
            $marks[] = new FormatMark($match[1], $wave, $fUnit->getFirstPart(), false);

            $marks[] = new FormatMark($match[1] + strlen($match[0]), $wave, $fUnit->getSecondPart(), true);
          }
        }
      }

      $wave++;
    }

    uasort($marks, "self::cmpMark");


    $out = "";

    $in = str_split($this->in);
    $index = 0;
    foreach($marks as $mark) {
      while($mark->index > $index) {
        if($escHtml) {
          $out.=self::escapeChar($in[$index]);
        }
        else {
          $out.=$in[$index];
        }
        $index++;
      }
      $out.=$mark->mark;
    }
    while($index < count($in)) {
      if($escHtml) {
        $out.=self::escapeChar($in[$index]);
      }
      else {
        $out.=$in[$index];
      }
      $index++;
    }


    return $out;
  }

  //rozhodne poradi dvou znacek podle indexu, vlny a unkocovani
  static function cmpMark($a, $b)
  {
    if($a->index == $b->index) {
      if($a->isEnd !== $b->isEnd) {
        return $a->isEnd ? -1 : 1;
      }
      elseif($a->isEnd === false) {
        return $a->wave < $b->wave ? -1 : 1;
      }
      else {
        return $a->wave < $b->wave ? 1 : -1;
      }
    }
    else {
      return $a->index < $b->index ? -1 : 1;
    }
  }

  static function escapeChar($char)
  {
    return htmlspecialchars($char, ENT_NOQUOTES);
  }

}
