<?php /** @noinspection ReturnTypeCanBeDeclaredInspection
 * @noinspection PhpMissingStrictTypesDeclarationInspection
 * @noinspection PhpMissingReturnTypeInspection
 * @noinspection PhpMissingParamTypeInspection
 * @noinspection PhpUnusedParameterInspection
 * @noinspection UnknownInspectionInspection
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
 * @version       2.1 2022-29-01
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see           https://github.com/EFTEC/ValidationOne
 */
class ValidationInputOne {
    /** @var MessageContainer */
    public $messageList;
    public $prefix = '';

    /** @var bool If true then the field exists (it could be null or empty) otherwise it generates an error */
    public $exist = false;
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
     * @param MessageContainer $messageList Optional. It autowires to a message list (if any), otherwise it creates a new one.
     */
    public function __construct($prefix = '', $messageList = null) {
        $this->prefix = $prefix;
        if ($messageList !== null) {
            $this->messageList = $messageList;
        } elseif (function_exists('messages')) {
            $this->messageList = messages();
        } else {
            $this->messageList = new MessageContainer();
        }
    }

    /**
     * If it's unable to fetch then it generates an error.<br>
     * However, by default it also returns the default value.
     * This validation doesn't fail if the field is empty or zero. Only if it's unable to fetch the value.
     *
     * @param bool $exist
     *
     * @return ValidationInputOne
     * @see ValidationOne::def()
     */
    public function exist($exist = true): ValidationInputOne
    {
        $this->exist = $exist;
        return $this;
    }

    /**
     * It's a friendly id used to replace the "id" used in message. For example: "id customer" instead of "idcustomer"
     *
     * @param $id
     *
     * @return ValidationInputOne
     */
    public function friendId($id): ValidationInputOne
    {
        $this->friendId = $id;
        return $this;
    }

    /**
     * It gets a field using the method GET.
     *
     * @param string $field The name of the field. By default, the library adds a prefix (if any)
     * @param null   $msg
     * @param bool   $isMissing
     *
     * @return array|bool|DateTime|float|int|mixed|null
     */
    public function get($field = "", $msg = null, &$isMissing = false) {
        return $this->getField($field, 1, $msg, $isMissing); // get
    }

    /**
     * Returns null if the value is not present, false if the value is incorrect and the value if it's correct
     *
     * @param string      $field      The name of the field. By default, the library adds a prefix (if any)
     * @param int|string  $inputType =[0,1,99][$i] // [INPUT_REQUEST 99,INPUT_POST 0,INPUT_GET 1] or it could be the value (for set)
     * @param null|string $msg
     * @param bool        $isMissing (ref). It's true if the value is missing (it's not set).
     *
     * @return array|mixed|null
     * @noinspection DuplicatedCode
     */
    public function getField($field, $inputType = 99, $msg = null, &$isMissing = false) {
        $fieldId = $this->prefix . $field;
        switch ($inputType) {
            case 0: // post
                if (!array_key_exists($fieldId,$_POST)) {
                    $isMissing = true;
                    return $this->initial ?? $this->default;
                }
                $r = $_POST[$fieldId];
                $r = ($r === NULLVAL) ? null : $r;
                break;
            case 1: //get

                if (!array_key_exists($fieldId,$_GET)) {
                    $isMissing = true;
                    return $this->initial ?? $this->default;
                }
                $r = $_GET[$fieldId];

                $r = ($r === NULLVAL) ? null : $r;
                break;
            case 99: // request
                if (array_key_exists($fieldId,$_POST)) {
                    $r = $_POST[$fieldId];
                } else {
                    if (!array_key_exists($fieldId,$_GET)) {
                        $isMissing = true;
                        return $this->initial ?? $this->default;
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
     * Returns null if the value is not present, false if the value is incorrect and the value if it's correct
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
    public function getFile($field, $array = false, &$msg = null, &$isMissing = false)
    {
        $fieldId = $this->prefix . $field;
        if (!$array) {
            $fileNew = self::sanitizeFileName(@$_FILES[$fieldId]['name']);
            if ($fileNew != "") {
                // it's uploading a file
                $fileTmp = @$_FILES[$fieldId]['tmp_name'];
                return [$fileNew, $fileTmp];
            }
            // it's not uploading a file.
            $isMissing = true;
            //return ($this->initial===null)?$this->default:$this->initial;
            return ($this->initial === null) ? $this->default : ['', ''];
        }
        $filenames = array();
        foreach ($_FILES[$fieldId]['name'] as $iValue) {
            $fileNew = self::sanitizeFileName(@$iValue);
            if ($fileNew != "") {
                // it's uploading a file
                $fileTmp = @$_FILES[$fieldId]['tmp_name'];
            } else {
                // it's not uploading a file.
                $fileTmp = '';
                $fileNew = '';
            }
            $r = [$fileNew, $fileTmp];
            $filenames[] = $r;
        }
        return $filenames;
    }

    /**
     * Sanitize a filename removing ".." and other nasty characters.
     * if mb_string is available then it also allows multibyte string characters such as accents.
     *
     * @param string $filename
     *
     * @return false|string|null
     * @noinspection CallableParameterUseCaseInTypeContextInspection
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
