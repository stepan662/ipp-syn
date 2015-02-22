<?php

#SYN:xgrana02

/**
 * Formatovaci jednotka - obsahuje regularni vyraz
 * a html znacky
 *
 * @author stepan
 */

include_once 'html_unit.php';

class FormatUnit
{
  private $reg_expr;      //regularni vyraz
  private $tags;          //pole HtmlUnit
  
  /**
   * Inicializace nastavenim regularniho vyrazu a html jednotek
   * @param string $r_expr Regularni vyraz
   * @param array $tags Pole jednotek HtmlUnit
   */
  public function __construct($r_expr, $tags)
  {
    $this->reg_expr = $r_expr;
    $this->tags = $tags;
  }
  
  /**
   * Vraci regularni vyraz
   * @return string
   */
  public function getRegExpr()
  {
    return $this->reg_expr;
  }
  
  /**
   * Vraci prvni cast formatovaci znacky
   * @return string
   */
  public function getFirstPart()
  {
    $ret = "";
    foreach($this->tags as $tag)
    {
      $ret.=$tag->getHtml();
    }
    return $ret;
  }
  
  /**
   * Vraci druhou cast formatovaci znacky
   * @return string
   */
  public function getSecondPart()
  {
    $ret = "";
    foreach(array_reverse($this->tags) as $tag)
    {
      $ret.=$tag->getCloseTag();
    }
    return $ret;
  }
}
