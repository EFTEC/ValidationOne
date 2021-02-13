<?php /** @noinspection UnknownInspectionInspection */
/** @noinspection SlowArrayOperationsInLoopInspection */

/** @noinspection PhpUnused */

namespace eftec;

/**
 * Class MessageList
 *
 * @package       eftec
 * @author        Jorge Castro Castillo
 * @version       1.9 20181015
 * @copyright (c) Jorge Castro C. LGLPV2 License  https://github.com/EFTEC/ValidationOne
 * @see           https://github.com/EFTEC/ValidationOne
 */
class MessageList {
    /** @var  MessageItem[] Array of containers */
    public $items;
    /** @var int Number of errors stored globally */
    public $errorcount = 0;
    /** @var int Number of warnings stored globally */
    public $warningcount = 0;
    /** @var int Number of errors or warning stored globally */
    public $errorOrWarning = 0;
    /** @var int Number of information stored globally */
    public $infocount = 0;
    /** @var int Number of success stored globally */
    public $successcount = 0;
    /** @var string[] Used to convert a type of message to a css class */
    public $cssClasses=['error'=>'danger','warning'=>'warning','info'=>'info','success'=>'success'];
    private $firstError;
    private $firstWarning;
    private $firstInfo;
    private $firstSuccess;

    /**
     * MessageList constructor.
     */
    public function __construct() {
        $this->items = array();
    }

    public function resetAll() {
        $this->errorcount = 0;
        $this->warningcount = 0;
        $this->errorOrWarning = 0;
        $this->infocount = 0;
        $this->successcount = 0;
        $this->items = array();
        $this->firstError = null;
        $this->firstWarning = null;
        $this->firstInfo = null;
        $this->firstSuccess = null;
    }

    /**
     * You could add a message (including errors,warning..) and store in a $id
     *
     * @param string $id      Identified of the container message (where the message will be stored)
     * @param string $message message to show. Example: 'the value is incorrect'
     * @param string $level   =['error','warning','info','success'][$i]
     */
    public function addItem($id, $message, $level = 'error') {
        $id = ($id === '') ? '0' : $id;
        if (!isset($this->items[$id])) {
            $this->items[$id] = new MessageItem();
        }
        switch ($level) {
            case 'error':
                $this->errorcount++;
                $this->errorOrWarning++;
                if ($this->firstError === null) {
                    $this->firstError = $message;
                }
                $this->items[$id]->addError($message);
                break;
            case 'warning':
                $this->warningcount++;
                $this->errorOrWarning++;
                if ($this->firstWarning === null) {
                    $this->firstWarning = $message;
                }
                $this->items[$id]->addWarning($message);
                break;
            case 'info':
                $this->infocount++;
                if ($this->firstInfo === null) {
                    $this->firstInfo = $message;
                }
                $this->items[$id]->addInfo($message);
                break;
            case 'success':
                $this->successcount++;
                if ($this->firstSuccess === null) {
                    $this->firstSuccess = $message;
                }
                $this->items[$id]->addSuccess($message);
                break;
        }
    }

    /**
     * @return array
     */
    public function allIds() {
        return array_keys($this->items);
    }

    /**
     * It returns an error item. If the item doesn't exist then it returns an empty object (not null)
     *
     * @param string $id Id of the container
     *
     * @return MessageItem
     */
    public function get($id) {
        $id = ($id === '') ? '0' : $id;
        if (!isset($this->items[$id])) {
            return new MessageItem(); // we returns an empty error.
        }
        return $this->items[$id];
    }

    /**
     * It returns a css class associated with the type of errors inside a container<br>
     * If the container contains more than one message, then it uses the most severe one (error,warning,etc.)
     *
     * @param string $id Id of the container
     *
     * @return string
     */
    public function cssClass($id) {
        $id = ($id === '') ? '0' : $id;
        if (!isset($this->items[$id])) {
            return '';
        }
        if (@$this->items[$id]->countError()) {
            return $this->cssClasses['error'];
        }
        if ($this->items[$id]->countWarning()) {
            return $this->cssClasses['warning'];
        }
        if ($this->items[$id]->countInfo()) {
            return $this->cssClasses['info'];
        }
        if ($this->items[$id]->countSuccess()) {
            return $this->cssClasses['success'];
        }
        return '';
    }

