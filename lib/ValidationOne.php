<?php /** @noinspection PhpUndefinedClassInspection */

/** @noinspection UnknownInspectionInspection */

namespace eftec;

use DateTime;
use Exception;
use JsonException;
use ReflectionMethod;
use RuntimeException;

if (!defined("NULLVAL")) {
    /**  Sometimes we want to set an empty as empty. For example <select><option> this nullval is equals to null */
    define('NULLVAL', '__nullval__');
}

/**
 * Class Validation
 *
 * @package       eftec
 * @author        Jorge Castro Castillo
 * @version       2.9 2024-03-02
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see           https://github.com/EFTEC/ValidationOne
 */
class ValidationOne
{
    public const VERSION='2.9';
    /** @var string It is the (expected) input format for date (short) */
    public string $dateShort = 'd/m/Y';
    /** @var string It is the (expected) input format (with date and time) */
    public string $dateLong = 'd/m/Y H:i:s';
    /** @var string It is the output format (for datestring) */
    public string $dateOutputString = 'Y-m-d';
    /** @var string It is the output format (for datetimestring) */
    public string $dateLongOutputString = 'Y-m-d\TH:i:s\Z';
    /** @var MessageContainer the container of the messages */
    public MessageContainer $messageList;

    /** @var ValidationInputOne|null */
    public ?ValidationInputOne $input = null;
    /** @var bool if debug then it fills an array called debugLog */
    public bool $debug = false;

    //private $NUMARR='integer,unixtime,boolean,decimal,float';
    /** @var ValidationItem[] */
    public array $conditions = [];
    /** @var string Prefix used for the input */
    public string $prefix;
    private string $STRARR = 'varchar,string';
    //<editor-fold desc="chain variables">
    /** @var string members of the family DATE */
    private string $DATARR = 'date,datetime';
    /** @var string members of the family DATESTRING */
    private string $DATSARR = 'datestring,datetimestring';
    /** @var mixed default value */
    private $default;
    /** @var mixed */
    private $initialValue;
    /** @var string=['integer','unixtime','boolean','decimal','float','varchar','string','date','datetime','datestring','datetimestring'][$i] */
    private string $type = 'string';
    /** @var array used to store types (if the input is an array) */
    private array $types = [];
    /** @var int=[0,1,2,3,4,5][$i] Family of types 0=number,1=string,2=date,3=boolean,4=file,5=datestring */
    private int $typeFam = 1;
    /** @var mixed|int=[0,1,2,3,4,5][$i] Family of types (for arrays). See self::$types */
    private  $typeFams = 1;
    /** @var bool if an error happens, then the next validations are not executed */
    private bool $abortOnError = false;
    /** @var bool if the value is an array or not */
    private bool $isArray = false;
    /** @var bool if the value is an array or not */
    private bool $isColumn = false;
    /** @var bool if true then the errors from id[0],id[1] ared stored in "idx" */
    private bool $isArrayFlat = false;
    /**
     * @var bool     If the value is an array but the indexes of the columns are inverted with the columns, then you can
     *               invert the order<br>
     *               <b>(false, no conversion)</b>: ['col1'=>['cocacola','fanta'],'col2'=>[1,2]]<br>
     *               <b>(true)</b>: [['col1'=>'cocacola','col2'=>1],['col1'=>'fanta','col2'=>2]]<br>
     */
    private bool $invertIndexRow = false;
    /** @var bool TODO */
    private bool $hasMessage = false;
    /** @var bool if the validation fails then it returns the default value */
    private bool $ifFailThenDefault = false;
    private bool $isNullValid = false;
    private bool $isEmptyValid = false;
    private bool $isMissingValid = false;
    /** @var bool if the validation fails then it returns the original (input) value */
    private bool $ifFailThenOrigin = false;
    /** @var null|array */
    private ?array $successMessage;
    /** @var bool It overrides previous errors (for the "id" used) */
    private bool $override = false;
    /** @var bool If true then the field exists otherwise it generates an error */
    private bool $exist = false;
    /** @var array The conversion stack */
    private array $conversion = [];
    private bool $alwaysTrim = false;
    private string $alwaysTrimChars = " \t\n\r\0\x0B";
    /** @var mixed It keeps a copy of the original value (after get/post/fetch or set) */
    private $originalValue;
    /** @var string|null It's a friendly id used to replace the "id" used in message. For example: "id customer" instead of "idcustomer" */
    private ?string $friendId;
    /** @var null|mixed It is the value used if the value is null or empty. If null then the value is not changed. */
    private $missingSet;

    /**
     * @var FormOne|null It is an optional feature that uses FormOne. It's used for callback.
     * @see          https://github.com/EFTEC/FormOne
     * @noinspection PhpUndefinedClassInspection
     */
    private ?FormOne $formOne;
    private bool $addToForm = false;
    /** @var bool if true and the validation fails, then it returns the default value */
    private bool $defaultIfFail = false;
    private bool $defaultRequired = false;
    /** @var bool value is missing */
    private bool $isMissing = false;
    /* @var int internal counter of error per chain */
    private int $countError=0;
    private bool $throwOnError = false;
    private bool $throwOnWarning = false;

    //</editor-fold>

    /**
     * Validation constructor.
     *
     * @param string $prefix Prefix used for the input. For example "frm_"
     */
    public function __construct(string $prefix = '')
    {
        // autowire an instance or create a new one.
        $this->messageList = MessageContainer::instance();
        $this->prefix = $prefix;
        $this->resetChain();
    }

    /**
     * It resets the chain (if any)<br>
     * It also reset any validating pending to be executed.<br>
     * <b>Note:</b> It does not delete the messages (if any)
     */
    public function resetChain(): void
    {
        $this->default = null;
        $this->initialValue = null;
        $this->type = 'string'; // it's important, string is the default value because it's not processed.
        $this->typeFam = 1; // string
        $this->isArray = false;
        $this->abortOnError = true;
        $this->isArrayFlat = false;
        $this->isColumn = false;
        $this->hasMessage = false;
        $this->ifFailThenDefault = $this->defaultIfFail;
        $this->isNullValid = false;
        $this->isEmptyValid = false;
        $this->isMissingValid = false;
        $this->ifFailThenOrigin = false;
        $this->conditions = [];
        $this->override = false;
        $this->resetValidation();
        $this->exist = $this->defaultRequired;
        $this->conversion = [];
        $this->friendId = null;
        $this->successMessage = null;
        $this->isMissing = false;
        $this->countError = 0;
        $this->addToForm = false;
        $this->missingSet = null;
        if ($this->throwOnError && $this->errorCount() > 0) {
            $errors = $this->messageList->allErrorArray();
            $this->throwOnError = false;
            $this->throwOnWarning = false;
            throw new RuntimeException(end($errors)); // it throws the latest error
        }
        if ($this->throwOnWarning && $this->messageList->warningCount > 0) {
            $warnings = $this->messageList->allWarningArray();
            $this->throwOnError = false;
            $this->throwOnWarning = false;
            throw new RuntimeException(end($warnings)); // it throws the latest warning
        }
        $this->throwOnError = false;
        $this->throwOnWarning = false;
    }

    /**
     * It cleans the stacked validations. It also could delete the messages
     *
     * @param bool $deleteMessage [default] if true then it deletes all messages (by default it's false).<br>.
     */
    public function resetValidation(bool $deleteMessage = false): void
    {
        if ($deleteMessage) {
            $this->messageList->resetAll();
        }
        $this->conditions = array();
    }

    /**
     * If we store an error then we also throw a PHP exception.
     *
     * @param bool    $throwOnError   if true (default), then it throws an excepcion every time
     *                                we store an error.
     * @param boolean $includeWarning If true then it also includes warnings.
     * @return ValidationOne
     */
    public function throwOnError(bool $throwOnError = true, bool $includeWarning = false): ValidationOne
    {
        $this->throwOnError = $throwOnError;
        $this->throwOnWarning = $includeWarning;
        return $this;
    }

    /**
     * It sets the input values (datestring and datetimestring) in "m/d/Y" and "m/d/Y H:i:s" format instead of "d/m/Y"
     * and "d/m/Y H:i:s" <br> The output is still "Y-m-D" and 'Y-m-d\TH:i:s\Z'<br> This configuration persists across
     * different calls, so you could set it once (during the configuration).
     *
     * @return $this
     */
    public function setDateFormatEnglish(): ValidationOne
    {
        $this->setDateFormat('m/d/Y', 'm/d/Y H:i:s', 'Y-m-d', 'Y-m-d\TH:i:s\Z');
        return $this;
    }

