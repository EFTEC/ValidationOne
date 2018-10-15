<?php

namespace eftec;

use DateTime;
use ReflectionMethod;

/** @var string  sometimes we want to sets an empty as empty. For example <select><option> this nullval is equals to null */
if (!defined("NULLVAL")) define('NULLVAL','__nullval__');

/**
 * Class Validation
 * @package eftec
 * @author Jorge Castro Castillo
 * @version 1.7 20181015
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see https://github.com/EFTEC/ValidationOne
 */
class ValidationOne
{
    public static $dateShort='d/m/Y';
    public static $dateLong='d/m/Y H:i:s';
    /** @var MessageList */
    var $messageList;

    var $prefix='';
    //private $NUMARR='integer,unixtime,boolean,decimal,float';
    private $STRARR='varchar,string';
    private $DATARR='date,datetime';

    //<editor-fold desc="chain variables">
    /** @var mixed default value */
    private $default=null;
    /** @var string integer,unixtime,boolean,decimal,float,varchar,string,date,datetime */
    private $type='string';
    /** @var int 0=number,1=string,2=date,3=boolean */
    private $typeFam=1;
    /** @var bool if the value is an array or not */
    private $isArray=false;
    /** @var bool if true then the the errors from id[0],id[1] ared stored in "idx" */
    private $isArrayFlat=false;

    private $hasMessage=false;
    /** @var bool if the validation fails then it returns the default value */
    private $ifFailThenDefault=false;
    /** @var bool It override previous errors (for the "id" used) */
    private $override=false;
    /** @var bool If true then the field is required otherwise it generates an error */
    private $required=false;
    /** @var string It's a friendly id used to replace the "id" used in message. For example: "id customer" instead of "idcustomer" */
    private $friendId=null;
    /** @var ValidationItem[]  */
    private $validation=[];

    private $defaultIfFail=false;
    private $defaultRequired=false;

    //</editor-fold>

    /**
     * Validation constructor.
     * @param string $prefix The prefix is used to fetch values. For example the prefix "frm_"
     */
    public function __construct($prefix='')
    {
        $this->prefix=$prefix;
        if (function_exists('messages')) {
            $this->messageList=messages();
        } else {
            $this->messageList=new MessageList();
        }
        $this->resetChain();
    }


    //<editor-fold desc="chain commands">
    /**
     * It sets a default value. It could be used as follow:
     * a) if the value is not set and it's not required (by default, it's not required), then it sets this value. otherwise null<br>
     * b) if the value is not set and it's required, then it returns an error and it sets this value, otherwise null<br>
     * c) if the value is not set and it's an array, then it sets a single value (not the whole array).
     * @param mixed $value
     * @param bool|null $ifFailThenDefault  True if the system returns the default value if error.
     * @return ValidationOne $this
     */
    public function def($value, $ifFailThenDefault=null) {
        $this->default=$value;
        if ($ifFailThenDefault!==null) $this->ifFailThenDefault=$ifFailThenDefault;
        return $this;
    }

    /**
     * It sets the default value based in the type of data. <br>
     * If the type of data is not specified, then it sets the value to string ''.<br>
     * number = 0 (-1 for negative)<br>
     * string = '' (null for negative)<br>
     * date = DateTime() (null for negative)<br>
     * boolean = true (false for negative)<br>
     * @param bool $negative if true then it returns the negative default value.
     * @return ValidationOne $this
     */
    public function defNatural($negative=false) {
        switch ($this->typeFam) {
            case 0:
                $this->default=(!$negative)?0:-1;
                break;
            case 1:
                $this->default=(!$negative)?'':null;
                break;
            case 2:
                $this->default=(!$negative)?new DateTime():null;
                break;
            case 3:
                $this->default=(!$negative)?true:false;
                break;
        }
        return $this;
    }

    /**
     * It configures all the next chains with those default values.<br>
     * For example, we could force to be required always.
     * @param bool $ifFailThenDefault
     * @param bool $ifRequired The field must be fetched, otherwise it generates an error
     */
    public function configChain($ifFailThenDefault=false,$ifRequired=false) {
        $this->defaultIfFail=$ifFailThenDefault;
        $this->defaultRequired=$ifRequired;
    }

