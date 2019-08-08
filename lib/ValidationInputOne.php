<?php
//declare(strict_types=1);

namespace eftec;

/**
 * Class InputOne
 * @package eftec
 * @author Jorge Castro Castillo
 * @version 1.1 2019-mar-8
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see https://github.com/EFTEC/ValidationOne
 */
class ValidationInputOne
{
	/** @var MessageList */
	var $messageList;

	var $prefix='';

	/** @var bool If true then the field is required otherwise it generates an error */
	public $required=false;
	/** @var mixed default value */
	public $default=null;
	/** @var mixed default value */
	public $initial=null;
	public $ifFailThenOrigin=false;


	/** @var mixed It keeps a copy of the original value (after get/post/fetch or set) */
	public $originalValue=null;
	/** @var string It's a friendly id used to replace the "id" used in message. For example: "id customer" instead of "idcustomer" */
	public $friendId=null;

	/**
	 * InputOne constructor.
	 * @param string $prefix
	 * @param MessageList $messageList Optional. It autowires to a message list (if any), otherwise it creates a new one.
	 */
	public function __construct($prefix='',$messageList=null)
	{
		$this->prefix=$prefix;
		if ($messageList!==null) {
			$this->messageList=$messageList;
		} else {
			if (function_exists('messages')) {
				$this->messageList = messages();
			} else {
				$this->messageList = new MessageList();
			}
		}
	}

	/**
	 * If it's unable to fetch then it generates an error.<br>
	 * However, by default it also returns the default value.
	 * This validation doesn't fail if the field is empty or zero. Only if it's unable to fetch the value.
	 * @param bool $required
	 * @return ValidationInputOne
	 * @see ValidationOne::def()
	 */
	public function required($required=true) {
		$this->required=$required;
		return $this;
	}

	/**
	 * It's a friendly id used to replace the "id" used in message. For example: "id customer" instead of "idcustomer"
	 * @param $id
	 * @return ValidationInputOne
	 */
	public function friendId($id) {
		$this->friendId=$id;
		return $this;
	}

	/**
	 * @param string $field
	 * @param null $msg
	 * @param bool $isMissing
	 * @return array|bool|\DateTime|float|int|mixed|null
	 */
	public function get($field="",$msg=null,&$isMissing=false) {
		$r=$this->getField($field,INPUT_GET,$msg,$isMissing);
		return $r;
	}

	/**
	 * Returns null if the value is not present, false if the value is incorrect and the value if its correct
	 * @param string $field id of the field, without the prefix.
	 * @param int|string $inputType=[INPUT_REQUEST,INPUT_POST,INPUT_GET][$i] or it could be the value (for set)
	 * @param null|string $msg
	 * @param bool $isMissing
	 * @return array|mixed|null
	 */
	public function getField($field,$inputType=INPUT_REQUEST,$msg=null,&$isMissing=false) {


		$fieldId=$this->prefix.$field;
		$r=null;


		switch ($inputType) {
			case INPUT_POST:
				if (!isset($_POST[$fieldId])) {
					$isMissing=true;
					if ($this->required) $this->addMessageInternal($msg,"Field is missing",$fieldId,"","",'error');
					return ($this->initial===null)?$this->default:$this->initial;
				}
				$r=$_POST[$fieldId];
				$r=($r===NULLVAL)?null:$r;
				break;
			case INPUT_GET:

				if (!isset($_GET[$fieldId])) {
					$isMissing=true;
					if ($this->required) $this->addMessageInternal($msg,"Field is missing",$fieldId,"","",'error');
					return ($this->initial===null)?$this->default:$this->initial;
				}
				$r=$_GET[$fieldId];
           
				$r=($r===NULLVAL) ?null:$r;
				break;
			case INPUT_REQUEST:
				if (isset($_POST[$fieldId]) ) {
					$r=$_POST[$fieldId];
				}  else {
					if (!isset($_GET[$fieldId]) ) {
						$isMissing=true;
						if ($this->required) $this->addMessageInternal($msg,"Field is missing",$fieldId,"","",'error');
						return ($this->initial===null)?$this->default:$this->initial;
					}
					$r=$_GET[$fieldId];
					$r=($r===NULLVAL) ?null:$r;
				}
				break;
			default:
				trigger_error("input type ".$inputType." not defined for getField()");
				$isMissing=false;
				$r=null;
		}
		return $r;
	}

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
		if (is_array($this->originalValue)) {
			$txt=str_replace(['%field','%realfield','%value','%comp','%first','%second']
				,[($this->friendId===null)?$fieldId:$this->friendId,$fieldId
					,$value,$vcomp,$first,$second],$txt);
		} else {
			$txt=str_replace(['%field','%realfield','%value','%comp','%first','%second']
				,[($this->friendId===null)?$fieldId:$this->friendId,$fieldId
					,$this->originalValue,$vcomp,$first,$second],$txt);
		}
		$this->messageList->addItem($fieldId,$txt, $level);
	}

	public function post($field,$msg=null,&$isMissing=false) {
		$r=$this->getField($field,INPUT_POST,$msg,$isMissing);
		return $r;
	}

	public function request($field,$msg=null,&$isMissing=false) {
		$r=$this->getField($field,INPUT_REQUEST,$msg,$isMissing);
		return $r;
	}

	/**
	 * It fetches a value.
	 * @param int $inputType INPUT_POST|INPUT_GET|INPUT_REQUEST
	 * @param string $field
	 * @param null|string $msg
	 * @param bool $isMissing
	 * @return mixed
	 */
	public function fetch($inputType,$field,$msg=null,&$isMissing=false) {
		$r=$this->getField($field,$inputType,$msg,$isMissing);
		return $r;
	}

	/**
	 * Returns null if the value is not present, false if the value is incorrect and the value if its correct
	 * @param $field
	 * @param bool $array
	 * @param string|null $msg
	 * @param bool $isMissing
	 * @return array=[current filename,temporal name]
	 * @internal param $folder
	 * @internal param string $type
	 */
	public function getFile($field,$array=false,&$msg=null,&$isMissing=false)
	{
		$fieldId=$this->prefix.$field;
		if (!$array) {
			$fileNew=self::sanitizeFileName( @$_FILES[$fieldId]['name']);
			if ($fileNew!="") {
				// its uploading a file
				$fileTmp=@$_FILES[$fieldId]['tmp_name'];
				return [$fileNew,$fileTmp];
			} else {
				// its not uploading a file.
				$isMissing=true;
				if ($this->required) $this->addMessageInternal($msg,"Field is missing",$field,"","",'error');
				//return ($this->initial===null)?$this->default:$this->initial;
				return ($this->initial===null)?$this->default: ['',''];
			}
		} else {
			// is array.
			$c=count($_FILES[$fieldId]['name']);
			$filenames=array();
			for($i=0;$i<$c;$i++) {
				$fileNew=self::sanitizeFileName( @$_FILES[$fieldId]['name'][$i]);
				if ($fileNew!="") {
					// its uploading a file
					$fileTmp=@$_FILES[$fieldId]['tmp_name'];
					$r=[$fileNew,$fileTmp];
				} else {
					// its not uploading a file.
					$fileTmp='';
					$fileNew='';
					$r=[$fileNew,$fileTmp];
				}
				$filenames[]=$r;
			}
			return $filenames;
		}
	}

	/**
	 * Sanitize a filename removing .. and other nasty characters.
	 * if mb_string is available then it also allows multibyte string characters such as accents.
	 * @param string $filename
	 * @return false|string|null
	 */
	public static function sanitizeFileName($filename) {
		if (empty($filename)) return "";
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