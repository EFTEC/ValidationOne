<?php
/** @noinspection UnknownInspectionInspection
 * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
 * @noinspection TypeUnsafeComparisonInspection
 * @noinspection RegExpRedundantEscape
 */

//declare(strict_types=1);

namespace eftec;

use DateTime;

/**
 * Class InputOne
 *
 * @package       eftec
 * @author        Jorge Castro Castillo
 * @version       1.1 2019-mar-8
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see           https://github.com/EFTEC/ValidationOne
 */
class ValidationInputOne {
    /** @var MessageList */
    public $messageList;
    public $prefix = '';

    /** @var bool If true then the field is required otherwise it generates an error */
    public $required = false;
    /** @var mixed default value */
    public $default;
    /** @var mixed default value */
    public $initial;
    public $ifFailThenOrigin = false;

    /** @var mixed It keeps a copy of the original value (after get/post/fetch or set) */
    public $originalValue;
    /** @var string It's a friendly id used to replace the "id" used in message. For example: "id customer" instead of "idcustomer" */
    public $friendId;

    /**
     * InputOne constructor.
     *
     * @param string      $prefix
     * @param MessageList $messageList Optional. It autowires to a message list (if any), otherwise it creates a new one.
     */
    public function __construct($prefix = '', $messageList = null) {
        $this->prefix = $prefix;
        if ($messageList !== null) {
            $this->messageList = $messageList;
        } elseif (function_exists('messages')) {
            $this->messageList = messages();
        } else {
            $this->messageList = new MessageList();
        }
    }

    /**
     * If it's unable to fetch then it generates an error.<br>
     * However, by default it also returns the default value.
     * This validation doesn't fail if the field is empty or zero. Only if it's unable to fetch the value.
     *
     * @param bool $required
     *
     * @return ValidationInputOne
     * @see ValidationOne::def()
     */
    public function required($required = true) {
        $this->required = $required;
        return $this;
    }

    /**
     * It's a friendly id used to replace the "id" used in message. For example: "id customer" instead of "idcustomer"
     *
     * @param $id
     *
     * @return ValidationInputOne
     */
    public function friendId($id) {
        $this->friendId = $id;
        return $this;
    }

    /**
     * @param string $field
     * @param null   $msg
     * @param bool   $isMissing
     *
     * @return array|bool|DateTime|float|int|mixed|null
     */
    public function get($field = "", $msg = null, &$isMissing = false) {
        return $this->getField($field, 1, $msg, $isMissing); // get
    }

    /**
     * Returns null if the value is not present, false if the value is incorrect and the value if its correct
     *
     * @param string      $field     id of the field, without the prefix.
     * @param int|string  $inputType =[0,1,99][$i] // [INPUT_REQUEST 99,INPUT_POST 0,INPUT_GET 1] or it could be the value (for set)
     * @param null|string $msg
     * @param bool        $isMissing (ref). It's true if the value is missing (it's not set).
     *
     * @return array|mixed|null
     * @noinspection DuplicatedCode
     */
    public function getField($field, $inputType = 99, $msg = null, &$isMissing = false) {
        $fieldId = $this->prefix . $field;
        $r = null;

        switch ($inputType) {
            case 0: // post
                if (!isset($_POST[$fieldId])) {
                    $isMissing = true;
                    if ($this->required) {
                        $this->addMessageInternal($msg, "Field is missing", $field, "", "", 'error');
                    }
                    return ($this->initial === null) ? $this->default : $this->initial;
                }
                $r = $_POST[$fieldId];
                $r = ($r === NULLVAL) ? null : $r;
                break;
            case 1: //get

                if (!isset($_GET[$fieldId])) {
                    $isMissing = true;
                    if ($this->required) {
                        $this->addMessageInternal($msg, "Field is missing", $field, "", "", 'error');
                    }
                    return ($this->initial === null) ? $this->default : $this->initial;
                }
                $r = $_GET[$fieldId];

                $r = ($r === NULLVAL) ? null : $r;
                break;
            case 99: // request
                if (isset($_POST[$fieldId])) {
                    $r = $_POST[$fieldId];
                } else {
                    if (!isset($_GET[$fieldId])) {
                        $isMissing = true;
                        if ($this->required) {
                            $this->addMessageInternal($msg, "Field is missing", $field, "", "", 'error');
                        }
                        return ($this->initial === null) ? $this->default : $this->initial;
                    }
                    $r = $_GET[$fieldId];
                    $r = ($r === NULLVAL) ? null : $r;
                }
                break;
            default:
                trigger_error("input type " . $inputType . " not defined for getField()");
                $isMissing = false;
                $r = null;
        }
        return $r;
    }

