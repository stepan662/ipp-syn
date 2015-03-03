<?php

#SYN:xgrana02

/**
 * Obsahuje formatovaci znacku s indexem s nasledujicimi parametry:
 * index - index ve zdrojovem kodu, kam se ma umistit
 * wave  - vlna, ve ktere byla znacka pridana
 * mark  - obsah znacky
 * isEnd - znaci, jestli jde o koncovou znacku
 *
 * @author stepan
 */
class FormatMark
{
  var $index;
  var $wave;
  var $mark;
  var $isEnd;
  
  function __construct($index, $wave, $mark, $isEnd)
  {
    $this->index = $index;
    $this->wave = $wave;
    $this->mark = $mark;
    $this->isEnd = $isEnd;
  }
}
