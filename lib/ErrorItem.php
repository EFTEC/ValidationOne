<?php

namespace eftec;

/**
 * Class ErrorItem
 * @package eftec
 * @author Jorge Castro Castillo
 * @version 1.0 20180930
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see https://github.com/EFTEC/ValidationOne
 */
class ErrorItem
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
     * ErrorItem constructor.
     */
    public function __construct()
    {
        $this->errorMsg=[];
        $this->warningMsg=[];
        $this->infoMsg=[];
        $this->successMsg=[];
    }
    public function addError($msg) {
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

    public function countError() {
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
    public function firstError() {
        if (isset($this->errorMsg[0])) {
            return $this->errorMsg[0];
        }
        return null;
    }
    public function firstErrorOrWarning() {
        $r=$this->firstError();
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
     * @return null|string[]
     */
    public function allError() {
        return $this->errorMsg;
    }
    /**
     * @return null|string[]
     */
    public function allErrorOrWarning() {

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