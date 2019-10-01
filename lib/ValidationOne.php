<?php /** @noinspection PhpUndefinedClassInspection */

//declare(strict_types=1);

namespace eftec;

use DateTime;
use Exception;
use ReflectionMethod;

/** @var string  sometimes we want to sets an empty as empty. For example <select><option> this nullval is equals to null */
if (!defined("NULLVAL")) {
    define('NULLVAL', '__nullval__');
}

/**
 * Class Validation
 *
 * @package       eftec
 * @author        Jorge Castro Castillo
 * @version       1.17 2019-10-01.
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see           https://github.com/EFTEC/ValidationOne
 */
class ValidationOne
{
    /** @var string It is the (expected) input format for date (short) */
    public $dateShort = 'd/m/Y';
    /** @var string It is the (expected) input format (with date and time) */
    public $dateLong = 'd/m/Y H:i:s';
    /** @var string It is the output format (for datestring) */
    public $dateOutputString = 'Y-m-d';
    /** @var string It is the output format (for datetimestring) */
    public $dateLongOutputString = 'Y-m-d\TH:i:s\Z';
    /** @var MessageList */
    var $messageList;

    /** @var ValidationInputOne */
    var $input;
    /** @var bool if debug then it fills an array called debugLog */
    var $debug = false;
    var $debugLog = [];

    //private $NUMARR='integer,unixtime,boolean,decimal,float';
    private $STRARR = 'varchar,string';
    /** @var string members of the family DATE */
    private $DATARR = 'date,datetime';
    /** @var string members of the family DATESTRING */
    private $DATSARR = 'datestring,datetimestring';
    //<editor-fold desc="chain variables">
    /** @var mixed default value */
    private $default = null;
    private $initial = null;
    /** @var string=['integer','unixtime','boolean','decimal','float','varchar','string','date','datetime','datestring','datetimestring'][$i] */
    private $type = 'string';
    /** @var array used to store types (if the input is an array) */
    private $types = [];
    /** @var int=[0,1,2,3,4,5][$i] Family of types 0=number,1=string,2=date,3=boolean,4=file,5=datestring */
    private $typeFam = 1;
    /** @var int=[0,1,2,3,4,5][$i] Family of types (for arrays). See self::$types */
    private $typeFams = 1;
    /** @var bool if an error happens, then the next validations are not executed */
    private $abortOnError = false;

    /** @var bool if the value is an array or not */
    private $isArray = false;
    /** @var bool if the value is an array or not */
    private $isColumn = false;
    /** @var bool if true then the the errors from id[0],id[1] ared stored in "idx" */
    private $isArrayFlat = false;

    private $hasMessage = false;
    /** @var bool if the validation fails then it returns the default value */
    private $ifFailThenDefault = false;
    /** @var bool if the validation fails then it returns the original (input) value */
    private $ifFailThenOrigin = false;
    /** @var null|string */
    private $successMessage = null;

    /** @var bool It override previous errors (for the "id" used) */
    private $override = false;
    /** @var bool If true then the field is required otherwise it generates an error */
    private $required = false;
    /** @var mixed It keeps a copy of the original value (after get/post/fetch or set) */
    private $originalValue = null;
    /** @var string It's a friendly id used to replace the "id" used in message. For example: "id customer" instead of "idcustomer" */
    private $friendId = null;
    /** @var ValidationItem[] */
    public $conditions = [];

    private $container = [];

    /**
     * @var FormOne It is a optional feature that uses FormOne. It's used for callback.
     * @see https://github.com/EFTEC/FormOne
     */
    private $formOne = null;
    private $addToForm = false;
    /** @var bool if true and the validation fails, then it returns the default value */
    private $defaultIfFail = false;
    private $defaultRequired = false;
    /** @var string Prefix used for the input */
    private $prefix = '';
    /** @var bool value is missing */
    private $isMissing = false;
    /* interal counter of error per chain */
    private $countError;

    //</editor-fold>

    /**
     * Validation constructor.
     *
     * @param string $prefix Prefix used for the input. For example "frm_"
     */
    public function __construct($prefix = '')
    {
        if (function_exists('messages')) {
            $this->messageList = messages();
        } else {
            $this->messageList = new MessageList();
        }
        $this->prefix = $prefix;
        $this->resetChain();
    }

    /**
     * It sets the date format (for input and output).<br>
     * Input is the expected value to fetch<br>
     * Output is the result of the value<br>
     * 
     * @param null|string $dateInput Example 'd/m/Y'
     * @param null|string $dateTimeInput Example 'd/m/Y H:i:s'
     * @param null|string $dateOutput Example 'Y-m-d' (used for datestring and datetimestring)
     * @param null|string $dateTimeOutput Example 'Y-m-d\TH:i:s\Z' (used for datestring and datetimestring)
     *
     * @return $this
     */
    public function setDateFormat($dateInput=null,$dateTimeInput=null,$dateOutput=null,$dateTimeOutput=null) {
        if($dateInput!==null) {
            $this->dateShort=$dateInput;
        }
        if($dateTimeInput!==null) {
            $this->dateLong=$dateTimeInput;
        }
        if($dateOutput!==null) {
            $this->dateOutputString=$dateOutput;
        }
        if($dateTimeOutput!==null) {
            $this->dateLongOutputString=$dateTimeOutput;
        }
        return $this;
    }

