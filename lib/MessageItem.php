<?php

namespace eftec;

/**
 * Class MessageItem
 * @package eftec
 * @author Jorge Castro Castillo
 * @version 1.7 20181015
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see https://github.com/EFTEC/ValidationOne
 */
class MessageItem
{
    /** @var string[] */
    private $errorMsg;
    /** @var string[] */
    private $warningMsg;
    /** @var string[] */
    private $infoMsg;
    /** @var string[] */
    private $successMsg;
    /**
     * MessageItem constructor.
     */
    public function __construct()
    {
        $this->errorMsg=[];
        $this->warningMsg=[];
        $this->infoMsg=[];
        $this->successMsg=[];
    }
    public function addMessage($msg) {
        @$this->errorMsg[]=$msg;
    }
    public function addWarning($msg) {
        @$this->warningMsg[]=$msg;
    }
    public function addInfo($msg) {
        @$this->infoMsg[]=$msg;
    }
    public function addSuccess($msg) {
        @$this->successMsg[]=$msg;
    }

    public function countMessage() {
        return count($this->errorMsg);
    }
    public function countWarning() {
        return count($this->warningMsg);
    }
    public function countInfo() {
        return count($this->infoMsg);
    }
    public function countSuccess() {
        return count($this->successMsg);
    }
    public function firstMessage() {
        if (isset($this->errorMsg[0])) {
            return $this->errorMsg[0];
        }
        return null;
    }
    public function firstMessageOrWarning() {
        $r=$this->firstMessage();
        if ($r===null) $r=$this->firstWarning();
        return $r;
    }
    public function firstWarning() {
        if (isset($this->warningMsg[0])) {
            return $this->warningMsg[0];
        }
        return null;
    }
    public function firstInfo() {
        if (isset($this->infoMsg[0])) {
            return $this->infoMsg[0];
        }
        return null;
    }
    public function firstSuccess() {
        if (isset($this->successMsg[0])) {
            return $this->successMsg[0];
        }
        return null;
    }

    /**
     * It returns the first message.<br>
     * If error then it returns the first message of error<br>
     * If not, if warning then it returns the first message of warning<br>
     * If not, then it show the first info message (if any)<br>
     * If not, then it shows the first success message (if any)<br>
     * If not, then it shows the default message.
     * @param string $defaultMsg
     * @return string
     */
    public function first($defaultMsg='') {
        $r=$this->firstMessage();
        if ($r!==null) return $r;
        $r=$this->firstWarning();
        if ($r!==null) return $r;
        $r=$this->firstInfo();
        if ($r!==null) return $r;
        $r=$this->firstSuccess();
        if ($r!==null) return $r;
        return $defaultMsg;
    }

    /**
     * @return null|string[]
     */
    public function allMessage() {
        return $this->errorMsg;
    }
    /**
     * @return null|string[]
     */
    public function allMessageOrWarning() {

        return @array_merge($this->errorMsg,$this->warningMsg);
    }
    /**
     * @return array|string[]
     */
    public function allWarning() {
        return $this->warningMsg;
    }

    /**
     * @return array|string[]
     */
    public function allInfo() {
        return $this->infoMsg;
    }
    /**
     * @return array|string[]
     */
    public function allSuccess() {
        return $this->successMsg;
    }
}