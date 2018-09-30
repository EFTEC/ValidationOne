<?php
namespace eftec;

/**
 * Class ErrorList
 * @package eftec
 * @author Jorge Castro Castillo
 * @version 1.0 20180930
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see https://github.com/EFTEC/ValidationOne
 */
class ErrorList
{
    /** @var  ErrorItem[] */
    var $items;
    var $errorcount=0;
    var $warningcount=0;
    var $infocount=0;
    var $successcount=0;
    var $firstError=null;
    var $firstWarning=null;
    var $firstInfo=null;
    var $firstSuccess=null;

    /**
     * ErrorList constructor.
     */
    public function __construct()
    {
        $this->items=array();
    }

    public function resetAll() {
        $this->errorcount=0;
        $this->warningcount=0;
        $this->infocount=0;
        $this->successcount=0;
        $this->items=array();
        $this->firstError=null;
        $this->firstWarning=null;
        $this->firstInfo=null;
        $this->firstSuccess=null;
    }
    /**
     * @param string $message
     * @param string $level = 2(error),1(warning) 0(no error) ERRORLIST_*
     * @param string $id
     */
    public function addItem($id,$message,$level='error') {
        if (!isset($this->items[$id])) {
            $this->items[$id]=new ErrorItem();
        }
        switch ($level) {
            case 'error':
                $this->errorcount++;
                if ($this->firstError===null) $this->firstError=$message;
                $this->items[$id]->addError($message);
                break;
            case 'warning':
                $this->warningcount++;
                if ($this->firstWarning===null) $this->firstWarning=$message;
                $this->items[$id]->addWarning($message);
                break;
            case 'info':
                $this->infocount++;
                if ($this->firstInfo===null) $this->firstInfo=$message;
                $this->items[$id]->addInfo($message);
                break;
            case 'success':
                $this->successcount++;
                if ($this->firstSuccess===null) $this->firstSuccess=$message;
                $this->items[$id]->addSuccess($message);
                break;
        }
    }

    /**
     * It returns an error item. If the item doesn't exist then it returns an empty object (not null)
     * @param $id
     * @return ErrorItem
     */
    public function get($id) {
        if (!isset($this->items[$id])) {
            return new ErrorItem(); // we returns an empty error.
        }
        return $this->items[$id];
    }

    /**
     * find a value by the index and returns the text (bootstrap 4)
     * @param string $idx
     * @return string
     */
    public function class($idx) {
        if (!isset($this->items[$idx])) return "";
        if (@$this->items[$idx]->countError()) {
            return "danger";
        }
        if ($this->items[$idx]->countWarning()) {
            return "warning";
        }
        if ($this->items[$idx]->countInfo()) {
            return "info";
        }
        if ($this->items[$idx]->countSuccess()) {
            return "success";
        }
        return "";
    }
    public function firstErrorText() {
        return ($this->errorcount==0)?"":$this->firstError;
    }

    public function firstErrorOrWarning() {
        if ($this->errorcount) return $this->firstError;
        return ($this->warningcount==0)?"":$this->firstWarning;
    }

    public function firstWarningText() {
        return ($this->warningcount==0)?"":$this->firstWarning;
    }

    public function firstInfoText() {
        return ($this->infocount==0)?"":$this->firstInfo;
    }
    public function firstSuccessText() {
        return ($this->successcount==0)?"":$this->firstSuccess;
    }
    public function allErrorArray() {
        $r=array();
        foreach($this->items as $v) {
            $r=array_merge($r,$v->allError());
        }
        return $r;
    }
    public function allArray() {
        $r=array();
        foreach($this->items as $v) {
            $r=array_merge($r,$v->allError());
            $r=array_merge($r,$v->allWarning());
            $r=array_merge($r,$v->allInfo());
            $r=array_merge($r,$v->allSuccess());
        }
        return $r;
    }
    public function allErrorOrWarningArray() {
        $r=array();
        foreach($this->items as $v) {
            $r=array_merge($r,$v->allError());
            $r=array_merge($r,$v->allWarning());
        }
        return $r;
    }
}