    /**
     * Sets the fetch for an array. It's not required for set()<br>
     * If $flat is true then then errors are returned as a flat array (idx instead of idx[0],idx[1])
     * @param bool $flat
     * @return ValidationOne $this
     */
    public function isArray($flat=false) {
        $this->isArray=true;
        $this->isArrayFlat=$flat;
        return $this;
    }

    /**
     * @param bool $ifFailDefault
     * @return ValidationOne ValidationOne
     */
    public function ifFailThenDefault($ifFailDefault=true) {
        $this->ifFailThenDefault=$ifFailDefault;
        return $this;
    }

    /**
     * If override previous errors
     * @param bool $override
     * @return ValidationOne
     */
    public function override($override=true) {
        $this->override=$override;
        return $this;
    }

    /**
     * If it's unable to fetch then it generates an error.<br>
     * However, by default it also returns the default value.
     * @param bool $required
     * @return ValidationOne
     * @see ValidationOne::def()
     */
    public function required($required=true) {
        $this->required=$required;
        return $this;
    }

    /**
     * It's a friendly id used to replace the "id" used in message. For example: "id customer" instead of "idcustomer"
     * @param $id
     * @return ValidationOne
     */
    public function friendId($id) {
        $this->friendId=$id;
        return $this;
    }

    /**
     * It returns the number of the family.
     * @param string $type integer,unixtime,boolean,decimal,float,varchar,string,date,datetime
     * @return int 1=string,2=date,3=boolean,0=number
     */
    private function getTypeFamily($type) {
        switch (1==1) {
            case (strpos($this->STRARR,$type)!==false):
                $r=1; // string
                break;
            case (strpos($this->DATARR,$type)!==false):
                $r=2; // date
                break;
            case ($type=='boolean'):
                $r=3; // boolean
                break;
            default:
                $r=0; // number
        }
        return $r;
    }
    /**
     * @param string $type integer,unixtime,boolean,decimal,float,varchar,string,date,datetime
     * @return ValidationOne $this
     */
    public function type($type) {
        $this->typeFam=$this->getTypeFamily($type);
        $this->type=$type;
        return $this;
    }

    /**
     * @param string $type
     *      number:req,eq,ne,gt,lt,gte,lte,between<br>
     *      string:req,eq,ne,minlen,maxlen,betweenlen,notnull<br>
     *      date:req,eq,ne,gt,lt,gte,lte,between<br>
     *      boolean:req,eq,ne,true,false<br>
     *      <b>function:</b><br>
     *          fn.static.Class.methodstatic<br>
     *          fn.global.function<br>
     *          fn.object.Class.method where object is a global $object<br>
     *          fn.class.Class.method<br>
     *          fn.class.\namespace\Class.method<br>
     * @param string $message<br>
     *      Message could uses the next variables '%field','%realfield','%value','%comp','%first','%second'
     * @param null $value
     * @param string $level (error,warning,info,success)
     * @return ValidationOne
     */
    public function condition($type, $message="", $value=null, $level='error') {
        $this->validation[]=new ValidationItem($type,$message,$value,$level);
        return $this;

    }

    /**
     * It resets the chain (if any)
     * It also reset any validating pending to be executed.
     */
    public function resetChain() {

        $this->default=null;
        $this->type='string'; // it's important, string is the default value because it's not processed.
        $this->typeFam=1; // string
        $this->isArray=false;
        $this->isArrayFlat=false;
        $this->hasMessage=false;
        $this->ifFailThenDefault=$this->defaultIfFail;
        $this->validation=[];

        $this->override=false;
        $this->resetValidation();
        $this->required=$this->defaultRequired;
        $this->friendId=null;
    }
    //</editor-fold>
    /**
     * You could add a message (including errors,warning..) and store in a $id
     * It is a wrapper of $this->messageList->addItem
     * @param string $id Identified of the message (where the message will be stored
     * @param string $message message to show. Example: 'the value is incorrect'
     * @param string $level = error|warning|info|success
     */
    public function addMessage($id,$message,$level='error') {
        $this->messageList->addItem($id,$message,$level);
    }

    /**
     * It cleans the stacked validations. It doesn't delete the errors.
     */
    public function resetValidation() {
        $this->validation=array();
    }

    //<editor-fold desc="fetch and end of chain commands">