    /**
     * it's the injector of validationinputone.
     *
     * @return ValidationInputOne
     */
    private function input()
    {
        if ($this->input === null) {
            $this->input = new ValidationInputOne($this->prefix, $this->messageList); // we used the same message list
        }
        return $this->input;
    }

    /**
     * @param string $field
     * @param null   $msg
     *
     * @return array|bool|DateTime|float|int|mixed|null
     */
    public function get($field = "", $msg = null)
    {
        return $this->endChainFetch(INPUT_GET, $field, $msg);
    }

    /**
     * @param string $field
     * @param null   $msg
     *
     * @return array|bool|DateTime|float|int|mixed|null
     */
    public function post($field, $msg = null)
    {
        return $this->endChainFetch(INPUT_POST, $field, $msg);
    }

    /**
     * @param string $field
     * @param null   $msg
     *
     * @return array|bool|DateTime|float|int|mixed|null
     */
    public function request($field, $msg = null)
    {
        return $this->endChainFetch(INPUT_REQUEST, $field, $msg);
    }

    /**
     * It fetches a value.
     *
     * @param int         $inputType INPUT_POST|INPUT_GET|INPUT_REQUEST
     * @param string      $field
     * @param null|string $msg
     *
     * @return mixed
     */
    public function fetch($inputType, $field, $msg = null)
    {
        return $this->endChainFetch($inputType, $field, $msg);
    }

    /**
     * It ends the fetch of the information. It doesn't modify this information
     * 
     * @param int    $inputType INPUT_POST|INPUT_GET|INPUT_REQUEST
     * @param string $fieldId
     * @param null   $msg
     *
     * @return array|bool|DateTime|float|int|mixed|null
     */
    private function endChainFetch($inputType, $fieldId, $msg = null)
    {

        $this->countError = $this->messageList->errorcount;

        $this->input()->default = $this->default;
        $this->input()->originalValue = $this->originalValue;
        $this->input()->ifFailThenOrigin = $this->ifFailThenOrigin;
        $this->input()->initial = $this->initial;
        foreach ($this->conditions as $c) {
            if ($c->type == "req") {
                $this->required = true;
                break;
            }
        }
        $r = $this->input()
            ->required($this->required)
            ->friendId($this->friendId)
            ->getField($fieldId, $inputType, $msg, $this->isMissing);
        return $this->afterFetch($r, $fieldId, $msg);

    }

    /**
     * Returns null if the value is not present, false if the value is incorrect and the value if its correct
     *
     * @param      $fieldId
     * @param bool $array
     * @param null $msg
     *
     * @return array|null
     * @internal param $folder
     * @internal param string $type
     */
    public function getFile($fieldId, $array = false, $msg = null)
    {
        $this->countError = $this->messageList->errorcount;

        $this->input()->default = $this->default;
        $this->input()->originalValue = $this->originalValue;
        $this->input()->ifFailThenOrigin = $this->ifFailThenOrigin;
        $this->input()->initial = $this->initial;
        foreach ($this->conditions as $c) {
            if ($c->type == "req") {
                $this->required = true;
                break;
            }
        }

        $r = $this->input()
            ->required($this->required)
            ->friendId($this->friendId)
            ->getFile($fieldId, $array, $msg, $this->isMissing);
        //->getField($fieldId,$inputType,$msg,$this->isMissing);
        return $this->afterFetch($r, $fieldId, $msg);
        //return $this->input()->getFile($field,$array);
    }


    //<editor-fold desc="chain commands">

    /**
     * It sets a default value. It could be used as follow:
     * a) if the value is not set and it's not required (by default, it's not required), then it sets this value. otherwise null<br>
     * b) if the value is not set and it's required, then it returns an error and it sets this value, otherwise null<br>
     * c) if the value is not set and it's an array, then it sets a single value or it sets a value per key of array.
     * d) if value is null, then the default value is the same input value.<br>
     * Note: This value must be in the same format than the (expected) input.
     *
     * @param mixed|array $value
     * @param bool|null   $ifFailThenDefault True if the system returns the default value if error.
     *
     * @return ValidationOne $this
     * @see \eftec\ValidationOne::ifFailThenDefault                   
     */
    public function def($value = null, $ifFailThenDefault = null)
    {
        $this->default = $value;
        if ($ifFailThenDefault !== null) {
            $this->ifFailThenDefault = $ifFailThenDefault;
        }
        return $this;
    }

    /**
     * (Optional). It sets an initial value.<br>
     * If the value is missing (that it's different to empty or null), then it uses this value.
     *
     * @param null $initial
     *
     * @return $this
     */
    public function initial($initial = null)
    {
        $this->initial = $initial;
        return $this;
    }

    /**
     * It sets the default value based on the family of type of data. <br>
     * If the type of data is not specified, then it sets the value to string ''.<br>
     * number = 0 (-1 if negative=true)<br>
     * string = '' (null if negative=true)<br>
     * date = DateTime() (null if negative=true)<br>
     * boolean = true (false if negative=true)<br>
     * file = '' (null if negative=true)<br>
     * datestring = '1970-01-01T00:00:00Z' (null if negative=true)<br>
     *
     * @param bool $negative if true then it returns the negative default value.
     *
     * @return ValidationOne $this
     * @throws Exception
     */
    public function defNatural($negative = false)
    {
        switch ($this->typeFam) {
            case '':
            case 0:
                $this->default = (!$negative) ? 0 : -1;
                break;
            case 1:
                $this->default = (!$negative) ? '' : null;
                break;
            case 2:
                $this->default = (!$negative) ? new DateTime() : null;
                break;
            case 3:
                $this->default = (!$negative) ? true : false;
                break;
            case 4:
                $this->default = (!$negative) ? '' : null; // file
                break;
            case 5:
                $defaultDate = new DateTime();
                if ($this->type == 'datetimestring') {
                    $defaultDate=$defaultDate->format($this->dateLong);
                } else {
                    $defaultDate->setTime(0,0,0);
                    $defaultDate=$defaultDate->format($this->dateShort);
                }
                $this->default = (!$negative) ? $defaultDate : null;
                break;
        }
        return $this;
    }

