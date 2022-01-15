<?php /** @noinspection PhpMissingParamTypeInspection */


namespace eftec;

/**
 * Class ValidationItem
 *
 * @package       eftec
 * @author        Jorge Castro Castillo
 * @version       2.0 2022-01-15
 * @copyright (c) Jorge Castro C. Licencia Dual LGLPV2 License y comercial.  https://github.com/EFTEC/ValidationOne
 * @see           https://github.com/EFTEC/ValidationOne
 */
class ValidationItem {
    /** @var string=['alpha','alphanum','between','betweenlen','contain','doc','domain','email','eq','ext','false','gt','gte','image','lt','lte','maxlen','maxsize','minlen','minsize','ne','notcontain','notnull','null','regexp','req','text','true','url','fn.*'][$i] */
    public $type;
    /** @var mixed value used for validation. It could be an array (between for example uses an array) */
    public $value;
    /** @var string|null Error message (if the condition is not meet) */
    public $msg;
    /** @var string=['error','warning','info','success'][$i] */
    public $level;

    /**
     * Tris constructor.
     *
     * @param string $type  =['alpha','alphanum','between','betweenlen','contain','doc','domain','email','eq','ext','false','gt','gte','image','lt','lte','maxlen','maxsize','minlen','minsize','ne','notcontain','notnull','null','regexp','req','text','true','url','fn.*'][$i]
     * @param string $msg   It uses sprintf, so you could use %s and %3$s
     * @param mixed  $value value used for validation. It could be an array (between for example uses an array)
     * @param string $level =['error','warning','info','success'][$i]
     */
    public function __construct($type, $msg = null, $value = null, $level = null) {
        $this->type = $type;
        $this->value = $value;
        $this->msg = $msg;
        $this->level = $level;
    }
}