    /**
     * Returns null if the value is not present, false if the value is incorrect and the value if its correct
     * @param $field
     * @param bool $array
     * @param string $fileTmp
     * @param string $fileNew
     * @return array|int|null|string
     * @internal param $folder
     * @internal param string $type
     */
    public static function getFile($field,$array=false,&$fileTmp="",&$fileNew="")
    {
        if (!$array) {
            $fileNew=@$_FILES[$field]['name'];
            if ($fileNew!="") {
                // its uploading a file
                $fileTmp=@$_FILES[$field]['tmp_name'];
                $filename=@$_POST[$field.'_file']; // previous filename if any
                return $filename;
            } else {
                $filename=@$_POST[$field.'_file']; // previous filename if any
                $fileTmp='';
                $fileNew='';
                return $filename;
            }
        } else {
            $c=count($_FILES[$field]['name']);
            $filenames=array();
            for($i=0;$i<$c;$i++) {
                $filename=@$_FILES[$field]['name'][$i];
                if ($filename!="") {
                    $filename=$filename.'&&'.@$_FILES[$field]['tmp_name'][$i].'&&'.@$_POST[$field.'_file'][$i];
                } else {
                    $filename='&&'.'&&'.@$_POST[$field.'_file'][$i];
                }
                $filenames[]=$filename;
            }
            return $filenames;
        }
    }
    public function get($field,$msg=null) {
        $fieldId=$this->prefix.$field;
        $r=$this->getField($fieldId,INPUT_GET,$msg);
        return $this->utilFetch($r,$fieldId);
    }
    public function post($field,$msg=null) {
        $fieldId=$this->prefix.$field;
        $r=$this->getField($fieldId,INPUT_POST,$msg);
        return $this->utilFetch($r,$fieldId);
    }
    public function request($field,$msg=null) {
        $fieldId=$this->prefix.$field;
        $r=$this->getField($fieldId,INPUT_REQUEST,$msg);
        return $this->utilFetch($r,$fieldId);
    }

    /**
     * It fetches a value.
     * @param int $inputType INPUT_POST|INPUT_GET|INPUT_REQUEST
     * @param string $field
     * @param null|string $msg
     * @return mixed
     */
    public function fetch($inputType,$field,$msg=null) {
        $fieldId=$this->prefix.$field;
        $r=$this->getField($fieldId,$inputType,$msg);
        return $this->utilFetch($r,$fieldId);
    }

    private function utilFetch($r,$fieldId) {
        if ($this->isArray) {
            if (is_array($r)) {
                foreach ($r as $items) {
                    $this->runConditions($items, $fieldId);
                }
            }
        } else {
            $this->runConditions($r,$fieldId);
        }

        if ($this->ifFailThenDefault) {
            if ($this->messageList->errorcount)
                $r=$this->default;
        }
        $this->resetChain();
        return $r;
    }
    public function set($value,$fieldId="setfield",$msg="") {
        if ($this->override) {
            $this->messageList->items[$fieldId]=new MessageItem();
        }

        if (is_array($value)) {
            foreach($value as $key=>&$v) {
                $currentField=($this->isArrayFlat)?$fieldId:$fieldId."[".$key."]";
                $v=$this->basicValidation($v,$currentField,$msg);
                $this->runConditions($v,$currentField);
            }
        } else {
            $this->runConditions($value,$fieldId);
        }
        $this->resetChain();
        return $value;
    }
    //</editor-fold>

