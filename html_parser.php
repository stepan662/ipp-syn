<?php

#SYN:xgrana02

/**
 * Provadi validaci html tagu
 *
 * @author stepan
 */
include_once 'html_scanner.php';

final class HtmlParser
{

  /**
   * Resi krizeni html tagu
   * 
   * @param &string $html vstupni html
   * @return string
   */
  public function validate(&$html)
  {
    $scanner = new HtmlScanner($html);
    $ret = "";                //vystupni retezec
    $open = array();          //zasobnik otevrenych tagu
    $suspend = array();       //zasobnik prerusenych tagu
    //scannerem rozsekame html na HtmlUnit (text nebo tag)
    while(($unit = $scanner->getUnit()) !== false)
    {
      if($unit->isEnd())
      {
        //jde o ukoncovaci tag - vyhledame v zasobniku otevrenych
        if(self::unit_in_array($unit, $open))
        {
          //pokud neni na vrcholu, musime ukoncit vsechny predchozi
          // a presunout je do prerusenych
          while(!$unit->isClosing($tmp = array_pop($open)))
          {
            $ret.=$tmp->getCloseTag();
            $suspend[] = $tmp;
          }
          $ret.=$tmp->getCloseTag();
        }
        elseif(self::unit_in_array($unit, $suspend))
        {
          //pokud je v seznamu prerusenych, tak je oba zahodime
          for($i = count($suspend) - 1; $i >= 0; $i--)
          {
            if($suspend[$i]->getName() == $unit->getName())
            {
              unset($suspend[$i]);
            }
          }
        }
      }
      elseif($unit->isBegin() and ! $unit->isClosed())
      {
        //pokud je oteviraci a neni samouzaviraci, dame do otevrenych
        $open[] = $unit;
        $ret.=$unit->getHtml();
      }
      else
      {
        //pokud jde o retezec, musime obnovit vsechny prerusene tagy
        // a pridat je do otevrenych
        while(!empty($suspend))
        {
          $tmp = array_pop($suspend);
          $ret.= $tmp->getHtml();
          $open[] = $tmp;
        }
        $ret.=$unit->getHtml();
      }
    }

    //budeme slusni uzavreme zbyle otevrene tagy
    // ale nemelo by se to stavat!!!
    while(!empty($open))
    {
      $tmp = array_pop($open);
      $ret.= $tmp->getHtml();
    }

    return $ret;
  }

  /**
   * Zjisti, jestli se jednotka nachazi v seznamu jednotek
   * @param HtmlUnit $unit Hledana Html jednotka 
   * @param array $arr Pole Html jednotek
   * @return boolean vraci true v pripade nalezeni
   */
  private function unit_in_array($unit, $arr)
  {
    foreach(array_reverse($arr) as $tmp)
    {
      if($tmp->getName() == $unit->getName())
        return true;
    }

    return false;
  }

}
