<?php
declare(strict_types=1);

namespace eftec;

use DateTime;
use ReflectionMethod;

/** @var string  sometimes we want to sets an empty as empty. For example <select><option> this nullval is equals to null */
if (!defined("NULLVAL")) define('NULLVAL','__nullval__');

/**
 * Class Validation
 * @package eftec
 * @author Jorge Castro Castillo
 * @version 1.13 2018-dic-15 
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see https://github.com/EFTEC/ValidationOne
 */
class ValidationOne
{
    public static $dateShort='d/m/Y';
    public static $dateLong='d/m/Y H:i:s';
    /** @var MessageList */
    var $messageList;

    /** @var ValidationInputOne */
    var $input;
    /** @var bool if debug then it fills an array called debugLog */
    var $debug=false;
    var $debugLog=[];

    //private $NUMARR='integer,unixtime,boolean,decimal,float';
    private $STRARR='varchar,string';
    private $DATARR='date,datetime';

    //<editor-fold desc="chain variables">
    /** @var mixed default value */
    private $default=null;
    private $initial=null;
    /** @var string integer,unixtime,boolean,decimal,float,varchar,string,date,datetime */
    private $type='string';
    private $types=[];
    /** @var int 0=number,1=string,2=date,3=boolean */
    private $typeFam=1;
    private $typeFams=1;
    /** @var bool if an error happens, then the next validations are not executed */
    private $abortOnError=false;

    /** @var bool if the value is an array or not */
    private $isArray=false;
    /** @var bool if the value is an array or not */
    private $isColumn=false;
    /** @var bool if true then the the errors from id[0],id[1] ared stored in "idx" */
    private $isArrayFlat=false;

    private $hasMessage=false;
    /** @var bool if the validation fails then it returns the default value */
    private $ifFailThenDefault=false;
    private $ifFailThenOrigin=false;
    /** @var null|string  */
    private $successMessage=null;

    /** @var bool It override previous errors (for the "id" used) */
    private $override=false;
    /** @var bool If true then the field is required otherwise it generates an error */
    private $required=false;
    /** @var mixed It keeps a copy of the original value (after get/post/fetch or set) */
    private $originalValue=null;
    /** @var string It's a friendly id used to replace the "id" used in message. For example: "id customer" instead of "idcustomer" */
    private $friendId=null;
    /** @var ValidationItem[]  */
    public $conditions=[];

    /**
     * @var FormOne It is a optional feature that uses FormOne. It's used for callback.
     * @see https://github.com/EFTEC/FormOne
     */
    private $formOne=null;
    private $addToForm=false;

    private $defaultIfFail=false;
    private $defaultRequired=false;
    /** @var string Prefix used for the input */
    private $prefix='';
    /** @var bool value is missing  */
    private $isMissing=false;
    /* interal counter of error per chain */
    private $countError;

    //</editor-fold>

    /**
     * Validation constructor.
     * @param string $prefix Prefix used for the input. For example "frm_"
     */
    public function __construct($prefix='')
    {
        if (function_exists('messages')) {
            $this->messageList=messages();
        } else {
            $this->messageList=new MessageList();
        }
        $this->prefix=$prefix;
        $this->resetChain();
    }

    /**
     * @return ValidationInputOne
     */
    private function input() {
        if ($this->input===null) {
            $this->input=new ValidationInputOne($this->prefix,$this->messageList); // we used the same message list
        }
        return $this->input;
    }

    /**
     * @param string $field
     * @param null $msg
     * @return array|bool|\DateTime|float|int|mixed|null
     */
    public function get($field="",$msg=null) {
        return $this->endChainFetch(INPUT_GET,$field,$msg);
    }
    /**
     * @param string $field
     * @param null $msg
     * @return array|bool|\DateTime|float|int|mixed|null
     */
    public function post($field,$msg=null) {
        return $this->endChainFetch(INPUT_POST,$field,$msg);
    }
    /**
     * @param string $field
     * @param null $msg
     * @return array|bool|\DateTime|float|int|mixed|null
     */
    public function request($field,$msg=null) {
        return $this->endChainFetch(INPUT_REQUEST,$field,$msg);
    }
    /**
     * It fetches a value.
     * @param int $inputType INPUT_POST|INPUT_GET|INPUT_REQUEST
     * @param string $field
     * @param null|string $msg
     * @return mixed
     */
    public function fetch($inputType,$field,$msg=null) {
        return $this->endChainFetch($inputType,$field,$msg);
    }