    //<editor-fold desc="conditions">
    /**
     * @param $r
     * @param ValidationItem $cond
     * @param $fail
     * @param $genMsg
     */
    private function runNumericCondition($r,$cond,&$fail,&$genMsg) {
        switch ($cond->type) {
            case 'req':
                if (!$r) {
                    $fail = true;
                    $genMsg = '%field is required';
                }
                break;
            case 'lt':
                if ($r >= $cond->value) {
                    $fail = true;
                    $genMsg = '%field is great or equal than %comp';
                }
                break;
            case 'lte':
                if ($r > $cond->value) {
                    $fail = true;
                    $genMsg = '%field is great than %comp';
                }
                break;
            case 'gt':
                if ($r <= $cond->value) {
                    $fail = true;
                    $genMsg = '%field is less or equal than %comp';
                }
                break;
            case 'eq':
                if ($r != $cond->value) {
                    $fail = true;
                    $genMsg = '%field is not equals than %comp';
                }
                break;
            case 'ne':
                if ($r == $cond->value) {
                    $fail = true;
                    $genMsg = '%field is equals than %comp';
                }
                break;
            case 'gte':
                if ($r <= $cond->value) {
                    $fail = true;
                    $genMsg = '%field is less than %comp';
                }
                break;
            case 'between':
                if ($r < @$cond->value[0] || $r > @$cond->value[1]) {
                    $fail = true;
                    $genMsg = '%field is not between ' . @$cond->value[0] . " and " . @$cond->value[1];
                }
                break;
            case 'notnull':
                break;
        }
    }
    /**
     * @param $r
     * @param ValidationItem $cond
     * @param $fail
     * @param $genMsg
     */
    private function runStringCondition($r,$cond,&$fail,&$genMsg) {
        switch ($cond->type) {
            case 'req':
                if (!$r) {
                    $fail = true;
                    $genMsg = '%field is required';
                }
                break;
            case 'eq':
                if ($r != $cond->value) {
                    $fail = true;
                    $genMsg = '%field is not equals than %comp';
                }
                break;
            case 'ne':
                if ($r == $cond->value) {
                    $fail = true;
                    $genMsg = '%field is equals than %comp';
                }
                break;
            case 'minlen':
                if (strlen($r) < $cond->value) {
                    $fail = true;
                    $genMsg = '%field size is less than %comp';
                }
                break;
            case 'maxlen':
                if (strlen($r) > $cond->value) {
                    $fail = true;
                    $genMsg = '%field size is great than %comp';
                }
                break;
            case 'betweenlen':
                if (strlen($r) < $cond->value[0] || strlen($r) > $cond->value[1]) {
                    $fail = true;
                    $genMsg = '%field size is not between %first and %second ';
                }
                break;
            case 'notnull':
                if ($r===null) {
                    $fail = true;
                    $genMsg = '%field is null ';
                }
                break;
            default:
                trigger_error("type not defined {$cond->type} for string");
        }

    }
    /**
     * @param $r
     * @param ValidationItem $cond
     * @param $fail
     * @param $genMsg
     */
    private function runDateCondition($r,$cond,&$fail,&$genMsg) {
        switch ($cond->type) {
            case 'req':
                if (!$r) {
                    $fail = true;
                    $genMsg = '%field is required';
                }
                break;
            case 'lt':
                if ($r >= $cond->value) {
                    $fail = true;
                    $genMsg = '%field is great or equal than %comp';
                }
                break;
            case 'lte':
                if ($r > $cond->value) {
                    $fail = true;
                    $genMsg = '%field is great than %comp';
                }
                break;
            case 'gt':
                if ($r <= $cond->value) {
                    $fail = true;
                    $genMsg = '%field is less or equal than %comp';
                }
                break;
            case 'eq':
                if ($r != $cond->value) {
                    $fail = true;
                    $genMsg = '%field is not equals than %comp';
                }
                break;
            case 'ne':
                if ($r == $cond->value) {
                    $fail = true;
                    $genMsg = '%field is equals than %comp';
                }
                break;
            case 'gte':
                if ($r <= $cond->value) {
                    $fail = true;
                    $genMsg = '%field is less than %comp';
                }
                break;
            case 'between':
                if ($r < @$cond->value[0] || $r > @$cond->value[1]) {
                    $fail = true;
                    $genMsg = '%field is not between ' . @$cond->value[0] . " and " . @$cond->value[1];
                }
                break;
        }

    }
    /**
     * @param $r
     * @param ValidationItem $cond
     * @param $fail
     * @param $genMsg
     */
    private function runBoolCondition($r,$cond,&$fail,&$genMsg) {
        switch ($cond->type) {
            case 'req':
                if (!$r) {
                    $fail = true;
                    $genMsg = '%field is required';
                }
                break;
            case 'eq':
                if ($r != $cond->value) {
                    $fail = true;
                    $genMsg = '%field is not equals than %comp';
                }
                break;
            case 'ne':
                if ($r == $cond->value) {
                    $fail = true;
                    $genMsg = '%field is equals than %comp';
                }
                break;
            case 'true':
                if ($r) {
                    $fail = true;
                    $genMsg = '%field is not true';
                }
                break;
            case 'false':
                if (!$r) {
                    $fail = true;
                    $genMsg = '%field is not false';
                }
                break;
        }

    }
    /**
     * @param $r
     * @param ValidationItem $cond
     * @param $fail
     * @param $genMsg
     */
    private function runFnCondition($r,$cond,&$fail,&$genMsg) {
        // is a function
        $arr=explode(".",$cond->type);
        switch ($arr[1]) {
            case 'static':
                // fn.static.Class.method
                try {
                    $reflectionMethod = new ReflectionMethod($arr[2], $arr[3]);
                    $fail=!$reflectionMethod->invoke(null, $r,$cond->value);
                } catch (\Exception $e) {
                    $fail=true;
                    $genMsg=$e->getMessage();
                }
                break;
            case 'global':
                // fn.global.method
                try {
                    $fail=!@call_user_func($arr[2], $r,$cond->value);
                } catch (\Exception $e) {
                    $fail=true;
                    $genMsg=$e->getMessage();
                    var_dump($genMsg);
                }
                break;
            case 'object':
                //  0.     1.   2.     3
                // fn.object.$arr.method
                try {
                    if (!isset($GLOBALS[$arr[2]])) {
                        throw new \Exception("variable {$arr[2]} not defined as global");
                    }
                    $obj=$GLOBALS[$arr[2]];
                    $reflectionMethod = new ReflectionMethod(get_class($obj), $arr[3]);
                    $fail=!$reflectionMethod->invoke($obj, $r,$cond->value);
                } catch (\Exception $e) {
                    $fail=true;
                    $genMsg=$e->getMessage();
                }
                break;
            case 'class':
                //  0.     1.   2.     3
                // fn.class.ClassName.method
                try {
                    $className=$arr[2];
                    if (function_exists('get'.$className)) {
                        // we try to call the function getClass();
                        $obj=call_user_func('get'.$className);
                        $reflectionMethod = new ReflectionMethod(null, 'get'.$className);
                        $called=$reflectionMethod->invoke(null);
                        if ($called===null || $called===false) {
                            throw new \Exception("unable to call injection");
                        }
                    } else {
                        $obj=new $className();
                    }
                    $reflectionMethod = new ReflectionMethod($className, $arr[3]);
                    $fail=!$reflectionMethod->invoke($obj, $r,$cond->value);
                } catch (\Exception $e) {
                    $fail=true;
                    $genMsg=$e->getMessage();
                }
                break;
            default:
                trigger_error("validation fn not defined");
        }
    }
    private function runConditions($r, $fieldId) {
        $genMsg='';
        foreach($this->validation as $cond) {
            $fail=false;

            if (strpos($cond->type,"fn.")===0) {
                $this->runFnCondition($r,$cond,$fail,$genMsg);
            } else {
                switch ($this->typeFam) {
                    case 0: // number
                        $this->runNumericCondition($r,$cond,$fail,$genMsg);
                        break;
                    case 1: // string
                        $this->runStringCondition($r,$cond,$fail,$genMsg);
                        break;
                    case 2: // date
                        $this->runDateCondition($r,$cond,$fail,$genMsg);
                        break;
                    case 3: // bool
                        $this->runBoolCondition($r,$cond,$fail,$genMsg);
                        break;
                } // switch
            }
            if ($fail) {
                $this->addMessageInternal($cond->msg,$genMsg,$fieldId,$r,$cond->value, $cond->level);
            }
        }
    }
    //</editor-fold>

