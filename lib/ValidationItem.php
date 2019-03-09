<?php

namespace eftec;

/**
 * Class ValidationItem
 * @package eftec
 * @author Jorge Castro Castillo
 * @version 1.14 2019-mar-8
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see https://github.com/EFTEC/ValidationOne
 */
class ValidationItem
{
	/** @var string=['alpha','alphanum','between','betweenlen','contain','doc','domain','email','eq','ext','false','gt','gte','image','lt','lte','maxlen','maxsize','minlen','minsize','ne','notcontain','notnull','null','regexp','req','text','true','url','fn.*'][$i]  */
    var $type;
    /** @var mixed value used for validation. It could be an array (between for example uses an array)  */
    var $value;
    /** @var string|null Error message (if the condition is not meet) */
    var $msg;
    /** @var string=['error','warning','info','success'][$i]  */
    var $level;

    /**
     * Tris constructor.
     * @param string $type=['alpha','alphanum','between','betweenlen','contain','doc','domain','email','eq','ext','false','gt','gte','image','lt','lte','maxlen','maxsize','minlen','minsize','ne','notcontain','notnull','null','regexp','req','text','true','url','fn.*'][$i]
     * @param string $msg It uses sprintf, so you could use %s and %3$s
     * @param mixed $value  value used for validation. It could be an array (between for example uses an array)
     * @param string $level=['error','warning','info','success'][$i]
     */
    public function __construct($type, $msg=null, $value=null, $level=null)
    {
        $this->type = $type;
        $this->value = $value;
        $this->msg = $msg;
        $this->level = $level;
    }
}