    /**
     * It configures all the next chains with those default values.<br>
     * For example, we could force to be required always.
     *
     * @param bool $ifFailThenDefault
     * @param bool $ifRequired The field must be fetched, otherwise it generates an error
     */
    public function configChain($ifFailThenDefault = false, $ifRequired = false)
    {
        $this->defaultIfFail = $ifFailThenDefault;
        $this->defaultRequired = $ifRequired;
    }

    /**
     * Sets if the conditions must be evaluated on Error or not. By default it's not aborted.
     *
     * @param bool $abort if true, then it stop at the first error.
     *
     * @return ValidationOne $this
     */
    public function abortOnError($abort = false)
    {
        $this->abortOnError = $abort;
        return $this;
    }

    /**
     * Sets the fetch for an array. It's not required for set()<br>
     * If $flat is true then then errors are returned as a flat array (idx instead of idx[0],idx[1])
     *
     * @param bool $flat
     *
     * @return ValidationOne $this
     */
    public function isArray($flat = false)
    {
        $this->isArray = true;
        $this->isArrayFlat = $flat;
        return $this;
    }

    public function isColumn($isColumn)
    {
        $this->isColumn = $isColumn;
        return $this;
    }

    /**
     * @param bool $ifFailDefault
     *
     * @return ValidationOne ValidationOne
     */
    public function ifFailThenDefault($ifFailDefault = true)
    {
        $this->ifFailThenDefault = $ifFailDefault;
        return $this;
    }

    /**
     * If the operation fails, then it assigns the original unadultered value (input value)
     * 
     * @param bool $ifFailThenOrigin
     *
     * @return ValidationOne ValidationOne
     */
    public function ifFailThenOrigin($ifFailThenOrigin = true)
    {
        $this->ifFailThenDefault = true;
        $this->ifFailThenOrigin = $ifFailThenOrigin;
        return $this;
    }

    public function successMessage($id, $msg, $level = "success")
    {
        $this->successMessage = ['id' => $id, 'msg' => $msg, 'level' => $level];
        return $this;
    }

    /**
     * If override previous errors
     *
     * @param bool $override
     *
     * @return ValidationOne
     */
    public function override($override = true)
    {
        $this->override = $override;
        return $this;
    }

    /**
     * If it's unable to fetch then it generates an error.<br>
     * However, by default it also returns the default value.
     * This validation doesn't fail if the field is missing or zero. Only if it's unable to fetch the value.
     * it's the same than $validation->condition("req","")
     *
     * @param bool $required
     *
     * @return ValidationOne
     * @see ValidationOne::def()
     * @see ValidationOne::condition()
     */
    public function required($required = true)
    {
        $this->required = $required;
        return $this;
    }

    /**
     * The value musn't be empty. It's equals than condition('ne','..','','error')
     *
     * @param string $msg
     *
     * @return $this
     */
    public function notempty($msg = '')
    {
        $this->condition('ne', ($msg == '') ? '%field is empty' : $msg, '', 'error');
        return $this;
    }

    /**
     * @param FormOne $form
     *
     * @return ValidationOne
     * @noinspection PhpUnused
     */
    public function useForm($form)
    {
        $this->formOne = $form;
        return $this;
    }

    /**
     * It's a friendly id used to replace the "id" used in message. For example: "id customer" instead of "idcustomer"
     *
     * @param $id
     *
     * @return ValidationOne
     */
    public function friendId($id)
    {
        $this->friendId = $id;
        return $this;
    }

    /**
     * It returns the number of the family.
     *
     * @param string|array $type =['integer','unixtime','boolean','decimal','float','varchar','string','date','datestring','datetime','datetimestring','file'][$i]
     *
     * @return int|int[] 1=string,2=date,3=boolean,4=file,5=datestring,0=number
     */
    private function getTypeFamily($type)
    {
        if (is_array($type)) {
            $r = [];
            foreach ($type as $key => $t) {
                $r[$key] = $this->getTypeFamily($t);
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
                case ($type == 'file'):
                    $r = 4; // file
                    break;
                case (strpos($this->DATSARR, $type) !== false):
                    $r = 5; // date string
                    break;
                default:
                    $r = 0; // number
            }
        }
        return $r;
    }

    /**
     * It sets the type for the current operation. The default type is 'string'<br>
     * This value important to validate the information.<br>
     * <b>Example:</b> $valid->type('integer')->set(20);<br>
     *
     * @param string|array $type =['integer','unixtime','boolean','decimal','float','varchar','string'
     *                           ,'date','datetime','datestring','datetimestring'][$i]
     *
     * @return ValidationOne $this
     */
    public function type($type)
    {
        if (is_array($type)) {
            $this->typeFams = $this->getTypeFamily($type);
            $this->types = $type;
        } else {
            $this->typeFam = $this->getTypeFamily($type);
            $this->type = $type;

        }
        return $this;
    }

