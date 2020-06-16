<?php

namespace eftec\tests;

use DateTime;
use eftec\MessageList;
use PHPUnit\Framework\TestCase;


class ValidationOneTest extends TestCase
{

    public function test_db()
    {
        getVal()->messageList->resetAll();
        $r = getVal()->def(-1)->type('integer')
            ->condition('fn.static.Example.customval', 'la funcion no funciona', 20) // this calls a custom function
        ->condition('req')->condition('lt', "es muy grande", 2000, 'warning')
            ->condition('eq', '%field %value is not equal to %comp ', 50)->set(12345);

        $this->assertEquals(12345, $r, 'it must be equals to 12345');
        //var_dump(getVal()->messageList->allErrorArray());
        $this->assertEquals(2, count(getVal()->messageList->allErrorArray()), 'it must be 2 errors');
    }

    public function testMessages()
    {
        $ml = new MessageList();
        $ml->addItem('c1', 'message error c1-1', 'error');
        $ml->addItem('c1', 'message error c1-2', 'error');

        $ml->addItem('c2', 'message error c2-1', 'error');
        $ml->addItem('c2', 'message error c2-2', 'error');

        $this->assertEquals(['message error c1-1', 'message error c1-2'], $ml->get('c1')->allErrorOrWarning());
        $this->assertEquals(['message error c1-1', 'message error c1-2'], $ml->get('c1')->allErrorOrWarning());

        $this->assertEquals([
            0 => 'message error c1-1',
            1 => 'message error c1-2',
            2 => 'message error c2-1',
            3 => 'message error c2-2'
        ], $ml->allErrorOrWarningArray());
    }

    public function test6()
    {
        getVal()->configChain(false, false);
        getVal()->resetValidation(true);


        getVal()->notempty('this value must not be empty')->set('', 'id');
        $this->assertEquals(1, getVal()->getMessageId('id')->countError());
        $this->assertEquals('this value must not be empty', getVal()->messageList->firstErrorText());
        getVal()->configChain(false, false);
    }

    public function test4()
    {
        getVal()->messageList->resetAll();
        $r = getVal()->def("???")->type('string')->condition('eq', null, ['foo', 'bar'])->set("hello");

        $this->assertEquals('hello', $r);
        //var_dump(getVal()->messageList->allErrorArray());
        $this->assertEquals('setfield is not equals than ["foo","bar"]', getVal()->messageList->firstErrorText());

        getVal()->messageList->resetAll();
        $r = getVal()->def("???")->type('string')->condition('ne', null, ['foo', 'bar'])->set("foo");

        $this->assertEquals('foo', $r);
        //var_dump(getVal()->messageList->allErrorArray());
        $this->assertEquals('setfield is in ["foo","bar"]', getVal()->messageList->firstErrorText());
    }

    public function testMultipleCondition()
    {
        getVal()->messageList->resetAll();
        $r = getVal()->def("???")->type('integer')->condition('between', 'value must be between zero and 100', [0, 100])
            ->condition('eq', 'value must be equals to 5', 5)->set('123', 'id1');
        $this->assertEquals(2, getVal()->messageList->errorOrWarning);
        $this->assertEquals([
            0 => 'value must be between zero and 100',
            1 => 'value must be equals to 5'
        ], getVal()->messageList->allErrorOrWarningArray());
        getVal()->messageList->resetAll();
   
        
        getVal()->def("")// what if the value is not read?, we should show something (or null)
        ->ifFailThenDefault(false)// if fails then we show the same value however it triggers an error
        ->type("varchar")// it is required to ind
        ->condition("req", "this value (%field) is required")
            ->condition("minlen", "The minimum lenght is 3", 3)
            ->condition("maxlen", "The maximum lenght is 100", 100)
            ->set('','name');
        $this->assertEquals([
            0 => 'this value (name) is required',
            1 => 'The minimum lenght is 3'
        ], getVal()->messageList->allErrorOrWarningArray());
        getVal()->messageList->resetAll();
    }