    /**
     * @param $inputType
     * @param $fieldId
     * @param null $msg
     * @return array|bool|\DateTime|float|int|mixed|null
     */
    private function endChainFetch($inputType,$fieldId,$msg=null) {

        $this->countError=$this->messageList->errorcount;

        $this->input()->default=$this->default;
        $this->input()->originalValue=$this->originalValue;
        $this->input()->ifFailThenOrigin=$this->ifFailThenOrigin;
        $this->input()->initial=$this->initial;
        $r=$this->input()
            ->required($this->required)
            ->friendId($this->friendId)
            ->getField($fieldId,$inputType,$msg,$this->isMissing);
        return $this->afterFetch($r,$fieldId,$msg);

    }
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
    public function getFile($field,$array=false,&$fileTmp="",&$fileNew="") {
        $tmp=$this->input();
        return $tmp::getFile($field,$array,$fileTmp,$fileNew);
    }


    //<editor-fold desc="chain commands">
    /**
     * It sets a default value. It could be used as follow:
     * a) if the value is not set and it's not required (by default, it's not required), then it sets this value. otherwise null<br>
     * b) if the value is not set and it's required, then it returns an error and it sets this value, otherwise null<br>
     * c) if the value is not set and it's an array, then it sets a single value or it sets a value per key of array.
     * d) if value is null, then the default value is the same input value.
     * @param mixed|array $value
     * @param bool|null $ifFailThenDefault  True if the system returns the default value if error.
     * @return ValidationOne $this
     */
    public function def($value=null, $ifFailThenDefault=null) {
        $this->default=$value;
        if ($ifFailThenDefault!==null) $this->ifFailThenDefault=$ifFailThenDefault;
        return $this;
    }