    /**
     * Returns null if the value is not present, false if the value is incorrect and the value if its correct
     * @param $field
     * @param int|string $inputType INPUT_REQUEST|INPUT_POST|INPUT_GET or it could be the value (for set)
     * @param null $msg
     * @return array|mixed|null
     */
    private function getField($field,$inputType=INPUT_REQUEST,$msg=null) {
        $r=null;

        switch ($inputType) {
            case INPUT_POST:
                if (!isset($_POST[$field])) {
                    if ($this->required) $this->addMessageInternal($msg,"Field is missing",$field,"","",'error');
                    return $this->default;
                }
                $r=$_POST[$field];
                $r=($r===NULLVAL)?null:$r;
                break;
            case INPUT_GET:
                if (!isset($_GET[$field])) {
                    if ($this->required) $this->addMessageInternal($msg,"Field is missing",$field,"","",'error');
                    return $this->default;
                }
                $r=$_GET[$field];
                $r=($r===NULLVAL) ?null:$r;
                break;
            case INPUT_REQUEST:
                if (isset($_POST[$field]) ) {
                    $r=$_POST[$field];
                }  else {
                    if (!isset($_GET[$field]) ) {
                        if ($this->required) $this->addMessageInternal($msg,"Field is missing",$field,"","",'error');
                        return $this->default;
                    }
                    $r=$_GET[$field];
                    $r=($r===NULLVAL) ?null:$r;
                }
                break;
            default:
                $r=$inputType;
        }
        if (!$this->isArray) {
            return $this->basicValidation($r, $field, $msg);
        } else {
            foreach($r as $key=>&$v) {
                $currentField=($this->isArrayFlat)?$field:$field."[".$key."]";
                $v=$this->basicValidation($v,$currentField,$msg);
            }
            return $r;
        }
    }
    /**
     * @param string $value
     * @param string $field
     * @param string $msg
     * @return bool|DateTime|float|int|mixed|null
     */
    private function basicValidation($value, $field, $msg="") {
        switch($this->type) {
            case 'integer':
            case 'unixtime':
                if (!is_numeric($value)) {
                    $this->hasMessage=true;
                    $this->addMessageInternal($msg,'%field is not numeric',$field,$value,null,'error');
                    return null;
                }
                return (int)$value;
                break;
            case 'boolean':
                return (bool)$value;
                break;
            case 'decimal':
                if (!is_numeric($value)) {
                    $this->hasMessage=true;
                    $this->addMessageInternal($msg,'$field is not decimal',$field,$value,null,'error');
                    return null;
                }
                return (double)$value;
                break;
            case 'float':
                if (!is_numeric($value)) {
                    $this->hasMessage=true;
                    $this->addMessageInternal($msg,'$field is not float',$field,$value,null,'error');
                    return null;
                }
                return (float)$value;
                break;
            case 'varchar':
            case 'string':
                // if string is empty then it uses the default value. It's useful for filter
                return ($value==="")?$this->default:$value;
                break;
            case 'date':
            case 'datetime':
                $valueDate=DateTime::createFromFormat(self::$dateLong, $value);
                if ($valueDate===false) {
                    // the format is not date and time, maybe it's only date
                    /** @var DateTime $valueDate */
                    $valueDate=DateTime::createFromFormat(self::$dateShort, $value);
                    if ($valueDate===false) {
                        // nope, it's neither date.
                        $this->hasMessage=true;
                        $this->addMessageInternal($msg,'%field is not date',$field,$value,null,'error');
                        return null;
                    }
                    $valueDate->settime(0,0,0,0);
                }
                return $valueDate;
                break;
            default:
                return $value;
                break;
        }
    }