    /**
     * It adds an error
     *
     * @param string $msg     first message. If it's empty or null then it uses the second message<br>
     *                        Message could uses the next variables '%field','%realfield','%value','%comp','%first','%second'
     * @param string $msg2    second message
     * @param string $fieldId id of the field
     * @param mixed  $value   value supplied
     * @param mixed  $vcomp   value to compare.
     * @param string $level   (error,warning,info,success) error level
     */
    private function addMessageInternal($msg, $msg2, $fieldId, $value, $vcomp, $level = 'error') {
        $txt = ($msg) ?: $msg2;
        if (is_array($vcomp)) {
            $first = @$vcomp[0];
            $second = @$vcomp[1];
            $vcomp = @$vcomp[0]; // is not array anymore
        } else {
            $first = $vcomp;
            $second = $vcomp;
        }
        if (is_array($this->originalValue)) {
            $txt = str_replace(['%field', '%realfield', '%value', '%comp', '%first', '%second'], [
                ($this->friendId === null) ? $fieldId : $this->friendId,
                $fieldId,
                $value,
                $vcomp,
                $first,
                $second
            ], $txt);
        } else {
            $txt = str_replace(['%field', '%realfield', '%value', '%comp', '%first', '%second'], [
                ($this->friendId === null) ? $fieldId : $this->friendId,
                $fieldId,
                $this->originalValue,
                $vcomp,
                $first,
                $second
            ], $txt);
        }
        $this->messageList->addItem($fieldId, $txt, $level);
    }

    public function post($field, $msg = null, &$isMissing = false) {
        return $this->getField($field, 0, $msg, $isMissing);
    }

    public function request($field, $msg = null, &$isMissing = false) {
        return $this->getField($field, 99, $msg, $isMissing);
    }

    /**
     * It fetches a value.
     *
     * @param int         $inputType INPUT_POST(0)|INPUT_GET(1)|INPUT_REQUEST(99)
     * @param string      $field
     * @param null|string $msg
     * @param bool        $isMissing
     *
     * @return mixed
     */
    public function fetch($inputType, $field, $msg = null, &$isMissing = false) {
        return $this->getField($field, $inputType, $msg, $isMissing);
    }

    /**
     * Returns null if the value is not present, false if the value is incorrect and the value if its correct
     *
     * @param             $field
     * @param bool        $array
     * @param string|null $msg
     * @param bool        $isMissing
     *
     * @return array=[current filename,temporal name]
     * @internal param $folder
     * @internal param string $type
     */
    public function getFile($field, $array = false, &$msg = null, &$isMissing = false) {
        $fieldId = $this->prefix . $field;
        if (!$array) {
            $fileNew = self::sanitizeFileName(@$_FILES[$fieldId]['name']);
            if ($fileNew != "") {
                // its uploading a file
                $fileTmp = @$_FILES[$fieldId]['tmp_name'];
                return [$fileNew, $fileTmp];
            }

// its not uploading a file.
            $isMissing = true;
            if ($this->required) {
                $this->addMessageInternal($msg, "Field is missing", $field, "", "", 'error');
            }
            //return ($this->initial===null)?$this->default:$this->initial;
            return ($this->initial === null) ? $this->default : ['', ''];
        }

// is array.
        $filenames = array();
        foreach ($_FILES[$fieldId]['name'] as $iValue) {
            $fileNew = self::sanitizeFileName(@$iValue);
            if ($fileNew != "") {
                // its uploading a file
                $fileTmp = @$_FILES[$fieldId]['tmp_name'];
                $r = [$fileNew, $fileTmp];
            } else {
                // its not uploading a file.
                $fileTmp = '';
                $fileNew = '';
                $r = [$fileNew, $fileTmp];
            }
            $filenames[] = $r;
        }
        return $filenames;
    }

    /**
     * Sanitize a filename removing .. and other nasty characters.
     * if mb_string is available then it also allows multibyte string characters such as accents.
     *
     * @param string $filename
     *
     * @return false|string|null
     */
    public static function sanitizeFileName($filename) {
        if (empty($filename)) {
            return "";
        }
        if (function_exists("mb_ereg_replace")) {
            $filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
            $filename = mb_ereg_replace("([\.]{2,})", '', $filename);
        } else {
            $filename = preg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
            $filename = preg_replace("([\.]{2,})", '', $filename);
        }
        return $filename;
    }

}