    /**
     * It sets the date format (for input and output).<br>
     * Input is the expected value to fetch<br>
     * Output is the result of the value<br>
     *
     * @param string|null $dateInput      Example 'd/m/Y'
     * @param string|null $dateTimeInput  Example 'd/m/Y H:i:s'
     * @param string|null $dateOutput     Example 'Y-m-d' (used for datestring and datetimestring)
     * @param string|null $dateTimeOutput Example 'Y-m-d\TH:i:s\Z' (used for datestring and datetimestring)
     *
     * @return $this
     */
    public function setDateFormat(
        ?string $dateInput = null,
        ?string $dateTimeInput = null,
        ?string $dateOutput = null,
        ?string $dateTimeOutput = null
    ): ValidationOne
    {
        if ($dateInput !== null) {
            $this->dateShort = $dateInput;
        }
        if ($dateTimeInput !== null) {
            $this->dateLong = $dateTimeInput;
        }
        if ($dateOutput !== null) {
            $this->dateOutputString = $dateOutput;
        }
        if ($dateTimeOutput !== null) {
            $this->dateLongOutputString = $dateTimeOutput;
        }
        return $this;
    }

    /**
     * It sets the input values (datestring and datetimestring) in "d/m/Y" and "d/m/Y H:i:s" format<br>
     * It is the default value.
     *
     * @return $this
     */
    public function setDateFormatDefault(): ValidationOne
    {
        $this->setDateFormat('d/m/Y', 'd/m/Y H:i:s', 'Y-m-d', 'Y-m-d\TH:i:s\Z');
        return $this;
    }

    /**
     * @param string      $field
     * @param string|null $msg
     *
     * @return array|bool|DateTime|float|int|mixed|null
     * @throws JsonException
     */
    public function get(string $field = "", ?string $msg = null)
    {
        return $this->endChainFetch(1, $field, $msg);
    }

    /**
     * It ends the fetch of the information. It doesn't modify this information
     *
     * @param int         $inputType INPUT_POST(0)|INPUT_GET(1)|INPUT_REQUEST(99)
     * @param string      $fieldId
     * @param string|null $msg
     *
     * @return array|bool|DateTime|float|int|mixed|null
     * @throws JsonException
     */
    private function endChainFetch(int $inputType, string $fieldId, ?string $msg = null)
    {
        $this->countError = $this->messageList->errorCount;
        if ($this->type === 'datestring' || $this->type === 'datetimestring') {
            // if the default value is a string and the input is expected a DateTime, then we convert it.
            if (is_string($this->default)) {
                $this->default = $this->inputToDate($this->default);
            }
        }
        $this->input()->default = $this->default;
        $this->input()->originalValue = $this->originalValue;
        $this->input()->ifFailThenOrigin = $this->ifFailThenOrigin;
        $this->input()->initial = $this->initialValue;
        foreach ($this->conditions as $c) {
            if ($c->type === "req") {
                $this->exist = true;
                break;
            }
        }
        $r = $this->input()
            ->exist($this->exist)
            ->friendId($this->friendId)
            ->getField($fieldId, $inputType, $msg, $this->isMissing);
        return $this->afterFetch($r, $fieldId, $msg);
    }

    /**
     * It converts a string into a DateTime object
     *
     * @param mixed $input
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
                        return false;
                    }
                    $value->settime(0, 0);
                    break;
                case 'datetime':
                case 'datetimestring':
                    $value = DateTime::createFromFormat($this->dateLong, $input);
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
     * it's the injector of validationinputone.
     *
     * @return ValidationInputOne
     */
    private function input(): ValidationInputOne
    {
        if ($this->input === null) {
            $this->input = ValidationInputOne::getInstance($this->prefix, $this->messageList); // we used the same message list
        }
        return $this->input;
    }

    /**
     * @param mixed       $input
     * @param mixed       $fieldId
     * @param string|null $msg
     * @return mixed|null
     * @throws JsonException
     */
    private function afterFetch($input, $fieldId, ?string $msg)
    {
        if ($this->missingSet !== null && ($input === null || $input === '')) {
            $input = $this->missingSet;
        }
        if($this->isArray && $this->invertIndexRow) {
            $input = self::invertArray($input);
        }
        //if (!$this->isMissing) {
        if ($this->ifFailThenOrigin) {
            $this->default = $input;
        }
        if ($this->isArray) {
            $this->originalValue = $input;
            if (is_array($input)) {
                if (!$this->isMissingValid || !$this->isMissing) { // bypass if missing is valid (and the value is missing)
                    foreach ($input as $key => &$v) {
                        $currentField = ($this->isArrayFlat) ? $fieldId : $fieldId . "[" . $key . "]";
                        $v = $this->basicValidation($v, $currentField, $msg, $key);
                    }
                    unset($v);
                }
            } else if ($input !== null) {
                // if the value is not array, but it is null, then we avoid showing a message (we consider it an empty array)
                $this->addMessageInternal('%field is not an array', '', $fieldId, 0, 'error');
            } else {
                // null are considered empty arrays.
                $input = [];
                $this->originalValue = $input;
            }
            if (!$this->isMissingValid || !$this->isMissing) { // bypass if missing is valid (and the value is missing)
                if (is_array($input)) {
                    foreach ($input as $key => &$items) {
                        $currentField = ($this->isArrayFlat) ? $fieldId : $fieldId . "[" . $key . "]";
                        $this->runConditions($items, $currentField, $key);

                        if ($this->ifFailThenDefault && $this->messageList->get($currentField)->countError()) {
                            $items = (is_array($this->default)) ? ($this->default[$key]??null) : $this->default;
                        }
                    }
                    unset($items);
                } else {
                    $this->runConditions($input, $fieldId);
                    if ($this->ifFailThenDefault && $this->messageList->get($fieldId)->countError()) {
                        $input = $this->default;
                    }
                }
            }
            //$output = $input;
        } else { // the value does not expect an array
            $this->originalValue = $input;
            if (!$this->isMissingValid || !$this->isMissing) {
                $input = $this->basicValidation($input, $fieldId, $msg);
                if (is_array($input)) {
                    foreach ($input as $key => &$items) {
                        $currentField = ($this->isArrayFlat) ? $fieldId : (string)$fieldId[$key];
                        $this->runConditions($items, $currentField, $key);
                        if ($this->ifFailThenDefault && $this->messageList->get($currentField)->countError()) {
                            $items = (is_array($this->default)) ? $this->default[$key] : $this->default;
                        }
                    }
                    unset($items);
                } else {
                    $this->runConditions($input, $fieldId);
                    if ($this->ifFailThenDefault && $this->messageList->get($fieldId)->countError()) {
                        $input = $this->default;
                    }
                }
            }
        }
        $output = $this->endConversion($input); // isArray
        /*} else {
            // we convert the input into a datetime object.
            //$input=$this->endConversion( $this->inputToDate($input));
            $output = $this->endConversion($input);
            //$output = $input;
        }*/ // is missing
        if ($this->messageList->errorCount === $this->countError && $this->successMessage !== null) {
            $this->messageList->addItem($this->successMessage['id'], $this->successMessage['msg'],
                $this->successMessage['level']);
        }
        if ($this->addToForm) {
            $this->callFormBack($fieldId);
        }
        $this->resetChain();
        return $output;
    }

    /**
     * If the value is an array but the indexes of the columns are inverted with the columns,
     * then you can invert the order<br>
     * **Example:**<br>
     * ```php
     * $arr=['col1'=>['cocacola','fanta'],'col2'=>[1,2]];
     * ValidationOne::invertArray($arr); // [['col1'=>'cocacola','col2'=>1],['col1'=>'fanta','col2'=>2]]
     * ```
     * @param array|null $input
     * @return array|null
     */
    public static function invertArray(?array $input):?array {
        if($input===null) {
            return null;
        }
        $inputFinal = [];
        foreach ($input as $kcol => $vcol) {
            if (is_array($vcol)) {
                foreach ($vcol as $kid => $vidx) {
                    $inputFinal[$kid][$kcol] = $vidx;
                }
            } else {
                $inputFinal[$kcol] = $vcol;
            }
        }
        return $inputFinal;
    }


    //<editor-fold desc="chain commands">