    //<editor-fold desc="error control">
    /**
     * It adds an error
     * @param string $msg first message. If it's empty or null then it uses the second message<br>
     *      Message could uses the next variables '%field','%realfield','%value','%comp','%first','%second'
     * @param string $msg2 second message
     * @param string $fieldId id of the field
     * @param mixed $value value supplied
     * @param mixed $vcomp value to compare.
     * @param string $level (error,warning,info,success) error level
     */
    private function addMessageInternal($msg, $msg2, $fieldId, $value, $vcomp, $level='error') {
        $txt=($msg)?$msg:$msg2;
        if (is_array($vcomp)) {
            $first=@$vcomp[0];
            $second=@$vcomp[1];
            $vcomp=@$vcomp[0]; // is not array anymore
        } else {
            $first=$vcomp;
            $second=$vcomp;
        }
        $txt=str_replace(['%field','%realfield','%value','%comp','%first','%second']
            ,[($this->friendId)?$fieldId:$this->friendId,$fieldId,$value,$vcomp,$first,$second],$txt);
        $this->messageList->addItem($fieldId,$txt, $level);
    }

    /**
     * It gets the first error message available in the whole messagelist.
     * @param bool $withWarning
     * @return null|string
     */
    public function getMessage($withWarning=false) {
        if ($withWarning) return $this->messageList->firstErrorOrWarning();
        return $this->messageList->firstErrorText();
    }

    /**
     * It returns an array with all the errors of all "ids"
     * @param bool $withWarning
     * @return array
     */
    public function getMessages($withWarning=false) {
        if ($withWarning) $this->messageList->allErrorOrWarningArray();
        return $this->messageList->allErrorArray();
    }

    /**
     * It returns the error of the element "id".  If it doesn't exist then it returns an empty MessageItem
     * @param string $id
     * @return MessageItem
     */
    public function getMessageId($id) {
        return $this->messageList->get($id);
    }
    //</editor-fold>


}