    /**
     * (Optional). It sets an initial value.<br>
     * If the value is missing (that it's different to empty or null), then it uses this value.
     * @param null $initial
     * @return $this
     */
    public function initial($initial=null) {
        $this->initial=$initial;
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
     * Sets if the conditions must be evaluated on Error or not. By default it's not aborted.
     * @param bool $abort if true, then it stop at the first error.
     * @return ValidationOne $this
     */
    public function abortOnError($abort=false) {
        $this->abortOnError=$abort;
        return $this;
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
    public function isColumn($isColumn) {
        $this->isColumn=$isColumn;
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
     * @param bool $ifFailThenOrigin
     * @return ValidationOne ValidationOne
     */
    public function ifFailThenOrigin($ifFailThenOrigin=true) {
        $this->ifFailThenDefault=true;
        $this->ifFailThenOrigin=$ifFailThenOrigin;
        return $this;
    }

    public function successMessage($id,$msg,$level="success") {
        $this->successMessage=['id'=>$id,'msg'=>$msg,'level'=>$level];
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
     * This validation doesn't fail if the field is missing or zero. Only if it's unable to fetch the value.
     * @param bool $required
     * @return ValidationOne
     * @see ValidationOne::def()
     */
    public function required($required=true) {
        $this->required=$required;
        return $this;
    }

    /**
     * The value musn't be empty. It's equals than condition('ne','..','','error')
     * @param string $msg
     * @return $this
     */
    public function notempty($msg='') {
        $this->condition('ne',($msg=='')?'%field is empty':$msg,'','error');
        return $this;
    }

    /**
     * @param FormOne $form
     * @return ValidationOne
     */
    public function useForm($form) {
        $this->formOne=$form;
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
     * @param string|array $type integer,unixtime,boolean,decimal,float,varchar,string,date,datetime
     * @return int|int[] 1=string,2=date,3=boolean,0=number
     */
    private function getTypeFamily($type) {
        if (is_array($type)) {
            $r=[];
            foreach($type as $key=>$t) {
                $r[$key]=$this->getTypeFamily($t);
            }
        } else {
            switch (1 == 1) {
                case (strpos($this->STRARR, $type) !== false):
                    $r = 1; // string
                    break;
                case (strpos($this->DATARR, $type) !== false):
                    $r = 2; // date
                    break;
                case ($type == 'boolean'):
                    $r = 3; // boolean
                    break;
                default:
                    $r = 0; // number
            }
        }
        return $r;
    }

    /**
     * @param string|array $type integer,unixtime,boolean,decimal,float,varchar,string,date,datetime
     * @return ValidationOne $this
     */
    public function type($type) {
        if(is_array($type)) {
            $this->typeFams=$this->getTypeFamily($type);
            $this->types=$type;
        } else {
            $this->typeFam=$this->getTypeFamily($type);
            $this->type=$type;

        }
        return $this;
    }

    /**
     * @param string $type
     *      number:req,eq,ne,gt,lt,gte,lte,between,null,notnull<br>
     *      string:req,eq,ne,minlen,maxlen,betweenlen,null,notnull,contain,notcontain,alpha,alphanum,text,regexp,email,url,domain<br>
     *      date:req,eq,ne,gt,lt,gte,lte,between<br>
     *      boolean:req,eq,ne,true,false<br>
     *      <b>function:</b><br>
     *          fn.static.Class.methodstatic<br>
     *          fn.global.function<br>
     *          fn.object.Class.method where object is a global $object<br>
     *          fn.class.Class.method<br>
     *          fn.class.\namespace\Class.method<br>
     * @param string $message <br>
     *      Message could uses the next variables '%field','%realfield','%value','%comp','%first','%second'
     * @param null $value
     * @param string $level (error,warning,info,success)
     * @param null $key
     * @return ValidationOne
     */
    public function condition($type, $message="", $value=null, $level='error',$key=null) {
        if ($key!==null) {
            $this->conditions[$key][]=new ValidationItem($type,$message,$value,$level);
        } else {
            $this->conditions[]=new ValidationItem($type,$message,$value,$level);
        }

        return $this;

    }

    /**
     * It resets the chain (if any)
     * It also reset any validating pending to be executed.
     */
    public function resetChain() {

        $this->default=null;
        $this->initial=null;

        $this->type='string'; // it's important, string is the default value because it's not processed.
        $this->typeFam=1; // string
        $this->isArray=false;
        $this->abortOnError=true;
        $this->isArrayFlat=false;
        $this->isColumn=false;
        $this->hasMessage=false;
        $this->ifFailThenDefault=$this->defaultIfFail;
        $this->ifFailThenOrigin=false;
        $this->conditions=[];
        $this->override=false;
        $this->resetValidation();
        $this->required=$this->defaultRequired;
        $this->friendId=null;
        $this->successMessage=null;
        $this->countError=0;
        $this->addToForm=false;
    }

    private $container=[];

    public function store() {
        $id=1;
        $this->container[$id]=[];
        $new=&$this->container[$id]; //it's an instance
        $new['isArray']=$this->isArray;
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
        $this->conditions=array();
    }

    //<editor-fold desc="fetch and end of chain commands">

    private function afterFetch($r, $fieldId,$msg) {

        if (!$this->isMissing) {
            if ($this->ifFailThenOrigin) {
                $this->default = $r;
            }
            if (!$this->isArray) {
                $this->originalValue = $r;
                $r = $this->basicValidation($r, $fieldId, $msg);
            } else {
                $this->originalValue = $r;
                if (is_array($r) || $r === null) {
                    if ($r !== null) {
                        foreach ($r as $key => &$v) {
                            $currentField = ($this->isArrayFlat) ? $fieldId : $fieldId . "[" . $key . "]";
                            $v = $this->basicValidation($v, $currentField, $msg, $key);
                        }
                    }
                } else {
                    $this->addMessageInternal('%field is not an array', '', $fieldId, 0, 'error');
                }
            }
            if ($this->isArray) {
                if (is_array($r)) {
                    foreach ($r as $key => &$items) {
                        $currentField = ($this->isArrayFlat) ? $fieldId : $fieldId . "[" . $key . "]";
                        $this->runConditions($items, $currentField, $key);

                        if ($this->ifFailThenDefault) {
                            if ($this->messageList->get($currentField)->countError())
                                $items = (is_array($this->default)) ? $this->default[$key] : $this->default;
                        }
                    }
                }
            } else {
                $this->runConditions($r, $fieldId);
                if ($this->ifFailThenDefault) {
                    if ($this->messageList->errorcount)
                        $r = $this->default;
                }
            }
        }
        if ($this->messageList->errorcount==$this->countError && $this->successMessage!==null) {
            $this->addMessage($this->successMessage['id'],$this->successMessage['msg'],$this->successMessage['level']);
        }
        if ($this->addToForm) {
            $this->callFormBack($fieldId);
        }
        $this->resetChain();
        return $r;
    }

    /**
     * It's a callback to the form if it's defined.<br>
     * It's used to inform to the form that the validation chain is ready to send validation to the visual layer.
     * @param $fieldId
     */
    private function callFormBack($fieldId) {
        if ($this->formOne!==null) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->formOne->callBack($this, $fieldId);
        }
    }

    public function set($value,$fieldId="setfield",$msg="",&$isMissing=false) {
        $this->isMissing=$isMissing;
        if ($this->override) {
            $this->messageList->items[$fieldId]=new MessageItem();
        }
        if (is_object($value)) {
            $value=(array)$value;
        }
        $this->countError=$this->messageList->errorcount;
        if (is_array($value)) {
            foreach($value as $key=>&$v) {
                $this->originalValue=$v;
                $currentField=($this->isArrayFlat)?$fieldId:$fieldId."[".$key."]";
                $v=$this->basicValidation($v,$currentField,$msg,$key);
                //if ($this->abortOnError && $this->messageList->errorcount) break;
                $this->runConditions($v,$currentField,$key);
                if ($this->messageList->errorcount===0) {
                    if ($this->messageList->get($currentField)->countError())
                        $v=@$this->default[$key];
                }
            }
        } else {
            $this->originalValue=$value;
            $value=$this->basicValidation($value,$fieldId,$msg);
            if ($this->abortOnError!=false || $this->messageList->errorcount==0) {
                $this->runConditions($value, $fieldId);
            }
            if ($this->ifFailThenDefault) {
                if ($this->messageList->get($fieldId)->countError())
                    $value=$this->default;
            }
        }
        if ($this->messageList->errorcount==$this->countError && $this->successMessage!==null) {
            $this->addMessage($this->successMessage['id'],$this->successMessage['msg'],$this->successMessage['level']);
        }
        if ($this->addToForm) {
            $this->callFormBack($fieldId);
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
    private function runNumericCondition($r, $cond, &$fail, &$genMsg) {
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
            case 'null':
                if ($r !==null) {
                    $fail = true;
                    $genMsg = '%field is noy null';
                }
                break;
            case 'notnull':
                if ($r ===null) {
                    $fail = true;
                    $genMsg = '%field is null';
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
    private function runStringCondition($r, $cond, &$fail, &$genMsg) {

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
            case 'contain':
                if (strpos($r,$cond->value)===false) {
                    $fail = true;
                    $genMsg = '%field contains %comp';
                }
                break;
            case 'notcontain':
                if (strpos($r,$cond->value)!==false) {
                    $fail = true;
                    $genMsg = '%field does not contain %comp';
                }
                break;
            case 'alpha':
                if (!ctype_alpha($r)) {
                    $fail = true;
                    $genMsg = '%field is not alphabetic';
                }
                break;
            case 'alphanum':
                //
                if (!ctype_alnum($r)) {
                    $fail = true;
                    $genMsg = '%field is not alphanumeric';
                }
                break;
            case 'text':
                // words, number, accents, spaces, and other characters
                if (!preg_match('^[\p{L}| |.|\/|*|+|.|,|=|_|"|\']+$',$r)) {
                    $fail = true;
                    $genMsg = '%field has characters not allowed';
                }
                break;
            case 'regexp':
                if (!preg_match($cond->value,$r)) {
                    $fail = true;
                    $genMsg = '%field is not allowed';
                }
                break;
            case 'email':
                if (!filter_var($r, FILTER_VALIDATE_EMAIL)) {
                    $fail = true;
                    $genMsg = '%field is not an email';
                }
                break;
            case 'url':
                if (!filter_var($r, FILTER_VALIDATE_URL)) {
                    $fail = true;
                    $genMsg = '%field is not an url';
                }
                break;
            case 'domain':
                if (!filter_var($r, FILTER_VALIDATE_DOMAIN)) {
                    $fail = true;
                    $genMsg = '%field is not a domain';
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
            case 'null':
                if ($r !==null) {
                    $fail = true;
                    $genMsg = '%field is noy null';
                }
                break;
            case 'notnull':
                if ($r ===null) {
                    $fail = true;
                    $genMsg = '%field is null';
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
    private function runDateCondition($r, $cond, &$fail, &$genMsg) {
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
    private function runBoolCondition($r, $cond, &$fail, &$genMsg) {
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
    private function runFnCondition($r, $cond, &$fail, &$genMsg) {
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
    private function runConditions($r, $fieldId,$key=null) {
        $genMsg='';
        if ($key===null || $this->isColumn) {
            foreach ($this->conditions as $cond) {
                $fail = false;
                if (strpos($cond->type, "fn.") === 0) {
                    // if it starts with fn. then it's a function condition
                    $this->runFnCondition($r, $cond, $fail, $genMsg);
                } else {
                    switch ($this->typeFam) {
                        case 0: // number
                            $this->runNumericCondition($r, $cond, $fail, $genMsg);
                            break;
                        case 1: // string
                            $this->runStringCondition($r, $cond, $fail, $genMsg);
                            break;
                        case 2: // date
                            $this->runDateCondition($r, $cond, $fail, $genMsg);
                            break;
                        case 3: // bool
                            $this->runBoolCondition($r, $cond, $fail, $genMsg);
                            break;
                    } // switch
                }
                if ($fail) {
                    $this->addMessageInternal($cond->msg, $genMsg, $fieldId, $r, $cond->value, $cond->level,$key);
                    if (!$this->abortOnError) {
                        break; // no continue anymore.
                    }
                }
            }
        } else {
            if (isset($this->conditions[$key])) {
                $fail = false;
                foreach ($this->conditions[$key] as $cond) {

                    if (strpos($cond->type, "fn.") === 0) {
                        // if it starts with fn. then it's a function condition
                        $this->runFnCondition($r, $cond, $fail, $genMsg);
                    } else {
                        switch ($this->typeFams[$key]) {
                            case 0: // number
                                $this->runNumericCondition($r, $cond, $fail, $genMsg);
                                break;
                            case 1: // string
                                $this->runStringCondition($r, $cond, $fail, $genMsg);
                                break;
                            case 2: // date
                                $this->runDateCondition($r, $cond, $fail, $genMsg);
                                break;
                            case 3: // bool
                                $this->runBoolCondition($r, $cond, $fail, $genMsg);
                                break;
                        } // switch
                    }
                    if ($fail) {
                        $this->addMessageInternal($cond->msg, $genMsg, $fieldId, $r, $cond->value, $cond->level,$key);
                        if (!$this->abortOnError) {
                            break; // no continue anymore.
                        }
                    }
                }
            }
        }
    }
    //</editor-fold>

    /**
     * @param mixed $valueToEval
     * @param string $field
     * @param string $msg
     * @param null $key
     * @return bool|DateTime|float|int|mixed|null
     */
    public function basicValidation($valueToEval, $field, $msg="",$key=null) {
        $localDefault=$items=(is_array($this->default))?$this->default[$key]:$this->default;
        if ($key!==null && isset($this->types[$key])) {
            $type=$this->types[$key];
            $value=$valueToEval;
        } else {
            $type=$this->type;
            $value=$valueToEval;
        }
        switch($type) {
            case 'integer':
            case 'unixtime':
                if (!is_numeric($value) && $value!=='') {
                    $this->hasMessage=true;
                    $this->addMessageInternal($msg,'%field is not numeric',$field,$value,null,'error',$key);
                    return null;
                }
                return (int)$value;
                break;
            case 'boolean':
                return (bool)$value;
                break;
            case 'decimal':
                if (!is_numeric($value) && $value!=='') {
                    $this->hasMessage=true;
                    $this->addMessageInternal($msg,'$field is not decimal',$field,$value,null,'error');
                    return null;
                }
                return (double)$value;
                break;
            case 'float':
                if (!is_numeric($value)  && $value!=='') {
                    $this->hasMessage=true;
                    $this->addMessageInternal($msg,'$field is not float',$field,$value,null,'error');
                    return null;
                }
                return (float)$value;
                break;
            case 'varchar':
            case 'string':
                // if string is empty then it uses the default value. It's useful for filter
                return ($value==="")?$localDefault:$value;
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
     *      Message could uses the next variables. Ex "%field is empty"<br>
     *      %field = name of the field, it could be the friendid or the actual name<br>
     *      %realfield = name of the field (not the friendid)<br>
     *      %value = current value of the field<br>
     *      %comp = value to compare (if any)<br>
     *      %first = first value to compare (if the compare value is an array)<br>
     *      %second = second value to compare (if the compare value is an array)<br>
     *      %key = key used (for input array)<br>
     * @param string $msg2 second message
     * @param string $fieldId id of the field
     * @param mixed $value value supplied
     * @param mixed $vcomp value to compare.
     * @param string $level (error,warning,info,success) error level
     * @param null $key
     */
    private function addMessageInternal($msg, $msg2, $fieldId, $value, $vcomp, $level='error',$key=null) {
        $txt=($msg)?$msg:$msg2;
        if (is_array($vcomp)) {
            $first=@$vcomp[0];
            $second=@$vcomp[1];
            $vcomp=@$vcomp[0]; // is not array anymore
        } else {
            $first=$vcomp;
            $second=$vcomp;
        }
        if (is_array($this->originalValue)) {
            $txt=str_replace(['%field','%realfield','%value','%comp','%first','%second','%key']
                ,[($this->friendId===null)?$fieldId:$this->friendId,$fieldId
                    ,is_array($value)?"[]":$value,$vcomp,$first,$second,$key],$txt);
            //$this->originalValue=$value;
        } else {
            $txt=str_replace(['%field','%realfield','%value','%comp','%first','%second','%key']
                ,[($this->friendId===null)?$fieldId:$this->friendId,$fieldId
                    ,$this->originalValue,$vcomp,$first,$second,$key],$txt);
        }
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