    public function test5()
    {
        getVal()->messageList->resetAll();
        $r = getVal()->def("???")->type('file')->condition('exist')->set(__FILE__, 'filename');

        $this->assertEquals([__FILE__, __FILE__], $r);
        //var_dump(getVal()->messageList->allErrorOrWarningArray());
        $this->assertEquals(0, getVal()->messageList->errorOrWarning); // the file exists.

        getVal()->messageList->resetAll();
        $r = getVal()->def("???")->type('file')->condition('exist')->set(__FILE__ . '.bak');

        $this->assertEquals([__FILE__ . '.bak', __FILE__ . '.bak'], $r);
        $this->assertEquals(1, getVal()->messageList->errorOrWarning); // the file does not exist


    }

    public function testFailCondition()
    {
        getVal()->messageList->resetAll();
        $r = getVal()->def("default")->type('string')->condition('contain', 'it must contains the text hello', 'hello')
            ->condition('req')->condition('maxlen', "it's too big", 10, 'warning')
            ->condition('eq', '%field %value is not equal to %comp', 'abc')->set('abcdefghijklmnopqrst12345');

        $this->assertEquals('abcdefghijklmnopqrst12345', $r, 'it must be equals to abcdefghijklmnopqrst12345');
        $this->assertEquals('it must contains the text hello', getVal()->messageList->allErrorArray()[0]);
        $this->assertEquals('it must contains the text hello', getVal()->messageList->firstErrorText());
        $this->assertEquals(2, count(getVal()->messageList->allErrorArray()), 'it must be 2 errors');
        $this->assertEquals('setfield abcdefghijklmnopqrst12345 is not equal to abc',
            getVal()->messageList->allErrorArray()[1]);

        $this->assertEquals(2, (getVal()->messageList->errorcount), 'it must be 2 errors');
        $this->assertEquals(1, count(getVal()->messageList->allWarningArray()), 'it must be 1 warning');
        $this->assertEquals(1, getVal()->messageList->warningcount, 'it must be 1 warning');
        $this->assertEquals(3, getVal()->messageList->errorOrWarning, 'it must be 3 errors or warnings');
    }

    public function test7()
    {
        $r = getVal()->type('string')->isNullValid(true)->set(null, 'field');
        $this->assertEquals(null, $r);
    }

    public function testOthers()
    {
        $r = getVal()->ifMissingThenSet('hi world')->get('nope');
        $this->assertEquals('hi world', $r);
        $r = getVal()->type('datestring')->setDateFormatEnglish()->set('02/01/2020');
        $this->assertEquals('2020-02-01', $r);
        $r = getVal()->type('datestring')->defNatural()->get('nope');
        $now = new DateTime();

        $this->assertEquals($now->format('Y-m-d'), $r);

        getVal()->setDateFormatDefault(); // to default configuration

    }

    public function testDateEmptyOrMissing()
    {
        getVal()->messageList->resetAll();
        $r = getVal()->type('datetimestring')->def('')->ifFailThenDefault()->set(null);
        $this->assertEquals('', $r);
        $r = getVal()->type('datetimestring')->get('missingfield');
        $this->assertEquals('', $r);
        $r = getVal()->type('datetimestring')->def(null)->ifFailThenDefault()->set(null);
        $this->assertEquals(null, $r);
    }