    /**
     * @param string $condition =['alpha','alphanum','between','betweenlen','contain','doc','domain','email','eq','ext'
     *                          ,'false','gt','gte','image','lt','lte','maxlen','maxsize','minlen','minsize','ne'
     *                          ,'notcontain','notnull','null','regexp','req','text','true','url','fn.*'][$i]
     *                          <b>number</b>:req,eq,ne,gt,lt,gte,lte,between,null,notnull<br>
     *                          <b>string</b>:req,eq,ne,minlen,maxlen,betweenlen,null,notnull,contain,notcontain
     *                          ,alpha,alphanum,text,regexp,email,url,domain<br>
     *                          <b>date</b>:req,eq,ne,gt,lt,gte,lte,between<br>
     *                          <b>datestring</b>:req,eq,ne,gt,lt,gte,lte,between<br>
     *                          <b>boolean</b>:req,eq,ne,true,false<br>
     *                          <b>file</b>:minsize,maxsize,req,image,doc,ext<br>
     *                          <b>function:</b><br>
     *                          fn.static.Class.methodstatic<br>
     *                          fn.global.function<br>
     *                          fn.object.Class.method where object is a global $object<br>
     *                          fn.class.Class.method<br>
     *                          fn.class.\namespace\Class.method<br>
     * @param string $message   <br>
     *                          Message could uses the next variables '%field','%realfield','%value','%comp','%first','%second'
     * @param null   $conditionValue Value used for some conditions. This value could be an array too.
     * @param string $level (error,warning,info,success)
     * @param null   $key If key is not null then it is used for add more than one condition by key
     *
     * @return ValidationOne
     */
    public function condition($condition, $message = "", $conditionValue = null, $level = 'error', $key = null)
    {
        if (strpos($this->DATSARR, $this->type) !== false) {
            $conditionValue = $this->inputToDate($conditionValue);
        }
        if ($key !== null) {
            $this->conditions[$key][] = new ValidationItem($condition, $message, $conditionValue, $level);
        } else {
            $this->conditions[] = new ValidationItem($condition, $message, $conditionValue, $level);
        }
        return $this;

    }

    /**
     * It resets the chain (if any)
     * It also reset any validating pending to be executed.
     */
    public function resetChain()
    {

        $this->default = null;
        $this->initial = null;

        $this->type = 'string'; // it's important, string is the default value because it's not processed.
        $this->typeFam = 1; // string
        $this->isArray = false;
        $this->abortOnError = true;
        $this->isArrayFlat = false;
        $this->isColumn = false;
        $this->hasMessage = false;
        $this->ifFailThenDefault = $this->defaultIfFail;
        $this->ifFailThenOrigin = false;
        $this->conditions = [];
        $this->override = false;
        $this->resetValidation();
        $this->required = $this->defaultRequired;
        $this->friendId = null;
        $this->successMessage = null;
        $this->isMissing = false;
        $this->countError = 0;
        $this->addToForm = false;
    }

    /**
     * Use future.
     */
    public function store()
    {
        $id = 1;
        $this->container[$id] = [];
        $new =& $this->container[$id]; //it's an instance
        $new['isArray'] = $this->isArray;
    }


    //</editor-fold>

    /**
     * You could add a message (including errors,warning..) and store in a $id
     * It is a wrapper of $this->messageList->addItem
     *
     * @param string $id      Identified of the message (where the message will be stored
     * @param string $message message to show. Example: 'the value is incorrect'
     * @param string $level   =['error','warning','info','success'][$i]
     */
    public function addMessage($id, $message, $level = 'error')
    {
        $this->messageList->addItem($id, $message, $level);
    }

    /**
     * It cleans the stacked validations. It doesn't delete the errors.
     */
    public function resetValidation()
    {
        $this->conditions = array();
    }

    //<editor-fold desc="fetch and end of chain commands">

    private function afterFetch($input, $fieldId, $msg)
    {
        if (!$this->isMissing) {
            if ($this->ifFailThenOrigin) {
                $this->default = $input;
            }
            if (!$this->isArray) {
                
                $this->originalValue = $input;
                $input = $this->basicValidation($input, $fieldId, $msg);
        
                
                if (is_array($input)) {
                    foreach ($input as $key => &$items) {
                        $currentField = ($this->isArrayFlat) ? $fieldId : $fieldId . "[" . $key . "]";
                        $this->runConditions($items, $currentField, $key);

                        if ($this->ifFailThenDefault) {
                            if ($this->messageList->get($currentField)->countError()) {
                                $items = (is_array($this->default)) ? $this->default[$key] : $this->default;
                            }
                        }
                    }
                }
                $output=$this->endConversion($input);
            } else {
                $this->originalValue = $input;
                if (is_array($input) || $input === null) {
                    if ($input !== null) {
                        foreach ($input as $key => &$v) {
                            $currentField = ($this->isArrayFlat) ? $fieldId : $fieldId . "[" . $key . "]";
                            $v = $this->basicValidation($v, $currentField, $msg, $key);
                        }
                    }
                } else {
                    $this->addMessageInternal('%field is not an array', '', $fieldId, 0, 'error');
                }
                $this->runConditions($input, $fieldId);
                if ($this->ifFailThenDefault) {
                    if ($this->messageList->errorcount) {
                        $input =  $this->default;
                    }
                }
                $output=$input;
            } // isArray
            
        } else {
            // we convert the input into a datetime object.
            //$input=$this->endConversion( $this->inputToDate($input));

            $output=$input;
        } // is missing
        if ($this->messageList->errorcount == $this->countError && $this->successMessage !== null) {
            $this->addMessage($this->successMessage['id'], $this->successMessage['msg'],
                $this->successMessage['level']);
        }
        if ($this->addToForm) {
            $this->callFormBack($fieldId);
        }
        
        $this->resetChain();
        return $output;
    }