    /**
     * It is the basic validation based on the type of data.<br>
     * It could convert the input data depending on the conditions, requirements, etc.
     *
     * @param mixed       $input Input value unmodified.
     * @param string      $field id of the field
     * @param string|null $msg   See condition() for more information   Default message
     * @param mixed|null  $key   key value. It is used if the value is an array.
     *
     * @return bool|DateTime|float|int|mixed|null  Returns the input modified.
     * @throws JsonException
     */
    public function basicValidation($input, string $field, ?string $msg = "", $key = null)
    {
        if (($input === null && $this->isNullValid) || ($input === '' && $this->isEmptyValid)) {
            // bypass (null or empty value and is valid, nothing to evaluate).
            return $input;
        }
        if ($this->ifFailThenDefault) {
            if ((is_array($this->default))) {
                $localDefault = $this->default[$key] ?? null;
            } else {
                $localDefault = $this->default;
            }
        } else {
            $localDefault = null;
        }
        if ($key !== null && isset($this->types[$key])) {
            $type = $this->types[$key];
        } else {
            $type = $this->type;
        }
        $value = $input;
        switch ($type) {
            case 'integer':
            case 'unixtime':
                if (!is_numeric($value) && $value !== '') {
                    $this->hasMessage = true;
                    $this->addMessageInternal($msg, '%field is not numeric', $field, $value, null, 'error', $key);
                    return $localDefault;
                }
                return (int)$value;
            case 'boolean':
                return (bool)$value;
            case 'decimal':
                if (!is_numeric($value) && $value !== '') {
                    $this->hasMessage = true;
                    $this->addMessageInternal($msg, '$field is not decimal', $field, $value, null);
                    return $localDefault;
                }
                return (double)$value;
            case 'float':
                if (!is_numeric($value) && $value !== '') {
                    $this->hasMessage = true;
                    $this->addMessageInternal($msg, '$field is not float', $field, $value, null);
                    return $localDefault;
                }
                return (float)$value;
            case 'varchar':
            case 'string':
                // if string is empty then it uses the default value. It's useful for filter
                return ($value === "") ? $localDefault : $value;
            case 'date':
            case 'datestring':
            case 'datetime':
            case 'datetimestring':
                if (is_string($value) && !$value && $this->exist === false) {
                    // we return the local value unmodified
                    return $this->inputToDate($localDefault);
                }
                $valueDate = ($value instanceof DateTime) ? $value
                    : DateTime::createFromFormat($this->dateLong, $value ?? '');
                if ($valueDate === false) {
                    // the format is not date and time, maybe it's only date
                    /** @var DateTime|false $valueDate */
                    $valueDate = DateTime::createFromFormat($this->dateShort, $value ?? '');
                    if ($valueDate === false) {
                        // nope, it's neither date and it is required
                        $this->hasMessage = true;
                        $this->addMessageInternal($msg, '%field is not a date', $field, $value, null);
                        $tmpOutput = ($localDefault instanceof DateTime) ? $localDefault
                            : DateTime::createFromFormat($this->dateLong, $localDefault ?? '');
                        if ($tmpOutput === false) {
                            $tmpOutput = DateTime::createFromFormat($this->dateShort, $localDefault ?? '');
                            if ($tmpOutput) {
                                $tmpOutput->settime(0, 0);
                            } else {
                                $tmpOutput = null;
                            }
                        }
                        return $tmpOutput;
                    }
                    $valueDate->settime(0, 0); // datetime without time
                }
                return $valueDate;
            default:
                return $value;
        }
    }

