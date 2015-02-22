<?php

#SYN:xgrana02

/**
 * Provadi vkladani html tagu do zdrojoveho souboru
 * @author stepan
 */

include_once 'format_unit.php';

class HtmlInserter
{

  private $iText;       //index do textu
  private $iReal;       //skutecny index i s html tagy
  private $source;      //zdrojovy soubor, kde jsou provadeny zmeny

  /**
   * Nastavime vystupni soubor a vynulujeme indexy
   * @param string $source vystupni retezec
   */
  public function __construct(&$source)
  {
    $this->source = &$source;
    $this->resetCounters();
  }

  /**
   * Obali danou cast textu formatovacimi znackami
   * 
   * @param FormatUnit $format Formatovaci informace
   * @param int $begin zacatek textu
   * @param int $length delka obal. textu
   * @throws Exception V pripade spatneho indexu
   */
  public function insert(&$format, $begin, $length)
  {
    if($begin < $this->iText)
    {
      throw new Exception("Index $begin is lower than $this->iText");
    }
    
    $this->findRealIndexLast($begin);
    
    $this->insertToIndex($this->iReal, $format->getFirstPart());
    
    $this->findRealIndexFirst($this->iText+$length);
    
    $this->insertToIndex($this->iReal, $format->getSecondPart());
    
    $out = &$this->source;
  }
  
  /**
   * Na dany index vlozi retezec
   * 
   * @param int $index
   * @param string $string
   */
  private function insertToIndex($index, $string)
  {
    $this->source = substr_replace($this->source, $string, $index, 0);
    $this->iReal+=strlen($string);
  }

  /**
   * Najde posledni index (az za html kodem), ktery vyhovuje indexu v textu
   * 
   * @param int $index
   */
  private function findRealIndexLast($index)
  {
    $state = "inText";
    $in = str_split($this->source);
    while($this->iText <= $index)
    {
      $char = $in[$this->iReal];
      //echo "$state($char)\t";
      //echo "iText: $this->iText, iReal: $this->iReal \t";
      //echo $this->source."\n";

      switch($state)
      {
        case "inText":
          if($char == "<")
          {
            $this->iReal++;
            $state = "inTag";
          }
          else
          {
            $this->iReal++;
            $this->iText++;
          }
          break;
        case "inTag":
          if($char == ">")
          {
            $this->iReal++;
            $state = "inText";
          }
          else
          {
            $this->iReal++;
          }
          break;
      }
    }
    $this->iReal--;
    if($index != $this->iText)
    {
      $this->iText--;
    }
  }
  /**
   * Najde prvni index (pred html kodem), ktery vyhovuje indexu v textu
   * 
   * @param int $index
   */
  private function findRealIndexFirst($index)
  {
    $state = "inText";
    $in = str_split($this->source);
    while($this->iText < $index)
    {
      $char = $in[$this->iReal];
      //echo "$state($char)\t";
      //echo "iText: $this->iText, iReal: $this->iReal \t";
      //echo $this->source."\n";

      switch($state)
      {
        case "inText":
          if($char == "<")
          {
            $this->iReal++;
            $state = "inTag";
          }
          else
          {
            $this->iReal++;
            $this->iText++;
          }
          break;
        case "inTag":
          if($char == ">")
          {
            $this->iReal++;
            $state = "inText";
          }
          else
          {
            $this->iReal++;
          }
          break;
      }
    }
  }

  /**
   * vynuluje pocitadla indexu
   */
  public function resetCounters()
  {
    $this->iText = 0;
    $this->iReal = 0;
  }

}
