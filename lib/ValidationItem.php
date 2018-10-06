<?php

namespace eftec;

/**
 * Class ValidationItem
 * @package eftec
 * @author Jorge Castro Castillo
 * @version 1.5 20181006
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see https://github.com/EFTEC/ValidationOne
 */
class ValidationItem
{
    var $type;
    var $value;
    var $msg;
    var $level;

    /**
     * Tris constructor.
     * @param string $type
     * @param string $msg It uses sprintf, so you could use %s and %3$s
     * @param mixed $value
     * @param string $level (error,warning,info,success)
     */
    public function __construct($type, $msg=null, $value=null, $level=null)
    {
        $this->type = $type;
        $this->value = $value;
        $this->msg = $msg;
        $this->level = $level;
    }
}