    public function testDate()
    {
        getVal()->messageList->resetAll();
        $r = getVal()->type('date')->condition('req')->set('31/12/2010');

        $this->assertEquals(DateTime::createFromFormat('d/m/Y h:i:s', '31/12/2010 00:00:00'), $r,
            'it must be equals to 31/12/2010');
        $this->assertEquals(0, count(getVal()->messageList->allErrorOrWarningArray()),
            'it must have 0 errors or warnings');
        getVal()->messageList->resetAll();

        $r = getVal()->type('date')->condition('req')
            ->condition('lt', 'greater than', DateTime::createFromFormat('d/m/Y h:i:s', '31/12/2009 00:00:00'))
            ->set('31/12/2010');
        $this->assertEquals(DateTime::createFromFormat('d/m/Y h:i:s', '31/12/2010 00:00:00'), $r,
            'it must be equals to 31/12/2010');
        $this->assertEquals(0, count(getVal()->messageList->allErrorOrWarningArray()),
            'it must have 0 errors or warnings');

        getVal()->messageList->resetAll();
        $r = getVal()->type('datetime')->condition('req')->set('31/12/2010 11:12:13');
        $this->assertEquals(DateTime::createFromFormat('d/m/Y h:i:s', '31/12/2010 11:12:13'), $r,
            'it must be equals to 31/12/2010 11:12:13');
        $this->assertEquals(0, count(getVal()->messageList->allErrorOrWarningArray()),
            'it must have 0 errors or warnings');
    }

    public function testDateString()
    {
        // getVal()->dateShort= 'd/m/Y'
        //getVal()->dateOutputString='Y-m-d'
        getVal()->messageList->resetAll();
        $r = getVal()->type('datestring')->condition('req')->set('31/12/2010');

        $this->assertEquals('2010-12-31', $r, 'it must be equals to 2010-12-31');
        $this->assertEquals(0, count(getVal()->messageList->allErrorOrWarningArray()),
            'it must have 0 errors or warnings');
        getVal()->messageList->resetAll();

        $r = getVal()->type('datestring')->condition('req')
            ->condition('lt', 'greater than', DateTime::createFromFormat('d/m/Y h:i:s', '31/12/2009 00:00:00'))
            ->set('31/12/2010');
        $this->assertEquals('2010-12-31', $r, 'it must be equals to 2010-12-31');
        $this->assertEquals(0, count(getVal()->messageList->allErrorOrWarningArray()),
            'it must have 0 errors or warnings');

        getVal()->messageList->resetAll();
        $r = getVal()->type('datetimestring')->condition('req')->set('31/12/2010 11:12:13');
        //->setTimezone(new DateTimeZone("UTC"));
        $this->assertEquals('2010-12-31T11:12:13Z', $r, 'it must be equals to 31/12/2010 11:12:13');
        $this->assertEquals(0, count(getVal()->messageList->allErrorOrWarningArray()),
            'it must have 0 errors or warnings');

        // ***********************
        $_POST['frm_date'] = '31/12/2010 11:12:13';
        getVal()->messageList->resetAll();
        $r = getVal()->type('datetimestring')->condition('req')->post('date');
        //->setTimezone(new DateTimeZone("UTC"));
        $this->assertEquals('2010-12-31T11:12:13Z', $r, 'it must be equals to 31/12/2010 11:12:13');
        $this->assertEquals(0, count(getVal()->messageList->allErrorOrWarningArray()),
            'it must have 0 errors or warnings');

        // *********************** testing errors, required and without a default value
        $_POST['frm_date'] = '31/12/2010a';
        getVal()->messageList->resetAll();
        $r = getVal()->type('datetimestring')->condition('req')->post('date');
        //->setTimezone(new DateTimeZone("UTC"));
        $this->assertEquals(null, $r, 'it must be equals to null');
        $this->assertEquals(1, count(getVal()->messageList->allErrorOrWarningArray()),
            'it must have 0 errors or warnings');

        // *********************** testing errors, not required and with a default value
        $_POST['frm_date'] = '31/12/2010a';
        getVal()->messageList->resetAll();
        $r = getVal()->type('datetimestring')->def('31/12/2010 11:22:11')->required(false)->ifFailThenDefault()
            ->post('date');
        //->setTimezone(new DateTimeZone("UTC"));
        $this->assertEquals('2010-12-31T11:22:11Z', $r, 'it must be equals to 2010-12-31T11:22:11Z');
        $this->assertEquals(1, count(getVal()->messageList->allErrorOrWarningArray()),
            'it must have 0 errors or warnings');
    }
}