    /**
     * It returns the first message of error (if any)
     *
     * @param bool $includeWarning if true then it also includes warning but any error has priority.
     * @return string empty if there is none
     */
    public function firstErrorText($includeWarning=false) {
        if ($includeWarning) {
            if ($this->errorcount) {
                return $this->firstError;
            }
            return ($this->warningcount === 0) ? '' : $this->firstWarning;
        }
        return ($this->errorcount === 0) ? '' : $this->firstError;
    }

    /**
     * It returns the first message of error (if any), if not,
     * it returns the first message of warning (if any)
     *
     * @return string empty if there is none
     * @see \eftec\MessageList::firstErrorText
     */
    public function firstErrorOrWarning() {
       return $this->firstErrorText(true);
    }

    /**
     * It returns the first message of warning (if any)
     *
     * @return string empty if there is none
     */
    public function firstWarningText() {
        return ($this->warningcount === 0) ? '' : $this->firstWarning;
    }

    /**
     * It returns the first message of information (if any)
     *
     * @return string empty if there is none
     */
    public function firstInfoText() {
        return ($this->infocount === 0) ? '' : $this->firstInfo;
    }

    /**
     * It returns the first message of success (if any)
     *
     * @return string empty if there is none
     */
    public function firstSuccessText() {
        return ($this->successcount === 0) ? '' : $this->firstSuccess;
    }

    /**
     * It returns an array with all messages of error of all containers.
     *
     * @param bool $includeWarning if true then it also include warnings.
     * @return string[] empty if there is none
     */
    public function allErrorArray($includeWarning=false) {
        if($includeWarning) {
            $r = array();
            foreach ($this->items as $v) {
                $r = array_merge($r, $v->allError());
                $r = array_merge($r, $v->allWarning());
            }
            return $r;
        } else {
            $r = array();
            foreach ($this->items as $v) {
                $r = array_merge($r, $v->allError());
            }
        }
        return $r;
    }

    /**
     * It returns an array with all messages of info of all containers.
     *
     * @return string[] empty if there is none
     */
    public function allInfoArray() {
        $r = array();
        foreach ($this->items as $v) {
            $r = array_merge($r, $v->allInfo());
        }
        return $r;
    }

    /**
     * It returns an array with all messages of warning of all containers.
     *
     * @return string[] empty if there is none
     */
    public function allWarningArray() {
        $r = array();
        foreach ($this->items as $v) {
            $r = array_merge($r, $v->allWarning());
        }
        return $r;
    }

    /**
     * It returns an array with all messages of success of all containers.
     *
     * @return string[] empty if there is none
     */
    public function AllSuccessArray() {
        $r = array();
        foreach ($this->items as $v) {
            $r = array_merge($r, $v->allSuccess());
        }
        return $r;
    }

    /**
     * It returns an array with all messages of any type of all containers
     *
     * @return string[] empty if there is none
     */
    public function allArray() {
        $r = array();
        foreach ($this->items as $v) {
            $r = array_merge($r, $v->allError());
            $r = array_merge($r, $v->allWarning());
            $r = array_merge($r, $v->allInfo());
            $r = array_merge($r, $v->allSuccess());
        }
        return $r;
    }

    /**
     * It returns an array with all messages of errors and warnings of all containers.
     *
     * @return string[] empty if there is none
     * @see \eftec\MessageList::allErrorArray
     */
    public function allErrorOrWarningArray() {
        return $this->allErrorArray(true);
    }

    /**
     * It returns true if there is an error (or error and warning).
     *
     * @param bool $includeWarning If true then it also returns if there is a warning
     * @return bool
     */
    public function hasError($includeWarning=false) {
        $tmp=$includeWarning
            ? $this->errorcount
            : $this->errorOrWarning;
        return $tmp !==0;
    }
}