    /**
     * It adds an error
     *
     * @param string|null $msg     See condition() for more information2
     * @param string|null $msg2    Second message.
     * @param string      $fieldId id of the field
     * @param mixed       $value   value supplied
     * @param mixed       $vcomp   value to compare.
     * @param string      $level   (error,warning,info,success) error level
     * @param mixed       $key
     * @throws JsonException
     */
    private function addMessageInternal(?string $msg, ?string $msg2, string $fieldId, $value
        ,                                       $vcomp, string $level = 'error', $key = null): void
    {
        $txt = ($msg) ?: $msg2;
        if (is_array($vcomp)) {
            $first = $vcomp[0] ?? null;
            $second = $vcomp[1] ?? null;
            $vcomp = json_encode($vcomp, JSON_THROW_ON_ERROR); // is not array anymore
        } else {
            $first = $vcomp;
            $second = $vcomp;
        }
        if (is_array($this->originalValue)) {
            $txt = str_replace(['%field', '%realfield', '%value', '%comp', '%first', '%second', '%key'], [
                $this->friendId ?? $fieldId,
                $fieldId,
                is_array($value) ? "[]" : $value,
                $vcomp,
                $first,
                $second,
                $key
            ], $txt);
            //$this->originalValue=$value;
        } else {
            $txt = str_replace(['%field', '%realfield', '%value', '%comp', '%first', '%second', '%key'], [
                $this->friendId ?? $fieldId,
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

    /**
     * It serializes a message.
     *
     * @param mixed $value
     *
     * @return false|string
     * @throws JsonException
     */
    private function addMessageSer($value)
    {
        if ($value instanceof DateTime) {
            return $value->format('c');
        }
        if (is_object($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }
        return $value;
    }

    /**
     * @param mixed $value
     * @param       $fieldId
     * @param null  $key
     * @throws JsonException
     */
    private function runConditions($value, $fieldId, $key = null): void
    {
        if (($value === null && $this->isNullValid) || ($value === '' && $this->isEmptyValid)) {
            // bypass (null or empty value and isvalidnull or isvalidempty, then it is ok and nothing to evaluate).
            return;
        }
        $genMsg = '';
        if ($key === null || $this->isColumn) {
            foreach ($this->conditions as $cond) {
                $fail = false;
                if (strpos($cond->type, "fn.") === 0) {
                    // if it starts with fn. then it's a function condition
                    $this->runFnCondition($value, $cond, $fail, $genMsg);
                } else {
                    //
                    //(function(){return ['integer','unixtime','boolean','decimal','float','varchar','string','date'
                    //,'datetime','datestring','datetimestring'][$i];})();
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
        } elseif (isset($this->conditions[$key])) {
            $fail = false;
            if (!is_array($this->conditions[$key])) {
                $this->conditions[$key] = [$this->conditions[$key]];
            }
            foreach ($this->conditions[$key] as $cond) {
                if (strpos($cond->type, "fn.") === 0) {
                    // if it starts with fn. then it's a function condition
                    $this->runFnCondition($value, $cond, $fail, $genMsg);
                } else {
                    $tf = is_array($this->typeFams) ? $this->typeFams[$key] : $this->typeFams;
                    switch ($tf) {
                        case 'integer':
                        case 'unixtime':
                        case 'decimal':
                        case 'float':
                        case 0:
                            $this->runNumericCondition($value, $cond, $fail, $genMsg);
                            break;
                        case 'varchar':
                        case 'string': // string
                        case 1:
                            $this->runStringCondition($value, $cond, $fail, $genMsg);
                            break;
                        case 'date':
                        case 'datetime':// date
                        case 2:
                            $this->runDateCondition($value, $cond, $fail, $genMsg);
                            break;
                        case 'boolean': // bool
                        case 3:
                            $this->runBoolCondition($value, $cond, $fail, $genMsg);
                            break;
                        case 'file': // file
                        case 4:
                            $this->runFileCondition($value, $cond, $fail, $genMsg);
                            break;
                        case 'datestring':
                        case 'datetimestring':// datestring
                        case 5:
                            // branch 5
                            $this->runDateCondition($value, $cond, $fail, $genMsg);
                            break;
                    } // switch
                }
                if ($fail) {
                    $this->addMessageInternal($cond->msg, $genMsg, $fieldId, $value, $cond->value, $cond->level, $key);
                    if (!$this->abortOnError) {
                        break; // no continue anymore.
                    }
                }
            }
        }
    }

    /**
     * @param mixed          $r      value
     * @param ValidationItem $cond   The condition to evaluate
     * @param boolean        $fail   True if the operation fails
     * @param string         $genMsg If it fails, it returns a message.
     */
    private function runFnCondition($r, ValidationItem $cond, bool &$fail, string &$genMsg): void
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
                        throw new RuntimeException("variable $arr[2] not defined as global");
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
                            throw new RuntimeException("unable to call injection");
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

    /**
     * @param mixed          $r      value
     * @param ValidationItem $cond   The condition to evaluate
     * @param boolean        $fail   True if the operation fails
     * @param string         $genMsg If it fails, it returns a message.
     */
    private function runNumericCondition($r, ValidationItem $cond, bool &$fail, string &$genMsg): void
    {
        if ($this->runSharedCondition($r, $cond, $fail, $genMsg, 0)) {
            return;
        }
        switch ($cond->type) {
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
            case 'gte':
                if ($r < $cond->value) {
                    $fail = true;
                    $genMsg = '%field is less than %comp';
                }
                break;
            case 'between':
                if (!isset($cond->value[0], $cond->value[1])) {
                    $fail = true;
                    $genMsg = '%field (between) lacks conditions';
                } else if ($r < $cond->value[0] || $r > $cond->value[1]) {
                    $fail = true;
                    $genMsg = '%field is not between ' . $cond->value[0] . " and " . $cond->value[1];
                }
                break;
        }
    }

    /**
     * @param mixed          $r      value
     * @param ValidationItem $cond   The condition to evaluate
     * @param boolean        $fail   True if the operation fails
     * @param string         $genMsg If it fails, it returns a message.
     * @param int            $type   =[0,1,2,3,4][$i]  0=number, 1=string,2=date,3=bool,4=file
     * @return bool true if one condition matches, otherwise false.
     * @noinspection TypeUnsafeArraySearchInspection
     */
    private function runSharedCondition($r, ValidationItem $cond, bool &$fail, string &$genMsg, int $type): bool
    {
        switch ($cond->type) {
            case 'exist':
                if ($this->isMissing && $type !== 4) { // file uses a different method
                    $fail = true;
                    $genMsg = '%field does not exist';
                }
                break;
            case 'missing':
            case 'notexist':
                if (!$this->isMissing && $type !== 4) { // file uses a different method
                    $fail = true;
                    $genMsg = '%field exists';
                }
                break;
            case 'req':
            case 'required':
                if (!$r) {
                    $fail = true;
                    $genMsg = '%field is required';
                }
                break;
            case 'eq':
            case '==':
                if (is_array($cond->value)) {
                    /** @noinspection TypeUnsafeArraySearchInspection */
                    if (!in_array($r, $cond->value)) {
                        $fail = true;
                        $genMsg = '%field is not equals than %comp';
                        return true;
                    }
                } /** @noinspection TypeUnsafeComparisonInspection */ elseif ($r != $cond->value) {
                    $fail = true;
                    $genMsg = '%field is not equals than %comp';
                    return true;
                }
                break;
            case 'ne':
            case '!=':
            case '<>':
                if (is_array($cond->value)) {
                    if (in_array($r, $cond->value)) {
                        $fail = true;
                        $genMsg = '%field is in %comp';
                        return true;
                    }
                } /** @noinspection TypeUnsafeComparisonInspection */ elseif ($r == $cond->value) {
                    $fail = true;
                    $genMsg = '%field is equals than %comp';
                    return true;
                }
                break;
            case 'null':
                if ($r !== null) {
                    $fail = true;
                    $genMsg = '%field is not null';
                }
                break;
            case 'empty':
                if (!empty($r)) {
                    $fail = true;
                    $genMsg = '%field is not empty';
                }
                break;
            case 'notempty':
                if (empty($r)) {
                    $fail = true;
                    $genMsg = '%field is empty';
                }
                break;
            case 'notnull':
                if ($r === null) {
                    $fail = true;
                    $genMsg = '%field is null';
                }
                break;
        }
        return false;
    }

    /**
     * @param mixed          $r      value
     * @param ValidationItem $cond   The condition to evaluate
     * @param boolean        $fail   True if the operation fails
     * @param string         $genMsg If it fails, it returns a message.
     */
    private function runStringCondition($r, ValidationItem $cond, bool &$fail, string &$genMsg): void
    {
        if ($this->runSharedCondition($r, $cond, $fail, $genMsg, 1)) {
            return;
        }
        switch ($cond->type) {
            case 'contain':
                if (strpos((string)$r, $cond->value) === false) {
                    $fail = true;
                    $genMsg = '%field contains %comp';
                }
                break;
            case 'notcontain':
                if (strpos((string)$r, $cond->value) !== false) {
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
            case 'alphanumunder':
                if (!ctype_alnum(str_replace('_', '', (string)$r))) {
                    $fail = true;
                    $genMsg = '%field is not alphanumeric with underscore';
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
                /** @noinspection NotOptimalRegularExpressionsInspection */
                if (!preg_match('^[\p{L}| |.|\/|*|+|.|,|=|_|"|\']+$', (string)$r)) {
                    $fail = true;
                    $genMsg = '%field has characters not allowed';
                }
                break;
            case 'regexp':
                if (!preg_match($cond->value, (string)$r)) {
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
            case 'minlen':
                if (strlen((string)$r) < $cond->value) {
                    $fail = true;
                    $genMsg = '%field size is less than %comp';
                }
                break;
            case 'maxlen':
                if (strlen((string)$r) > $cond->value) {
                    $fail = true;
                    $genMsg = '%field size is great than %comp';
                }
                break;
            case 'betweenlen':
                $rl = strlen((string)$r);
                if ($rl < $cond->value[0] || $rl > $cond->value[1]) {
                    $fail = true;
                    $genMsg = '%field size is not between %first and %second ';
                }
                break;
        }
    }

    /**
     * @param mixed          $r      timestamp of the date/time
     * @param ValidationItem $cond   Where cond->value equals to the timestamp of the date/time<br>
     *                               Where cond->value equals to the timestamp of the date/time
     * @param boolean        $fail   True if the operation fails
     * @param string         $genMsg If it fails, it returns a message.
     */
    private function runDateCondition($r, ValidationItem $cond, bool &$fail, string &$genMsg): void
    {
        if ($this->runSharedCondition($r, $cond, $fail, $genMsg, 2)) {
            return;
        }
        switch ($cond->type) {
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
            case 'gte':
                if ($r < $cond->value) {
                    $fail = true;
                    $genMsg = '%field is less than %comp';
                }
                break;
            case 'between':
                if (!isset($cond->value[0], $cond->value[1])) {
                    $fail = true;
                    $genMsg = '%field (between) lacks conditions';
                } elseif ($r < $cond->value[0] || $r > $cond->value[1]) {
                    $fail = true;
                    $genMsg = '%field is not between ' . $cond->value[0] . " and " . $cond->value[1];
                }
                break;
        }
    }

    /**
     * @param mixed          $r      value.
     * @param ValidationItem $cond   The condition to evaluate.
     * @param boolean        $fail   True if the operation fails
     * @param string         $genMsg If it fails, it returns a message.
     */
    private function runBoolCondition($r, ValidationItem $cond, bool &$fail, string &$genMsg): void
    {
        if ($this->runSharedCondition($r, $cond, $fail, $genMsg, 3)) {
            return;
        }
        switch ($cond->type) {
            case 'true':
                if ($r === true) {
                    $fail = true;
                    $genMsg = '%field is not true';
                }
                break;
            case 'false':
                if ($r === false) {
                    $fail = true;
                    $genMsg = '%field is not false';
                }
                break;
        }
    }

    /**
     * @param mixed          $value  =['req','minsize','maxsize','image','doc','compression','architecture','ext'][$i]
     * @param ValidationItem $cond   The condition to evaluate
     * @param boolean        $fail
     * @param string         $genMsg Default error message, it could be replaced
     *                               if there is a message for this condition
     * @noinspection TypeUnsafeArraySearchInspection
     */
    private function runFileCondition($value, ValidationItem $cond, bool &$fail, string &$genMsg): void
    {
        $fileName = $value[0] ?? null;
        $fileNameTmp = $value[1] ?? null;
        if ($this->runSharedCondition($value, $cond, $fail, $genMsg, 4)) {
            return;
        }
        switch ($cond->type) {
            case 'exist':
                $fileExist = @file_exists($fileNameTmp);
                if (!$fileExist) {
                    $genMsg = '%field does not exist';
                    $fail = true;
                }
                break;
            case 'notexist':
                $fileExist = !@file_exists($fileNameTmp);
                if (!$fileExist) {
                    $genMsg = '%field does exist';
                    $fail = true;
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
            case 'mime':
                $mime = $this->getFileMime($fileNameTmp);
                if (!is_array($cond->value)) {
                    $cond->value = [$cond->value];
                }
                if (!in_array($mime, $cond->value)) {
                    $fail = true;
                    $genMsg = '%field incorrect media type';
                }
                break;
            case 'mimetype':
                $mime = $this->getFileMime($fileNameTmp, true);
                if (!is_array($cond->value)) {
                    $cond->value = [$cond->value];
                }
                if (!in_array($mime, $cond->value)) {
                    $fail = true;
                    $genMsg = '%field incorrect media type';
                }
                break;
            case 'image':
                $verifyimg = @getimagesize($fileNameTmp);
                if (!$verifyimg) {
                    $fail = true;
                    $genMsg = '%field is not a right image';
                } else {
                    $ext = $this->getFileExtension($fileName);
                    if (!in_array($ext, ['jpg', 'png', 'gif', 'jpeg', 'bmp'])) {
                        $fail = true;
                        $genMsg = '%field is not allowed';
                    }
                }
                break;
            case 'doc':
                $ext = $this->getFileExtension($fileName);
                if (!in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'xlsm', 'ppt', 'pptx', 'pdf', 'txt', 'rtf'])) {
                    $fail = true;
                    $genMsg = '%field is not allowed';
                }
                break;
            case 'compression':
                $ext = $this->getFileExtension($fileName);
                if (!in_array($ext, ['rar', 'zip', 'gzip', 'gz', '7z'])) {
                    $fail = true;
                    $genMsg = '%field is not allowed';
                }
                break;
            case 'architecture':
                $ext = $this->getFileExtension($fileName);
                if (!in_array($ext, ['dwg', 'rvt', '3ds', 'fbx', 'dxf', 'max', 'obj'])) {
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
     * It returns the mime-type of full filename.  If not found or error, it returns false<br>
     * Example:<br>
     * $this->getFileMime("/folder/filename.txt"); // it could return "text/plain"<br>
     * $this->getFileMime("/folder/filename.txt",true); // it could return "text"<br>
     *
     * @param string $fullFilename Full filename (with path) of the file to analyze.
     * @param bool   $onlyType     if true then it only returns the first part of the mime.
     *                             Example "text" instead of "text/plain"
     *
     * @return bool|mixed|string
     */
    public function getFileMime(string $fullFilename, bool $onlyType = false)
    {
        if (function_exists("finfo_file")) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
            $mime = @finfo_file($finfo, $fullFilename);
            @finfo_close($finfo);
            if ($onlyType) {
                $mimeArr = @explode('/', $mime);
                $mime = $mimeArr[0] ?? '';
            }
            return $mime;
        }
        if (function_exists("mime_content_type")) {
            $mime = @mime_content_type($fullFilename);
            if ($onlyType) {
                $mimeArr = @explode('/', $mime);
                $mime = $mimeArr[0] ?? '';
            }
            return $mime;
        }
        return false;
    }

    /**
     * Get the extension without dot of a file always in lowercase
     *
     * @param string $fullPath
     * @param bool   $asMime if true then it returns
     *
     * @return string mixed
     */
    public function getFileExtension(?string $fullPath, bool $asMime = false): string
    {
        if (empty($fullPath)) {
            return '';
        }
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (!$asMime) {
            return $ext;
        }
        $mimes = ['aac' => 'audio/aac',
            'abw' => 'application/x-abiword',
            'avi' => 'video/x-msvideo',
            'bmp' => 'image/bmp',
            'bz' => 'application/x-bzip',
            'bz2' => 'application/x-bzip2',
            'css' => 'text/css',
            'csv' => 'text/csv',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dwg' => 'image/vnd.dwg',
            'eot' => 'application/vnd.ms-fontobject',
            'epub' => 'application/epub+zip',
            'gif' => 'image/gif',
            'html' => 'text/html',
            'htm' => 'text/html',
            'ico' => 'image/x-icon',
            'ics' => 'text/calendar',
            'jar' => 'application/java-archive',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'midi' => 'audio/midi audio/x-midi',
            'mid' => 'audio/midi audio/x-midi',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpkg' => 'application/vnd.apple.installer+xml',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'oga' => 'audio/ogg',
            'ogg' => 'audio/ogg',
            'ogv' => 'video/ogg',
            'ogx' => 'application/ogg',
            'otf' => 'font/otf',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'rar' => 'application/x-rar-compressed',
            'rtf' => 'application/rtf',
            'sh' => 'application/x-sh',
            'svg' => 'image/svg+xml',
            'swf' => 'application/x-shockwave-flash',
            'tar' => 'application/x-tar',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'ts' => 'application/typescript',
            'ttf' => 'font/ttf',
            'txt' => 'text/plain',
            'vsd' => 'application/vnd.visio',
            'wav' => 'audio/wav',
            'weba' => 'audio/webm',
            'webm' => 'video/webm',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'xhtml' => 'application/xhtml+xml',
            'xls' => 'application/vnd.ms-excel',
            'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xml' => 'application/xml',
            'xul' => 'application/vnd.mozilla.xul+xml',
            'zip' => 'application/zip',
            '3gp' => 'video/3gpp',
            '3g2' => 'video/3gpp2',
            '7z' => 'application/x-7z-compressed',
            'default' => 'application/octet-stream'
        ];
        return array_key_exists($ext, $mimes)
            ? $mimes[$ext]
            : $mimes['default'];
    }

    /**
     * Trim the end result. It is an after-validation operation. By default, the result is not trimmed.<br>
     * It is equals than to use $this->conversion('trim')
     *
     *
     * @param null|string $type      =[null,'ltrim','rtrim','trim'][$i] (null = no trim)
     * @param string      $trimChars Characters to trim " \t\n\r\0\x0B"
     * @return ValidationOne
     * @see ValidationOne::conversion
     */
    public function trim(?string $type = 'trim', string $trimChars = " \t\n\r\0\x0B"): ValidationOne
    {
        $this->conversion[] = [$type, $trimChars, null];
        return $this;
    }

    /**
     * If set then it always "trims" the values.
     *
     * @param bool   $always    [false] If true then it trims all the results.
     * @param string $trimChars Characters to trim
     */
    public function alwaysTrim(bool $always = true, string $trimChars = " \t\n\r\0\x0B"): void
    {
        $this->alwaysTrim = $always;
        $this->alwaysTrimChars = $trimChars;
    }

    /**
     * It adds a conversion of the result. It is an after-validation operation.<br>
     * <b>Note:</b> Default values are never converted.
     * <b>example</b><br>
     * ```php
     * $this->type('string')->conversion('alphanumericunderscore')->post('FIELDREQ2');
     * ```
     *
     * @param string     $type =['upper','lower','ucfirst','ucwords','replace','sanitizer'
     *                         ,'rtrim','ltrim','trim','htmlencode','htmldecode','alphanumeric'
     *                         ,'alphanumericminus','alphanumericunderscore','regexp'][$i] <br>
     *                         <ul>
     *                         <li><b>upper</b>: Converts in uppercase</li>
     *                         <li><b>lower</b>: Converts in lowercase</li>
     *                         <li><b>ucfirst</b>: Converts in first case</li>
     *                         <li><b>ucwords</b>: Converts every first letter in a word in uppercase</li>
     *                         <li><b>replace</b>: Replaces a value (or values) by other(s)</li>
     *                         <li><b>sanitizer</b>: It uses the default FILTER_DEFAULT but you can specify other</li>
     *                         <li><b>rtrim</b>: Trim to the right, you can change the value to trim</li>
     *                         <li><b>ltrim</b>: Trim to the left, you can change the value to trim</li>
     *                         <li><b>trim</b>: Trim to the left, you can change the value to trim</li>
     *                         <li><b>htmlencode</b>: HTML encode</li>
     *                         <li><b>htmldecode</b>: HTML decode</li>
     *                         <li><b>alphanumeric</b>: Removes all non-alphanumeric characters</li>
     *                         <li><b>alphanumericminus</b>: Removes all non-alphanumeric characters plus symbol
     *                         minus</li>
     *                         <li><b>alphanumericunderscore</b>: Removes all non-alphanumeric characters plus symbol
     *                         underscore</li>
     *                         <li><b>regexp</b>: Use a regular expression to clean a text</li>
     *                         </ul>
     * @param mixed|null $arg1 It is used if the conversion requires an argument.
     * @param mixed|null $arg2 It is used if the conversion requires a second argument.
     * @return $this
     */
    public function conversion(string $type, $arg1 = null, $arg2 = null): ValidationOne
    {
        $this->conversion[] = [$type, $arg1, $arg2];
        return $this;
    }


    /**
     * If the input is an object DateTime and the type is datestring or datetimestring, then it is converted
     * into a string<br> It also "trims" the result.
     *
     * @param mixed $input
     *
     * @return mixed
     */
    private function endConversion($input)
    {
        // end conversion, we convert the input or default value.
        if ($input !== null) {
            if ($this->alwaysTrim) {
                $this->trim('trim', $this->alwaysTrimChars);
            }
            $tmp = null;
            if (!is_object($input) && count($this->conversion) > 0) {
                foreach ($this->conversion as $v) {
                    switch ($v[0]) {
                        case 'ltrim':
                            $tmp = ltrim($input, $v[1] ?? " \t\n\r\0\x0B");
                            break;
                        case 'rtrim':
                            $tmp = rtrim($input, $v[1] ?? " \t\n\r\0\x0B");
                            break;
                        case 'trim':
                            $tmp = trim($input, $v[1] ?? " \t\n\r\0\x0B");
                            break;
                        case 'upper':
                            $tmp = strtoupper($input);
                            break;
                        case 'lower':
                            $tmp = strtolower($input);
                            break;
                        case 'ucfirst':
                            $tmp = ucfirst($input);
                            break;
                        case 'ucwords':
                            $tmp = ucwords($input, $v[1] ?? " \t\r\n\f\v");
                            break;
                        case 'replace':
                            $tmp = str_replace($v[1], $v[2], $input);
                            break;
                        case 'sanitizer':
                            $tmp = $v[2] === null
                                ? filter_var($input, $v[1] ?? FILTER_DEFAULT)
                                : filter_var($input, $v[1] ?? FILTER_DEFAULT, $v[2]);
                            break;
                        case 'alphanumeric':
                            $tmp = preg_replace('/\W/', '', $input);
                            break;
                        case 'alphanumericminus':
                            $tmp = preg_replace('/[^\w-]/', '', $input);
                            break;
                        case 'alphanumericunderscore':
                            $tmp = preg_replace('/[^\w_]/', '', $input);
                            break;
                        case 'regexp':
                            $tmp = preg_replace($v[1], $v[2] ?? '', $input);
                            break;
                        case 'htmlencode':
                            $tmp = htmlentities($input, $v[1] ?? ENT_QUOTES | ENT_SUBSTITUTE, $v[2]);
                            break;
                        case 'htmldecode':
                            $tmp = html_entity_decode($input, $v[1] ?? ENT_QUOTES | ENT_SUBSTITUTE, $v[2]);
                            break;
                        default:
                            $tmp = $input;
                    }
                }
            } else {
                $tmp = $input;
            }
            switch ($this->type) {
                case 'datestring':
                    $output = ($input instanceof DateTime) ? $input->format($this->dateOutputString) : $tmp;
                    break;
                case 'datetimestring':
                    $output = ($input instanceof DateTime) ? $input->format($this->dateLongOutputString) : $tmp;
                    break;
                default:
                    $output = $tmp;
            }
        } else {
            $output = null;
        }
        return $output;
    }

    /**
     * You could add a message (including errors,warning...) and store in an $id
     * It is a wrapper of $this->messageList->addItem
     *
     * @param string $idLocker Identified of the locker (where the message will be stored
     * @param string $msg      message to show. Example: 'the value is incorrect'
     * @param string $level    =['error','warning','info','success'][$i]
     * @return ValidationOne
     */
    public function addMessage(string $idLocker, string $msg, string $level = 'error'): ValidationOne
    {
        $this->messageList->addItem($idLocker, $msg, $level);
        return $this;
    }

    /**
     * It's a callback to the form if it's defined.<br>
     * It's used to inform to the form that the validation chain is ready to send validation to the visual layer.
     *
     * @param $fieldId
     */
    private function callFormBack($fieldId): void
    {
        if ($this->formOne !== null) {
            $this->formOne->callBack($this, $fieldId);
        }
    }

    /**
     * @param string      $field
     * @param string|null $msg
     *
     * @return array|bool|DateTime|float|int|mixed|null
     * @throws JsonException
     */
    public function post(string $field, ?string $msg = null)
    {
        return $this->endChainFetch(0, $field, $msg);
    }

    /**
     * @param string      $field
     * @param string|null $msg
     *
     * @return array|bool|DateTime|float|int|mixed|null
     * @throws JsonException
     */
    public function request(string $field, ?string $msg = null)
    {
        return $this->endChainFetch(99, $field, $msg); // 99 request
    }

    /**
     * It fetches a value.
     *
     * @param int         $inputType INPUT_POST(0)|INPUT_GET(1)|INPUT_REQUEST(99)
     * @param string      $field
     * @param null|string $msg
     *
     * @return mixed
     * @throws JsonException
     */
    public function fetch(int $inputType, string $field, ?string $msg = null)
    {
        return $this->endChainFetch($inputType, $field, $msg);
    }

    /**
     * Returns null if the value is not present, false if the value is incorrect and the value
     * if it's correct
     *
     * @param string      $fieldId
     * @param bool        $isArray
     * @param string|null $msg
     *
     * @return mixed
     * @throws JsonException
     * @internal param string $type
     * @internal param $folder
     */
    public function getFile(string $fieldId, bool $isArray = false, ?string $msg = null)
    {
        $this->countError = $this->messageList->errorCount;
        $this->input()->default = $this->default;
        $this->input()->originalValue = $this->originalValue;
        $this->input()->ifFailThenOrigin = $this->ifFailThenOrigin;
        $this->input()->initial = $this->initialValue;
        foreach ($this->conditions as $c) {
            if ($c->type === "exist") {
                $this->exist = true;
                break;
            }
        }
        $r = $this->input()->exist($this->exist)->friendId($this->friendId)->getFile($fieldId, $isArray, $msg,
            $this->isMissing);
        //->getField($fieldId,$inputType,$msg,$this->isMissing);
        return $this->afterFetch($r, $fieldId, $msg);
        //return $this->input()->getFile($field,$array);
    }

    /**
     * It sets a default value. It could be used as follows:<br>
     * <ol>
     * <li> If the value is not set, and it's not required (by default, it's not required), then it sets this value.
     * Otherwise, null</li>
     * <li> If the value is not set, and it's required, then it returns an error, and it sets this value
     * , otherwise null</li>
     * <li> If the value is not set, and it's an array, then it sets a single value, or it sets a
     * value per key of array.</li>
     * <li>d) if value is null, then the default value is the same input value.</li>
     * </ol>
     * <b>Note:</b> This value must be in the same format as the (expected) output.<br>
     * <b>Note:</b> Default value is not converted but returned directly.
     *
     * @param mixed|array $value
     * @param bool|null   $ifFailThenDefault If true then, if the validations fails, then it returns this default value,
     *                                       otherwise, it returns a null.
     *
     * @return ValidationOne
     * @see ValidationOne::ifFailThenDefault
     */
    public function def($value = null, ?bool $ifFailThenDefault = null): ValidationOne
    {
        $this->default = $value;
        if ($ifFailThenDefault !== null) {
            $this->ifFailThenDefault = $ifFailThenDefault;
        }
        return $this;
    }

    /**
     * If the value is null, then it is not evaluated, and it doesn't generate any message<br>
     * This method is used where a value null is a valid condition.<br>
     * **Example:**<br>
     * ```php
     * $this->isNullValid()->condition("eq","hello")->get("idfield"); // hello or null are valid conditions
     * ```
     *
     * @param bool $isValid if true then, if the value is null then it is not evaluated.
     *
     * @return ValidationOne
     */
    public function isNullValid(bool $isValid = true): ValidationOne
    {
        $this->isNullValid = $isValid;
        return $this;
    }

    /**
     * If the value is null or empty '', then it is not evaluated, and it doesn't generate any message<br>
     * This method is used where a value null/empty is a valid condition.<br>
     * **Example:**<br>
     * ```php
     * $this->isNullorEmptyValid()->condition("eq","hello")->get("idfield"); // hello or null/'' are valid conditions
     * ```
     *
     * @param bool $isValid if true then, if the value is null/empty then it is not evaluated.
     *
     * @return ValidationOne
     */
    public function isNullOrEmptyValid(bool $isValid = true): ValidationOne
    {
        $this->isNullValid = $isValid;
        $this->isEmptyValid = $isValid;
        return $this;
    }

    /**
     * If the value is empty, then it is not evaluated, and it doesn't generate any message<br>
     * This method is used where a value empty is a valid condition.<br>
     * **Example:**<br>
     * ```php
     * $this->isNullValid()->condition("eq","hello")->get("idfield"); // hello or '' are valid conditions
     * ```
     *
     * @param bool $isEmpty if true then, if the value is null then it is not evaluated.
     *
     * @return ValidationOne
     */
    public function isEmptyValid(bool $isEmpty = true): ValidationOne
    {
        $this->isEmptyValid = $isEmpty;
        return $this;
    }

    /**
     * If the value is missing, then it is not evaluated, and it doesn't generate any message<br>
     * This method is used where a value missing is a valid condition.<br>
     * **Example:**<br>
     * ```php
     * $this->isMissingValid()->condition("eq","hello")->get("idfield"); // hello or not defined are valid conditions
     * ```
     *
     * @param bool $isMissing if true then, if the value is null then it is not evaluated.
     *
     * @return ValidationOne
     */
    public function isMissingValid(bool $isMissing = true): ValidationOne
    {
        $this->isMissingValid = $isMissing;
        return $this;
    }


    //</editor-fold>

    /**
     * (Optional). It sets an initial value.<br>
     * If the value is missing (that it's different to empty or null), then it uses this value.<br>
     * It does not work with set()
     *
     * @param mixed|null $initial
     *
     * @return $this
     */
    public function initial($initial = null): ValidationOne
    {
        $this->initialValue = $initial;
        return $this;
    }

    /**
     * It configures all the next chains with those default values.<br>
     * For example, we could force to be required always.
     *
     * @param bool $ifFailThenDefault
     * @param bool $ifRequired The field must be fetched, otherwise it generates an error
     */
    public function configChain(bool $ifFailThenDefault = false, bool $ifRequired = false): void
    {
        $this->defaultIfFail = $ifFailThenDefault;
        $this->defaultRequired = $ifRequired;
    }

    //<editor-fold desc="fetch and end of chain commands">

    /**
     * Sets if the conditions must be evaluated on Error or not. By default, it's not aborted.
     *
     * @param bool $abort if true, then it stops at the first error.
     *
     * @return ValidationOne $this
     */
    public function abortOnError(bool $abort = false): ValidationOne
    {
        $this->abortOnError = $abort;
        return $this;
    }

    /**
     * Sets the fetch for an array. It's not required for set()<br>
     * If $flat is true then the errors are returned as a flat array (idx instead of idx[0],idx[1])
     *
     * @param bool $flat
     * @param bool $invertIndexRow If true, then it invert the order of the array<br>
     *                             <b>(false, no conversion)</b>: ['col1'=>['cocacola','fanta'],'col2'=>[1,2]]<br>
     *                             <b>(true)</b>: [['col1'=>'cocacola','col2'=>1],['col1'=>'fanta','col2'=>2]]<br>
     *
     * @return ValidationOne $this
     */
    public function isArray(bool $flat = false,bool $invertIndexRow = false): ValidationOne
    {
        $this->isArray = true;
        $this->isArrayFlat = $flat;
        $this->invertIndexRow = $invertIndexRow;
        return $this;
    }

    public function getHasMessage(): bool
    {
        return $this->hasMessage;
    }

    public function isColumn($isColumn): ValidationOne
    {
        $this->isColumn = $isColumn;
        return $this;
    }

    /**
     * @param bool $ifFailDefault
     *
     * @return ValidationOne ValidationOne
     */
    public function ifFailThenDefault(bool $ifFailDefault = true): ValidationOne
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
    public function ifFailThenOrigin(bool $ifFailThenOrigin = true): ValidationOne
    {
        $this->ifFailThenDefault = true;
        $this->ifFailThenOrigin = $ifFailThenOrigin;
        return $this;
    }
    //</editor-fold>
    //<editor-fold desc="conditions">
    /**
     * If the value is missing (null or empty) then it sets a value. If it does not set then it uses
     * the default natural value.<br>
     * **Example:**<br>
     * ```php
     * $this->ifMissingThenSet("some value");
     * ```
     *
     * @param mixed $value The value to set if the value is missing.
     *
     * @return ValidationOne
     */
    public function ifMissingThenSet($value = null): ValidationOne
    {
        if ($value === null) {
            $this->missingSet = $this->defNatural();
            return $this;
        }
        $this->missingSet = $value;
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
     * datestring = (current date) '1970-01-01T00:00:00Z' (null if negative=true)<br>
     * <b>Note:</b> Default value is not converted but returned directly.
     *
     * @param bool $negative if true then it returns the negative default value.
     *
     * @return ValidationOne $this
     */
    public function defNatural(bool $negative = false): ValidationOne
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
                $this->default = !$negative;
                break;
            case 4:
                $this->default = (!$negative) ? '' : null; // file
                break;
            case 5:
                $defaultDate = new DateTime();
                if ($this->type === 'datetimestring') {
                    $defaultDate = $defaultDate->format($this->dateLong);
                } else {
                    $defaultDate->setTime(0, 0);
                    $defaultDate = $defaultDate->format($this->dateShort);
                }
                $this->default = (!$negative) ? $defaultDate : null;
                break;
        }
        return $this;
    }

    public function successMessage($id, $msg, $level = "success"): ValidationOne
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
    public function override(bool $override = true): ValidationOne
    {
        $this->override = $override;
        return $this;
    }

    /**
     * If true, then the value must exist, otherwise it will raise an error<br>
     * However, even in the case of error, it still returns the default value.<br>
     *
     * @param bool   $exist
     * @param string $msg See condition() for more information
     * @return ValidationOne
     * @see ValidationOne::def()
     * @see ValidationOne::condition()
     */
    public function exist(bool $exist = true, string $msg = ''): ValidationOne
    {
        $this->exist = $exist;
        if ($this->exist) {
            $this->condition('exist', $msg);
        }
        return $this;
    }

    /**
     * It adds a condition to the variable. If the conditions doesn't meet, then it stores a message and raise an error
     * level. The conditions depend on the type of the variable. Also, some conditions requires one or two values.
     * **Example:**
     * ```php
     * $field2=getVal()->type('string')
     *      ->condition('minlen','',3)
     *      ->condition('maxlen','',10)
     *      ->post('field2');
     * $field2=getVal()->type('int')
     *      ->condition('between','',[0,100])
     *      ->post('percentage');
     * ```
     *
     * @param string      $condition      =['alpha','alphanum','alphanumunder','between','betweenlen','contain','doc','domain','email'
     *                                    ,'eq','exist','ext'
     *                                    ,'false','notexist','missing','gt','gte','image','doc','compression','architecture',
     *                                    ,'lt','lte','maxlen','maxsize','minlen','minsize','ne'
     *                                    ,'notcontain','notnull','null','empty','notempty','regexp','req','required','text','true'
     *                                    ,'url','fn.*'][$i]
     *                                    <ul>
     *                                    <li><b>alpha</b>: An alphabetic value</li>
     *                                    <li><b>alphanum</b>: An alphanumeric value</li>
     *                                    <li><b>alphanumunder</b>: An alphanumeric value including underscore</li>
     *                                    <li><b>between</b>: A value between two values</li>
     *                                    <li><b>betweenlen</b>: The leght of a string between two values</li>
     *                                    <li><b>contain</b>: The text must contain a value</li>
     *                                    <li><b>doc</b>: The extension is a document file</li>
     *                                    <li><b>domain</b>: The name of a domain</li>
     *                                    <li><b>email</b>: An email</li>
     *                                    <li><b>eq</b>: equal</li>
     *                                    <li><b>exist</b>: The value exists (in a list of values)</li>
     *                                    <li><b>ext</b>: The extension of the file must be equals to a value</li>
     *                                    <li><b>false</b>: The value is false</li>
     *                                    <li><b>notexist</b>: The value does not exist (in a list of values)</li>
     *                                    <li><b>missing</b>: The value is missing</li>
     *                                    <li><b>gt</b>: Great than</li>
     *                                    <li><b>gte</b>: Great than or equals</li>
     *                                    <li><b>image</b>: The extension is an image</li>
     *                                    <li><b>compression</b>: The extension is a compression file</li>
     *                                    <li><b>architecture</b>: The extension is an architecture file</li>
     *                                    <li><b>lt</b>: Less than</li>
     *                                    <li><b>lte</b>: Less than or equals</li>
     *                                    <li><b>maxlen</b>: The maximum lenght</li>
     *                                    <li><b>maxsize</b>: The maximum size (of a file)</li>
     *                                    <li><b>minlen</b>: The minimum lehght</li>
     *                                    <li><b>minsize</b>: The minimum size (of a file)</li>
     *                                    <li><b>ne</b>: Not equals</li>
     *                                    <li><b>notcontain</b>: Not contains a text</li>
     *                                    <li><b>notnull</b>: Is not null</li>
     *                                    <li><b>null</b>: Is null</li>
     *                                    <li><b>empty</b>: Is empty</li>
     *                                    <li><b>notempty</b>: Is not empty</li>
     *                                    <li><b>regexp</b>: Evaluates a regular expression</li>
     *                                    <li><b>req</b>: Is required</li>
     *                                    <li><b>required</b>: Is required (alias)</li>
     *                                    <li><b>text</b>: The value is a words, number, accents, spaces, and other
     *                                    characters</li>
     *                                    <li><b>true</b>: Is true</li>
     *                                    <li><b>url</b>: Is an url</li>
     *                                    <li><b>fn.*</b>: Evaluate using a function (* is the name of the
     *                                    function)</li>
     *                                    </ul>
     *                                    <b>Usage by type of value:</b><br>
     *                                    <br><b>number</b>:req,eq,ne,gt,lt,gte,lte,between,null,notnull,empty,notempty<br>
     *                                    <b>string</b>:req,eq,ne,minlen,maxlen,betweenlen,null,notnull,empty,notempty
     *                                    ,contain,notcontain
     *                                    ,alpha,alphanum,text,regexp,email,url,domain<br>
     *                                    <b>date</b>:req,eq,ne,gt,lt,gte,lte,between<br>
     *                                    <b>datestring</b>:req,eq,ne,gt,lt,gte,lte,between<br>
     *                                    <b>boolean</b>:req,eq,ne,true,false<br>
     *                                    <b>file</b>:exist,notexist,minsize,maxsize,req,image,doc,compression
     *                                    ,architecture,ext<br>
     *                                    <b>functions:</b><br>
     *                                    <ul>
     *                                    <li><b>fn.static.Class.methodstatic</b>: Call a static method</li>
     *                                    <li><b>fn.global.function</b>: Call a global function</li>
     *                                    <li><b>fn.object.Class.method</b>: Call a method where object is a global
     *                                    $object</li>
     *                                    <li><b>fn.class.\namespace\Class.method</b>: Call a satic method</li>
     *                                    </ul>
     * @param string|null $msg            The message to display. It could also use a special variable<br>
     *                                    Example:"the field with name %field does not exist"<br>
     *                                    <b>%field</b> = name of the field, it could be the friendid or the actual
     *                                    name<br>
     *                                    <b>%realfield</b> = name of the field (not the friendid)<br>
     *                                    <b>%value</b> = current value of the field<br>
     *                                    <b>%comp</b> = value to compare (if any). In array the value is []<br>
     *                                    <b>%first</b> = first value to compare (if the compare value is an array)<br>
     *                                    <b>%second</b> = second value to compare (if the compare value is an
     *                                    array)<br>
     *                                    <b>%key</b> = key used (for input array)<br>
     * @param mixed|null  $conditionValue (optional) Value used for some conditions. This value could be an array.
     * @param string      $level          =['error','warning','info','success'][$i]<br>
     *                                    The level of error to raise if the condition doesn't match (default is error)
     * @param mixed|null  $key            If key is not null then it is used for add more than one condition by key
     *
     * @return ValidationOne
     */
    public function condition(
        string  $condition,
        ?string $msg = "",
                $conditionValue = null,
        string  $level = 'error',
                $key = null
    ): ValidationOne
    {
        if (strpos($this->DATSARR, $this->type) !== false) {
            $conditionValue = $this->inputToDate($conditionValue);
        }
        if ($key !== null) {
            $this->conditions[$key][] = new ValidationItem($condition, $msg, $conditionValue, $level);
        } else {
            $this->conditions[] = new ValidationItem($condition, $msg, $conditionValue, $level);
        }
        return $this;
    }

    /**
     * The value mustn't be empty. It's equals than condition('ne')
     *
     * @param string $msg See condition() for more information
     *
     * @return $this
     * @see ValidationOne::condition
     */
    public function notempty(string $msg = ''): ValidationOne
    {
        $this->condition('ne', $msg);
        return $this;
    }

    /**
     * The value is required, so it must not be null or empty.<br>
     * It is different from exist(), where exist() validates that the field is asigned with any value
     * (including null or empty).<br>
     *
     * it's the same as $validation->condition("exist")
     *
     * @param bool   $required
     * @param string $msg See condition() for more information
     * @return $this
     * @see ValidationOne::condition
     */
    public function required(bool $required = true, string $msg = ''): ValidationOne
    {
        if ($required) {
            $this->condition('req', $msg);
        }
        return $this;
    }

    /**
     * @param FormOne|null $form
     *
     * @return ValidationOne
     * @noinspection PhpUndefinedClassInspection
     * @noinspection PhpMissingParamTypeInspection
     */
    public function useForm($form): ValidationOne
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
    public function friendId($id): ValidationOne
    {
        $this->friendId = $id;
        return $this;
    }

    /**
     * It sets the type for the current operation. The default type is 'string'<br>
     * This value important to validate the information.<br>
     * **Example:** $valid->type('integer')->set(20);<br>
     *
     * @param string|array $type =['integer','unixtime','boolean','decimal','float','varchar','string'
     *                           ,'date','datetime','datestring','datetimestring'][$i]
     *
     * @return ValidationOne $this
     */
    public function type($type): ValidationOne
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
    //</editor-fold>

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
            switch (true) {
                case (strpos($this->STRARR, $type) !== false):
                    $r = 1; // string
                    break;
                case (strpos($this->DATARR, $type) !== false):
                    $r = 2; // date
                    break;
                case ($type === 'boolean'):
                    $r = 3; // boolean
                    break;
                case ($type === 'file'):
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

    //<editor-fold desc="error control">

    /**
     * It is an alternative to get(), post() and request(). It reads a value from a literal os variable.
     *
     * @param mixed       $input   Input data.
     * @param string      $fieldId (optional)
     * @param string|null $msg     See condition() for more information     Used for the initial (basic) validation of
     *                             the data.
     * @param bool        $isMissing
     *
     * @return array|bool|DateTime|float|int|mixed|null
     * @throws JsonException
     */
    public function set($input, string $fieldId = "setfield", ?string $msg = "", bool $isMissing = false)
    {
        $this->isMissing = $isMissing;
        if ($this->override) {
            $this->messageList->items[$fieldId] = new MessageLocker();
        }
        if (is_object($input)) {
            $input = (array)$input;
        }
        if(is_array($input) && $this->invertIndexRow) {
            $input = self::invertArray($input);
        }
        $this->countError = $this->messageList->errorCount;
        if (is_array($input)) {

            if (!$this->isMissingValid || !$this->isMissing) { // bypass if missing is valid (and the value is missing)
                foreach ($input as $key => &$v) {
                    $this->originalValue = $v;
                    $currentField = ($this->isArrayFlat) ? $fieldId : $fieldId . "[" . $key . "]";
                    $v = $this->basicValidation($v, $currentField, $msg, $key);
                    //if ($this->abortOnError && $this->messageList->errorcount) break;
                    $this->runConditions($v, $currentField, $key);
                    if (($this->messageList->errorCount === 0) && $this->messageList->get($currentField)->countError()) {
                        $v = $this->default[$key] ?? null;
                    }
                }
                unset($v);
            }
        } else {
            if ($this->type === 'file') {
                $input = [$input, $input]; // [new file,old file]
            }
            $this->originalValue = $input;
            // bypass
            if ((!$this->isMissingValid || !$this->isMissing)) {
                $input = $this->basicValidation($input, $fieldId, $msg);
                if ($this->abortOnError !== false || $this->messageList->errorCount === 0) {
                    $this->runConditions($input, $fieldId);
                }
                if ($this->ifFailThenDefault && $this->messageList->get($fieldId)->countError()) {
                    $input = $this->default;
                }
            }
        }
        if ($this->messageList->errorCount === $this->countError && $this->successMessage !== null) {
            $this->messageList->addItem($this->successMessage['id'], $this->successMessage['msg'],
                $this->successMessage['level']);
        }
        if ($this->addToForm) {
            $this->callFormBack($fieldId);
        }
        $output = $this->endConversion($input);
        $this->resetChain();
        return $output;
    }

    /**
     * It gets the first error message available in the whole messagelist.
     *
     * @param bool $withWarning
     *
     * @return null|string
     */
    public function getMessage(bool $withWarning = false): ?string
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
    public function getMessages(bool $withWarning = false): array
    {
        if ($withWarning) {
            $this->messageList->allErrorOrWarningArray();
        }
        return $this->messageList->allErrorArray();
    }

    /**
     * It returns the error of the element "id".  If it doesn't exist then it returns an empty MessageLocker
     *
     * @param string $idLocker
     *
     * @return MessageLocker
     */
    public function getMessageId(string $idLocker): MessageLocker
    {
        return $this->messageList->get($idLocker);
    }

    /**
     * It returns the number of errors (or errors and warnings)
     *
     * @param bool $includeWarning If true then it also includes the warning.
     * @return int
     */
    public function errorCount(bool $includeWarning = false): int
    {
        return $includeWarning
            ? $this->messageList->errorCount
            : $this->messageList->errorOrWarningCount;
    }


    /**
     * It returns true if there is an error (or error and warning).
     *
     * @param bool $includeWarning If true then it also returns if there is a warning
     * @return bool
     */
    public function hasError(bool $includeWarning = false): bool
    {
        return $this->messageList->hasError($includeWarning);
    }

    //</editor-fold>
}
