<?php

#SYN:xgrana02

/**
 * Obsahuje cast html kodu text nebo tag
 * @author stepan
 */
class HtmlUnit
{

  private $html;      //cely tag - '< .... >'
  private $type;      //b - begin / e - end / t - text
  private $name;      //jmeno tagu

  /**
   * Inicializace Html jednotky
   * Pozor spravnost se nekontroluje!!
   * @param type $html Cely tag i s vnitrnimi hodnotami napr '<font size=3>'
   * @param type $type Typ tagu: t - text, b - pocatecni, e - uzaviraci
   * @param type $name Pouze nazev tagu napr 'font'
   */
  function __construct($html, $type, $name = "")
  {
    $this->type = $type;
    $this->html = $html;
    $this->name = $name;
  }

  /**
   * Je tag oteviraci?
   * @return bool
   */
  function isBegin()
  {
    return ($this->type === "b");
  }

  /**
   * Je tag uzaviraci?
   * @return bool
   */
  function isEnd()
  {
    return ($this->type === "e");
  }

  /**
   * Jde pouze o text?
   * @return bool
   */
  function isText()
  {
    return ($this->type === "t");
  }

  /**
   * Je tag samouzaviraci?
   * @return bool
   */
  function isClosed()
  {
    return ($this->type == "t");
  }

  /**
   * Overi, jestli tento tag uzavira jiny
   * @param HtmlUnit $unit jiny tag
   * @return bool
   */
  function isClosing($unit)
  {
    return ($this->isEnd() and $unit->getName() === $this->name);
  }

  /**
   * Ziska jmeno tagu
   * @return string
   */
  function getName()
  {
    return $this->name;
  }

  /**
   * Ziska cely tag
   * @return string
   */
  function getHtml()
  {
    return $this->html;
  }

  /**
   * Pokud jde o oteviraci tag, vraci jeho uzaviraci
   * @return string
   */
  function getCloseTag()
  {
    if($this->type == 'b')
    {
      return "</" . $this->name . ">";
    }
    else
    {
      return "";
    }
  }

}
