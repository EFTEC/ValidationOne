<?php
namespace eftec\tests;
use DateTime;
use eftec\DaoOne;
use Exception;
use PHPUnit\Framework\TestCase;


class ValidationOneTest extends TestCase
{

    public function test_db()
    {
        getVal()->messageList->resetAll();
	    $r=getVal()->def(-1)
		    ->type('integer')
		    ->condition('fn.static.Example.customval','la funcion no funciona',20) // this calls a custom function
		    ->condition('req')
		    ->condition('lt',"es muy grande",2000,'warning')
		    ->condition('eq','%field %value is not equal to %comp ',50)->set(12345);
	    
	    $this->assertEquals(12345,$r,'it must be equals to 12345');
	    //var_dump(getVal()->messageList->allErrorArray());
	    $this->assertEquals(2,count(getVal()->messageList->allErrorArray()),'it must be 2 errors');
    }

    public function testFailCondition()
    {
        getVal()->messageList->resetAll();
        $r=getVal()->def("default")
            ->type('string')
            ->condition('contain','it must contains the text hello','hello') // this calls a custom function
            ->condition('req')
            ->condition('maxlen',"it's too big",10,'warning')
            ->condition('eq','%field %value is not equal to %comp','abc')
            ->set('abcdefghijklmnopqrst12345');

        $this->assertEquals('abcdefghijklmnopqrst12345',$r,'it must be equals to 12345');
        $this->assertEquals('it must contains the text hello',getVal()->messageList->allErrorArray()[0]);
        $this->assertEquals('setfield abcdefghijklmnopqrst12345 is not equal to abc',getVal()->messageList->allErrorArray()[1]);
        $this->assertEquals(2,count(getVal()->messageList->allErrorArray()),'it must be 2 errors');
        $this->assertEquals(1,count(getVal()->messageList->allWarningArray()),'it must be 1 warning');
    }
    public function testDate()
    {
        getVal()->messageList->resetAll();
        $r=getVal()
            ->type('date')
            ->condition('req')
            ->set('31/12/2010');

        $this->assertEquals(DateTime::createFromFormat('d/m/Y h:i:s','31/12/2010 00:00:00')
            ,$r,'it must be equals to 31/12/2010');
        $this->assertEquals(0,count(getVal()->messageList->allErrorOrWarningArray()),'it must have 0 errors or warnings');
        getVal()->messageList->resetAll();
        
        $r=getVal()
            ->type('date')
            ->condition('req')
            ->condition('lt','greater than',DateTime::createFromFormat('d/m/Y h:i:s','31/12/2009 00:00:00'))
            ->set('31/12/2010');
        $this->assertEquals(DateTime::createFromFormat('d/m/Y h:i:s','31/12/2010 00:00:00')
            ,$r,'it must be equals to 31/12/2010');
        $this->assertEquals(0,count(getVal()->messageList->allErrorOrWarningArray()),'it must have 0 errors or warnings');        
        
        getVal()->messageList->resetAll();
        $r=getVal()
            ->type('datetime')
            ->condition('req')
            ->set('31/12/2010 11:12:13');
        $this->assertEquals(DateTime::createFromFormat('d/m/Y h:i:s','31/12/2010 11:12:13')
            ,$r,'it must be equals to 31/12/2010 11:12:13');
        $this->assertEquals(0,count(getVal()->messageList->allErrorOrWarningArray()),'it must have 0 errors or warnings');        
    }

    public function testDateString()
    {
        // getVal()->dateShort= 'd/m/Y'
        //getVal()->dateOutputString='Y-m-d'
        getVal()->messageList->resetAll();
        $r=getVal()
            ->type('datestring')
            ->condition('req')
            ->set('31/12/2010');
        
        $this->assertEquals('2010-12-31'
            ,$r,'it must be equals to 2010-12-31');
        $this->assertEquals(0,count(getVal()->messageList->allErrorOrWarningArray()),'it must have 0 errors or warnings');
        getVal()->messageList->resetAll();

        $r=getVal()
            ->type('datestring')
            ->condition('req')
            ->condition('lt','greater than',DateTime::createFromFormat('d/m/Y h:i:s','31/12/2009 00:00:00'))
            ->set('31/12/2010');
        $this->assertEquals('2010-12-31'
            ,$r,'it must be equals to 2010-12-31');
        $this->assertEquals(0,count(getVal()->messageList->allErrorOrWarningArray()),'it must have 0 errors or warnings');

        getVal()->messageList->resetAll();
        $r=getVal()
            ->type('datetimestring')
            ->condition('req')
            ->set('31/12/2010 11:12:13');
        //->setTimezone(new DateTimeZone("UTC"));
        $this->assertEquals('2010-12-31T11:12:13Z'
            ,$r,'it must be equals to 31/12/2010 11:12:13');
        $this->assertEquals(0,count(getVal()->messageList->allErrorOrWarningArray()),'it must have 0 errors or warnings');

        // ***********************
        $_POST['frm_date']='31/12/2010 11:12:13';
        getVal()->messageList->resetAll();
        $r=getVal()
            ->type('datetimestring')
            ->condition('req')
            ->post('date');
        //->setTimezone(new DateTimeZone("UTC"));
        $this->assertEquals('2010-12-31T11:12:13Z'
            ,$r,'it must be equals to 31/12/2010 11:12:13');
        $this->assertEquals(0,count(getVal()->messageList->allErrorOrWarningArray()),'it must have 0 errors or warnings');

        // *********************** testing errors, required and without a default value
        $_POST['frm_date']='31/12/2010a';
        getVal()->messageList->resetAll();
        $r=getVal()
            ->type('datetimestring')
            ->condition('req')
            ->post('date');
        //->setTimezone(new DateTimeZone("UTC"));
        $this->assertEquals(null
            ,$r,'it must be equals to null');
        $this->assertEquals(1,count(getVal()->messageList->allErrorOrWarningArray()),'it must have 0 errors or warnings');

        // *********************** testing errors, not required and with a default value
        $_POST['frm_date']='31/12/2010a';
        getVal()->messageList->resetAll();
        $r=getVal()
            ->type('datetimestring')
            ->def('31/12/2010 11:22:11')
            ->required(false)
            ->ifFailThenDefault()
            ->post('date');
        //->setTimezone(new DateTimeZone("UTC"));
        $this->assertEquals('2010-12-31T11:22:11Z'
            ,$r,'it must be equals to 2010-12-31T11:22:11Z');
        $this->assertEquals(1,count(getVal()->messageList->allErrorOrWarningArray()),'it must have 0 errors or warnings');

    }
}