    /**
     * @param mixed $input
     *
     * @return null
     */
    private function endConversion($input) {
        // end conversion, we convert the input or default value.
        if($input!==null) {
            switch ($this->type) {
                case 'datestring':
                    $output = $input->format($this->dateOutputString);
                    break;
                case 'datetimestring':
                    $output = $input->format($this->dateLongOutputString);
                    break;
                default:
                    $output = $input;
            }
        } else {
            $output=null;
        }
        return $output;
    }

    /**
     * It's a callback to the form if it's defined.<br>
     * It's used to inform to the form that the validation chain is ready to send validation to the visual layer.
     *
     * @param $fieldId
     */
    private function callFormBack($fieldId)
    {
        if ($this->formOne !== null) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->formOne->callBack($this, $fieldId);
        }
    }

    /**
     * It converts a string into a DateTime object
     *
     * @param string $input
     *
     * @return bool|DateTime If the operation fails, then it returns false
     */
    private function inputToDate($input)
    {
        if (is_string($input)) {
            switch ($this->type) {
                case 'date':
                case 'datestring':
                    $value = DateTime::createFromFormat($this->dateShort, $input);
                    if ($value === false) {
                        return $value;
                    }
                    $value->settime(0, 0);
                    break;
                case 'datestringx':
                    $value = DateTime::createFromFormat($this->dateShort, $input);
                    if ($value === false) {
                        return $value;
                    }
                    $value->settime(0, 0);
                    $value=$value->format($this->dateOutputString);
                    break;
                case 'datetime':
                case 'datetimestring':
                    $value = DateTime::createFromFormat($this->dateLong, $input);
                    break;
                case 'datetimestringxx':
                    $value = DateTime::createFromFormat($this->dateLong, $input)->format($this->dateLongOutputString);
                    break;
                default:
                    $value = $input;
            }
        } else {
            $value = $input;
        }
        return $value;
    }

    /**
     * It is an alternative to get(), post() and request(). It reads from the memory.
     * 
     * @param mixed  $input   Input data.
     * @param string $fieldId (optional)
     * @param string $msg     Used for the initial (basic) validation of the data.
     * @param bool   $isMissing
     *
     * @return array|bool|DateTime|float|int|mixed|null
     */
    public function set($input, $fieldId = "setfield", $msg = "", &$isMissing = false)
    {
        $this->isMissing = $isMissing;
        if ($this->override) {
            $this->messageList->items[$fieldId] = new MessageItem();
        }
        if (is_object($input)) {
            $input = (array)$input;
        } 
        $this->countError = $this->messageList->errorcount;
        if (is_array($input)) {
            foreach ($input as $key => &$v) {
                $this->originalValue = $v;
                $currentField = ($this->isArrayFlat) ? $fieldId : $fieldId . "[" . $key . "]";
                $v = $this->basicValidation($v, $currentField, $msg, $key);
                //if ($this->abortOnError && $this->messageList->errorcount) break;
                $this->runConditions($v, $currentField, $key);
                if ($this->messageList->errorcount === 0) {
                    if ($this->messageList->get($currentField)->countError()) {
                        $v = @$this->default[$key];
                    }
                }
            }
        } else {
            $this->originalValue = $input;
            $input = $this->basicValidation($input, $fieldId, $msg);
            if ($this->abortOnError != false || $this->messageList->errorcount == 0) {
                $this->runConditions($input, $fieldId);
            }
            if ($this->ifFailThenDefault) {
                if ($this->messageList->get($fieldId)->countError()) {
                    $input = $this->default;
                }
            }
        }
        if ($this->messageList->errorcount == $this->countError && $this->successMessage !== null) {
            $this->addMessage($this->successMessage['id'], $this->successMessage['msg'],
                $this->successMessage['level']);
        }
        if ($this->addToForm) {
            $this->callFormBack($fieldId);
        }
        $output=$this->endConversion($input);
        $this->resetChain();
        return $output;
    }
    //</editor-fold>

    //<editor-fold desc="conditions">
    /**
     * @param int            $r      timestamp of the date/time
     * @param ValidationItem $cond   Where cond->value equals to the timestamp of the date/time
     * @param boolean        $fail   True if the operation fails
     * @param string         $genMsg If it fails, it returns a message.
     */
    private function runNumericCondition($r, $cond, &$fail, &$genMsg)
    {
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
                if ($r !== null) {
                    $fail = true;
                    $genMsg = '%field is noy null';
                }
                break;
            case 'notnull':
                if ($r === null) {
                    $fail = true;
                    $genMsg = '%field is null';
                }
                break;

        }
    }

    /**
     * @param int            $r      timestamp of the date/time
     * @param ValidationItem $cond   Where cond->value equals to the timestamp of the date/time
     * @param boolean        $fail   True if the operation fails
     * @param string         $genMsg If it fails, it returns a message.
     */
    private function runStringCondition($r, $cond, &$fail, &$genMsg)
    {

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
                if (strpos($r, $cond->value) === false) {
                    $fail = true;
                    $genMsg = '%field contains %comp';
                }
                break;
            case 'notcontain':
                if (strpos($r, $cond->value) !== false) {
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
                if (!preg_match('^[\p{L}| |.|\/|*|+|.|,|=|_|"|\']+$', $r)) {
                    $fail = true;
                    $genMsg = '%field has characters not allowed';
                }
                break;
            case 'regexp':
                if (!preg_match($cond->value, $r)) {
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
                if ($r !== null) {
                    $fail = true;
                    $genMsg = '%field is noy null';
                }
                break;
            case 'notnull':
                if ($r === null) {
                    $fail = true;
                    $genMsg = '%field is null';
                }
                break;
            default:
                trigger_error("type not defined {$cond->type} for string");
        }

    }

    /**
     * @param int            $r      timestamp of the date/time
     * @param ValidationItem $cond   Where cond->value equals to the timestamp of the date/time   Where cond->value equals to the timestamp of the date/time
     * @param boolean        $fail   True if the operation fails
     * @param string         $genMsg If it fails, it returns a message.
     */
    private function runDateCondition($r, $cond, &$fail, &$genMsg)
    {
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
     * @param int            $r      timestamp of the date/time
     * @param ValidationItem $cond   Where cond->value equals to the timestamp of the date/time
     * @param boolean        $fail   True if the operation fails
     * @param string         $genMsg If it fails, it returns a message.
     */
    private function runBoolCondition($r, $cond, &$fail, &$genMsg)
    {
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
     * Get the extension (without) dot of a file always in lowercase
     *
     * @param string $path
     *
     * @return string mixed
     */
    private function getFileExtension($path)
    {
        if (empty($path)) {
            return '';
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        return strtolower($ext);
    }

    /**
     * @param                $value  =['req','minsize','maxsize','image','doc','ext'][$i]
     * @param ValidationItem $cond   Where cond->value equals to the timestamp of the date/time
     * @param boolean        $fail
     * @param string         $genMsg (default error message, it could be replaced
     *                               if there is a message for this condition)
     */
    private function runFileCondition($value, $cond, &$fail, &$genMsg)
    {
        $fileName = @$value[0];
        $fileNameTmp = @$value[1];
        switch ($cond->type) {
            case 'req':
                if (!$fileName) {
                    $fail = true;
                    $genMsg = '%field is required';
                }
                break;
            case 'minsize':
                $size = filesize($fileNameTmp);
                if ($size < $cond->value) {
                    $fail = true;
                    $genMsg = '%field is small than %comp';
                }
                break;
            case 'maxsize':
                $size = filesize($fileNameTmp);
                if ($size > $cond->value) {
                    $fail = true;
                    $genMsg = '%field is big than %comp';
                }
                break;
            case 'image':
                $verifyimg = @getimagesize($fileNameTmp);
                if (!$verifyimg) {
                    $fail = true;
                    $genMsg = '%field is not a right image';
                } else {
                    $ext = $this->getFileExtension($fileName);
                    if (!in_array($ext, ['jpg', 'png', 'gif', 'jpeg'])) {
                        $fail = true;
                        $genMsg = '%field is not allowed';
                    }
                }
                break;
            case 'doc':
                $ext = $this->getFileExtension($fileName);
                if (!in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'xlsxm', "ppt", "pptx"])) {
                    $fail = true;
                    $genMsg = '%field is not allowed';
                }
                break;
            //minsize,maxsize,req,image,doc,ex
            case 'ext':
                $ext = $this->getFileExtension($fileName);
                if (!in_array($ext, $cond->value)) {
                    $fail = true;
                    $genMsg = '%field is not allowed';
                }
                break;
            default:
                $fail = true;
                $genMsg = '%field has an incorrect condition';
                break;
        }

    }

    /**
     * @param int            $r      timestamp of the date/time
     * @param ValidationItem $cond   Where cond->value equals to the timestamp of the date/time
     * @param boolean        $fail   True if the operation fails
     * @param string         $genMsg If it fails, it returns a message.
     */
    private function runFnCondition($r, $cond, &$fail, &$genMsg)
    {
        // is a function
        $arr = explode(".", $cond->type);
        switch ($arr[1]) {
            case 'static':
                // fn.static.Class.method
                try {
                    $reflectionMethod = new ReflectionMethod($arr[2], $arr[3]);
                    $fail = !$reflectionMethod->invoke(null, $r, $cond->value);
                } catch (Exception $e) {
                    $fail = true;
                    $genMsg = $e->getMessage();
                }
                break;
            case 'global':
                // fn.global.method
                try {
                    $fail = !@call_user_func($arr[2], $r, $cond->value);
                } catch (Exception $e) {
                    $fail = true;
                    $genMsg = $e->getMessage();
                }
                break;
            case 'object':
                //  0.     1.   2.     3
                // fn.object.$arr.method
                try {
                    if (!isset($GLOBALS[$arr[2]])) {
                        throw new Exception("variable {$arr[2]} not defined as global");
                    }
                    $obj = $GLOBALS[$arr[2]];
                    $reflectionMethod = new ReflectionMethod(get_class($obj), $arr[3]);
                    $fail = !$reflectionMethod->invoke($obj, $r, $cond->value);
                } catch (Exception $e) {
                    $fail = true;
                    $genMsg = $e->getMessage();
                }
                break;
            case 'class':
                //  0.     1.   2.     3
                // fn.class.ClassName.method
                try {
                    $className = $arr[2];
                    if (function_exists('get' . $className)) {
                        // we try to call the function getClass();
                        $obj = call_user_func('get' . $className);
                        $reflectionMethod = new ReflectionMethod(null, 'get' . $className);
                        $called = $reflectionMethod->invoke(null);
                        if ($called === null || $called === false) {
                            throw new Exception("unable to call injection");
                        }
                    } else {
                        $obj = new $className();
                    }
                    $reflectionMethod = new ReflectionMethod($className, $arr[3]);
                    $fail = !$reflectionMethod->invoke($obj, $r, $cond->value);
                } catch (Exception $e) {
                    $fail = true;
                    $genMsg = $e->getMessage();
                }
                break;
            default:
                trigger_error("validation fn not defined");
        }
    }

    private function runConditions($value, $fieldId, $key = null)
    {
        $genMsg = '';
        if ($key === null || $this->isColumn) {
            foreach ($this->conditions as $cond) {
                $fail = false;
                if (strpos($cond->type, "fn.") === 0) {
                    // if it starts with fn. then it's a function condition
                    $this->runFnCondition($value, $cond, $fail, $genMsg);
                } else {
                    //
                    //(function(){return ['integer','unixtime','boolean','decimal','float','varchar','string','date','datetime','datestring','datetimestring'][$i];})();
                    switch ($this->type) {
                        case 'integer':
                        case 'unixtime':
                        case 'decimal':
                        case 'float':
                            // number
                            $this->runNumericCondition($value, $cond, $fail, $genMsg);
                            break;
                        case 'varchar':
                        case 'string': // string
                            $this->runStringCondition($value, $cond, $fail, $genMsg);
                            break;
                        case 'datestring':
                        case 'datetimestring':// datestring                            
                        case 'date':
                        case 'datetime':// date
                            if ($value instanceof DateTime) {
                                $value = $value->getTimestamp();
                                $condCopy = clone $cond;
                                if ($condCopy->value instanceof DateTime) {
                                    $condCopy->value = $condCopy->value->getTimeStamp();
                                }

                                $this->runDateCondition($value, $cond, $fail, $genMsg);
                            }
                            break;
                        case 'boolean': // bool
                            $this->runBoolCondition($value, $cond, $fail, $genMsg);
                            break;
                        case 'file': // file
                            $this->runFileCondition($value, $cond, $fail, $genMsg);
                            break;


                    } // switch
                }
                if ($fail) {
                    $this->addMessageInternal($cond->msg, $genMsg, $fieldId, $value, $cond->value, $cond->level, $key);
                    if (!$this->abortOnError) {
                        break; // no continue anymore.
                    }
                }
            } //foreach
        } else {
            if (isset($this->conditions[$key])) {
                $fail = false;
                foreach ($this->conditions[$key] as $cond) {

                    if (strpos($cond->type, "fn.") === 0) {
                        // if it starts with fn. then it's a function condition
                        $this->runFnCondition($value, $cond, $fail, $genMsg);
                    } else {
                        switch ($this->typeFams[$key]) {
                            case 'integer':
                            case 'unixtime':
                            case 'decimal':
                            case 'float':
                                $this->runNumericCondition($value, $cond, $fail, $genMsg);
                                break;
                            case 'varchar':
                            case 'string': // string
                                $this->runStringCondition($value, $cond, $fail, $genMsg);
                                break;
                            case 'date':
                            case 'datetime':// date
                                $this->runDateCondition($value, $cond, $fail, $genMsg);
                                break;
                            case 'boolean': // bool
                                $this->runBoolCondition($value, $cond, $fail, $genMsg);
                                break;
                            case 'file': // file
                                $this->runFileCondition($value, $cond, $fail, $genMsg);
                                break;
                            case 'datestring':
                            case 'datetimestring':// datestring
                                $this->runDateCondition($value, $cond, $fail, $genMsg);
                                break;
                        } // switch
                    }
                    if ($fail) {
                        $this->addMessageInternal($cond->msg, $genMsg, $fieldId, $value, $cond->value, $cond->level,
                            $key);
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
     * It is the basic validation based on the type of data.<br>
     * It could converts the input data depending on the conditions, requirements, etc.
     * 
     * @param mixed  $input Input value unmodified.
     * @param string $field id of the field
     * @param string $msg Default message
     * @param null   $key key value. It is used if the value is an array.
     *
     * @return bool|DateTime|float|int|mixed|null  Returns the input modified.
     */
    public function basicValidation($input, $field, $msg = "", $key = null)
    {
        if ($this->ifFailThenDefault) {
            $localDefault = (is_array($this->default)) ? @$this->default[$key] : $this->default;
        } else {
            $localDefault = null;
        }
        if ($key !== null && isset($this->types[$key])) {
            $type = $this->types[$key];
            $value = $input;
        } else {
            $type = $this->type;
            $value = $input;
        }
        switch ($type) {
            case 'integer':
            case 'unixtime':
                if (!is_numeric($value) && $value !== '') {
                    $this->hasMessage = true;
                    $this->addMessageInternal($msg, '%field is not numeric', $field, $value, null, 'error', $key);
                    return $localDefault;
                }
                return (int)$value;
                break;
            case 'boolean':
                return (bool)$value;
                break;
            case 'decimal':
                if (!is_numeric($value) && $value !== '') {
                    $this->hasMessage = true;
                    $this->addMessageInternal($msg, '$field is not decimal', $field, $value, null, 'error');
                    return $localDefault;
                }
                return (double)$value;
                break;
            case 'float':
                if (!is_numeric($value) && $value !== '') {
                    $this->hasMessage = true;
                    $this->addMessageInternal($msg, '$field is not float', $field, $value, null, 'error');
                    return $localDefault;
                }
                return (float)$value;
                break;
            case 'varchar':
            case 'string':
                // if string is empty then it uses the default value. It's useful for filter
                return ($value === "") ? $localDefault : $value;
                break;
            case 'date':
            case 'datestring':
            case 'datetime':
            case 'datetimestring':
                
                if(is_string($value) && !$value && $this->required===false) {
                    $valueDate=$this->inputToDate($localDefault); // we return the local value unmodified
                    return $valueDate;
                }
                $valueDate = ($value instanceof DateTime) 
                    ? $value 
                    : DateTime::createFromFormat($this->dateLong, $value);
            
                if ($valueDate === false) {
                    
                    // the format is not date and time, maybe it's only date
                    /** @var DateTime $valueDate */
                    $valueDate = DateTime::createFromFormat($this->dateShort, $value);
                    if ($valueDate === false) {
                        // nope, it's neither date and it is required
                        $this->hasMessage = true;
                        $this->addMessageInternal($msg, '%field is not a date', $field, $value, null, 'error');
                        $tmpOutput=($localDefault instanceof DateTime) 
                            ? $localDefault 
                            : DateTime::createFromFormat($this->dateLong, $localDefault);
                        if ($tmpOutput === false) {
                            $tmpOutput = DateTime::createFromFormat($this->dateShort, $localDefault);
                            if($tmpOutput!=false) {
                                $tmpOutput->settime(0, 0, 0);    
                            } else {
                                $tmpOutput=null;
                            }
                        }    
                        return $tmpOutput;
                    }
                    $valueDate->settime(0, 0, 0); // datetime without time
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
     *
     * @param string $msg     first message. If it's empty or null then it uses the second message<br>
     *                        Message could uses the next variables. Ex "%field is empty"<br>
     *                        %field = name of the field, it could be the friendid or the actual name<br>
     *                        %realfield = name of the field (not the friendid)<br>
     *                        %value = current value of the field<br>
     *                        %comp = value to compare (if any)<br>
     *                        %first = first value to compare (if the compare value is an array)<br>
     *                        %second = second value to compare (if the compare value is an array)<br>
     *                        %key = key used (for input array)<br>
     * @param string $msg2    second message
     * @param string $fieldId id of the field
     * @param mixed  $value   value supplied
     * @param mixed  $vcomp   value to compare.
     * @param string $level   (error,warning,info,success) error level
     * @param null   $key
     */
    private function addMessageInternal($msg, $msg2, $fieldId, $value, $vcomp, $level = 'error', $key = null)
    {
        $txt = ($msg) ? $msg : $msg2;
        if (is_array($vcomp)) {
            $first = @$vcomp[0];
            $second = @$vcomp[1];
            $vcomp = @$vcomp[0]; // is not array anymore
        } else {
            $first = $vcomp;
            $second = $vcomp;
        }
        if (is_array($this->originalValue)) {
            $txt = str_replace(['%field', '%realfield', '%value', '%comp', '%first', '%second', '%key']
                , [
                    ($this->friendId === null) ? $fieldId : $this->friendId,
                    $fieldId
                    ,
                    is_array($value) ? "[]" : $value,
                    $vcomp,
                    $first,
                    $second,
                    $key
                ], $txt);
            //$this->originalValue=$value;
        } else {
            $txt = str_replace(['%field', '%realfield', '%value', '%comp', '%first', '%second', '%key']
                , [
                    ($this->friendId === null) ? $fieldId : $this->friendId,
                    $fieldId,
                    $this->addMessageSer($this->originalValue),
                    $this->addMessageSer($vcomp),
                    $this->addMessageSer($first),
                    $this->addMessageSer($second),
                    $key
                ], $txt);
        }
        $this->messageList->addItem($fieldId, $txt, $level);
    }

    private function addMessageSer($value)
    {
        if ($value instanceof DateTime) {
            return $value->format('c');
        }
        if (is_object($value)) {
            return json_encode($value);
        }
        return $value;
    }

    /**
     * It gets the first error message available in the whole messagelist.
     *
     * @param bool $withWarning
     *
     * @return null|string
     */
    public function getMessage($withWarning = false)
    {
        if ($withWarning) {
            return $this->messageList->firstErrorOrWarning();
        }
        return $this->messageList->firstErrorText();
    }

    /**
     * It returns an array with all the errors of all "ids"
     *
     * @param bool $withWarning
     *
     * @return array
     */
    public function getMessages($withWarning = false)
    {
        if ($withWarning) {
            $this->messageList->allErrorOrWarningArray();
        }
        return $this->messageList->allErrorArray();
    }

    /**
     * It returns the error of the element "id".  If it doesn't exist then it returns an empty MessageItem
     *
     * @param string $id
     *
     * @return MessageItem
     */
    public function getMessageId($id)
    {
        return $this->messageList->get($id);
    }
    //</editor-fold>

}