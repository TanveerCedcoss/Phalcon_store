<?php

namespace App\Components;

use Phalcon\Escaper;

/**
 * Escaper class
 * @package Product
 * @author Tanveer <tanveer@cedcoss.com>
 */
class MyEscaper
{
    /**
     * this function get a variable and perform html escaper on it
     * and then returns the variable formed after the escaper on 
     * @param [char] $val
     * @return char
     */
    public function sanitize($val)
    {
        $escaper = new Escaper();
        $santzVal = $escaper->escapeHtml($val);
        return $santzVal;
        
    }
}