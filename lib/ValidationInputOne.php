<?php /**
 * @noinspection PhpUnusedParameterInspection
 * @noinspection RegExpRedundantEscape
 */

//declare(strict_types=1);
namespace eftec;
/**
 * Class InputOne<br>
 * This class manages the input entries, such as GET,POST,REQUEST
 *
 * @package       eftec
 * @author        Jorge Castro Castillo
 * @version       2.2 2022-08-27
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see           https://github.com/EFTEC/ValidationOne
 */
class ValidationInputOne
{
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
     * @param string                $prefix
     * @param MessageContainer|null $messageList
     * @return ValidationInputOne
     */
    public static function getInstance(string $prefix = '', ?MessageContainer $messageList = null): ValidationInputOne
    {
        return new static($prefix, $messageList);
    }

    /**
     * InputOne constructor.<br>
     * If you want to create an instance, then call the method this::getInstance()
     *
     * @param string                $prefix
     * @param MessageContainer|null $messageList Optional. It auto-wires to a message list (if any), otherwise it
     *                                           creates a new one.
     */
    protected function __construct(string $prefix = '', ?MessageContainer $messageList = null)
    {
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
    public function exist(bool $exist = true): ValidationInputOne
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
     * Returns null if the value is not present, false if the value is incorrect and the value if it's correct
     *
     * @param string      $field     The name of the field. By default, the library adds a prefix (if any)
     * @param int         $inputType =[0,1,99][$i] <br>
     *                               INPUT_REQUEST: 99<br>
     *                               INPUT_POST: 0<br>
     *                               INPUT_GET: 1
     * @param null|string $msg
     * @param bool        $isMissing (ref). It's true if the value is missing (it's not set).
     *
     * @return array|mixed|null
     * @noinspection DuplicatedCode
     */
    public function getField(string $field, int $inputType = 99, ?string $msg = null, bool &$isMissing = false)
    {
        $fieldId = $this->prefix . $field;
        switch ($inputType) {
            case 0: // post
                if (!array_key_exists($fieldId, $_POST)) {
                    $isMissing = true;
                    return $this->initial ?? $this->default;
                }
                $r = $_POST[$fieldId];
                $r = ($r === NULLVAL) ? null : $r;
                break;
            case 1: //get
                if (!array_key_exists($fieldId, $_GET)) {
                    $isMissing = true;
                    return $this->initial ?? $this->default;
                }
                $r = $_GET[$fieldId];
                $r = ($r === NULLVAL) ? null : $r;
                break;
            case 99: // request
                if (array_key_exists($fieldId, $_POST)) {
                    $r = $_POST[$fieldId];
                } else {
                    if (!array_key_exists($fieldId, $_GET)) {
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


    /**
     * Returns null if the value is not present, false if the value is incorrect and the value if it's correct
     *
     * @param string      $field
     * @param bool        $isArray
     * @param string|null $msg
     * @param bool        $isMissing
     *
     * @return null|array=[current filename,temporal name]
     * @internal param $folder
     * @internal param string $type
     */
    public function getFile(string $field, bool $isArray = false, ?string &$msg = null, bool &$isMissing = false): ?array
    {
        $fieldId = $this->prefix . $field;
        if (!$isArray) {
            $fileNew = self::sanitizeFileName(@$_FILES[$fieldId]['name']);
            if ($fileNew !== "" && $fileNew !== null) {
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
            if ($fileNew !== "" && $fileNew !== null) {
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
     * @param string|null $filename
     *
     * @return false|string|null
     */
    public static function sanitizeFileName(?string $filename)
    {
        if (empty($filename)) {
            return "";
        }
        if (function_exists("mb_ereg_replace")) {
            $filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
            $filename = mb_ereg_replace("([\.]{2,})", '', $filename);
        } else {
            $filename = preg_replace("([^\w\s\-_~,;\[\]\(\).])", '', $filename);
            $filename = preg_replace("(\.{2,})", '', $filename);
        }
        return $filename;
    }

}
