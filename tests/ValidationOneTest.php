<?php

namespace eftec\tests;

use DateTime;
use eftec\MessageContainer;
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

        self::assertEquals(12345, $r, 'it must be equals to 12345');
        //var_dump(getVal()->messageList->allErrorArray());
        self::assertCount(2, getVal()->messageList->allErrorArray(), 'it must be 2 errors');
    }

    public function testMessages()
    {
        $ml = MessageContainer::instance();
        $ml->resetAll();
        $ml->addItem('c1', 'message error c1-1', 'error');
        $ml->addItem('c1', 'message error c1-2', 'error');

        $ml->addItem('c2', 'message error c2-1', 'error');
        $ml->addItem('c2', 'message error c2-2', 'error');

        self::assertEquals(['message error c1-1', 'message error c1-2'], $ml->get('c1')->allErrorOrWarning());
        self::assertEquals(['message error c1-1', 'message error c1-2'], $ml->get('c1')->allErrorOrWarning());

        self::assertEquals([
            0 => 'message error c1-1',
            1 => 'message error c1-2',
            2 => 'message error c2-1',
            3 => 'message error c2-2'
        ], $ml->allErrorOrWarningArray());
    }
    public function testArray() {
        getVal()->resetValidation(true);
        getVal()->condition('gt','10')->isArray()->set([1,2,3],'id');
        self::assertEquals([], getVal()->getMessageId('id')->all());
    }

    public function test6()
    {
        getVal()->configChain(false, false);
        getVal()->resetValidation(true);
        getVal()->useForm(null);

        getVal()->notempty('this value must not be empty')->set('', 'id');
        self::assertEquals(1, getVal()->getMessageId('id')->countError());
        self::assertEquals('this value must not be empty', getVal()->messageList->firstErrorText());
        getVal()->configChain(false, false);
    }

    public function test4()
    {
        getVal()->messageList->resetAll();
        $r = getVal()->def("???")->type('string')->condition('eq', null, ['foo', 'bar'])->set("hello");

        self::assertEquals('hello', $r);
        //var_dump(getVal()->messageList->allErrorArray());
        self::assertEquals('setfield is not equals than ["foo","bar"]', getVal()->messageList->firstErrorText());

        getVal()->messageList->resetAll();
        $r = getVal()->def("???")->type('string')->condition('ne', null, ['foo', 'bar'])->set("foo");

        self::assertEquals('foo', $r);
        //var_dump(getVal()->messageList->allErrorArray());
        self::assertEquals('setfield is in ["foo","bar"]', getVal()->messageList->firstErrorText());
    }
    public function testPostGetRequest() {
        getVal()->messageList->resetAll();
        $_POST['frm_FIELDREQ']="hello";
        $_GET['frm_FIELDREQ']="hello-get";
        $_FILES['frm_FIELDREQF']['tmp_name']='name.jpg';
        $_FILES['frm_FIELDREQF']['name']='name.jpg';

        $r=getVal()->type('string')->exist()->post('FIELDREQ');
        self::assertEquals("hello",$r);
        $r=getVal()->type('string')->exist()->get('FIELDREQ');
        self::assertEquals("hello-get",$r);
        $r=getVal()->type('string')->exist()->request('FIELDREQ');
        self::assertEquals("hello",$r);
        $r=getVal()->type('string')->exist()->getFile('FIELDREQF');
        self::assertEquals(['name.jpg','name.jpg'],$r);
    }
    public function testOther() {
        // VALUE IS MISSING -> exist -> GENERATE AN ERROR -> SET DEFAULT
        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ'], $_GET['frm_FIELDREQ'], $_FILES['frm_FIELDREQF']);
        $_POST['frm_FIELDREQ']='abc';
        $r=getVal()->type('integer')->def('noexist')->exist(true)->get('FIELDREQ');
        self::assertEquals(false,getVal()->getHasMessage());
    }
    public function testPipeline() {
        // VALUE IS MISSING -> exist -> GENERATE AN ERROR -> SET DEFAULT
        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ'], $_GET['frm_FIELDREQ'], $_FILES['frm_FIELDREQF']);

        $r=getVal()->type('string')->def('noexist')->exist(true)->post('FIELDREQ');
        self::assertEquals(1,getVal()->messageList->errorCount);
        self::assertEquals("FIELDREQ does not exist",getVal()->getMessage());
        self::assertEquals('noexist',$r);

        // VALUE IS MISSING -> NOEXIST -> GENERATE AN ERROR -> SET DEFAULT
        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ'], $_GET['frm_FIELDREQ'], $_FILES['frm_FIELDREQF']);

        $r=getVal()->type('string')->def('noexist')->exist(false)->post('FIELDREQ');
        self::assertEquals(0,getVal()->messageList->errorCount);
        self::assertEquals("",getVal()->getMessage());
        self::assertEquals('noexist',$r);

        // VALUE IS PRESENT -> CORRECT -> RETURN VALUE
        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ'], $_GET['frm_FIELDREQ'], $_FILES['frm_FIELDREQF']);
        $_POST['frm_FIELDREQ']='exist';
        $r=getVal()->type('string')->def('noexist')->exist(false)->post('FIELDREQ');
        self::assertEquals(0,getVal()->messageList->errorCount);
        self::assertEquals("",getVal()->getMessage());
        self::assertEquals('exist',$r);

        // VALUE IS PRESENT -> INCORRECT -> RETURN ORIGIN
        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ'], $_GET['frm_FIELDREQ'], $_FILES['frm_FIELDREQF']);
        $_POST['frm_FIELDREQ']='exist';
        $r=getVal()->type('integer')->def('noexist',false)->ifFailThenOrigin()->post('FIELDREQ');
        self::assertEquals(1,getVal()->messageList->errorCount);
        self::assertEquals("FIELDREQ is not numeric",getVal()->getMessage());
        self::assertEquals('exist',$r);

        // VALUE IS PRESENT -> INCORRECT -> RETURN DEFAULT
        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ'], $_GET['frm_FIELDREQ'], $_FILES['frm_FIELDREQF']);
        $_POST['frm_FIELDREQ']='exist';
        $r=getVal()->type('integer')->def('noexist')->ifFailThenDefault()->post('FIELDREQ');
        self::assertEquals(1,getVal()->messageList->errorCount);
        self::assertEquals("FIELDREQ is not numeric",getVal()->getMessage());
        self::assertEquals('noexist',$r);

        // VALUE IS PRESENT -> INCORRECT -> RETURN NULL
        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ'], $_GET['frm_FIELDREQ'], $_FILES['frm_FIELDREQF']);
        $_POST['frm_FIELDREQ']='exist';
        $r=getVal()->type('integer')->post('FIELDREQ');
        self::assertEquals(1,getVal()->messageList->errorCount);
        self::assertEquals("FIELDREQ is not numeric",getVal()->getMessage());
        self::assertEquals(null,$r);

        // reset the test
        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ'], $_GET['frm_FIELDREQ'], $_FILES['frm_FIELDREQF']);

        $r=getVal()->type('string')->def('1')->exist()->post('FIELDREQ');
        self::assertEquals("FIELDREQ does not exist",getVal()->getMessage());
        self::assertEquals('1',$r);

    }
    public function testPostGetRequestNoFound() {
        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ'], $_GET['frm_FIELDREQ'], $_FILES['frm_FIELDREQF']);

        $r=getVal()->type('string')->exist()->post('FIELDREQ');
        self::assertEquals(1,getVal()->messageList->errorCount);
        getVal()->messageList->resetAll();

        $r=getVal()->type('string')->exist()->get('FIELDREQ');
        self::assertEquals(1,getVal()->messageList->errorCount);
        getVal()->messageList->resetAll();

        $r=getVal()->type('string')->exist()->request('FIELDREQ');
        self::assertEquals(1,getVal()->messageList->errorCount);
        getVal()->messageList->resetAll();

        $r=getVal()->type('string')->exist()->getFile('FIELDREQF');
        self::assertEquals(1,getVal()->messageList->errorCount);
        getVal()->messageList->resetAll();

    }
    public function testMessageContainer() {
        getVal()->messageList->resetAll();
        self::assertEquals(0,getVal()->messageList->errorCount);
        getVal()->messageList->addItem('containere','errorm','error');
        getVal()->messageList->addItem('containeri','infom','info');
        getVal()->messageList->addItem('container1','warningm','warning');
        getVal()->messageList->addItem('containers','successm','success');
        self::assertEquals(1,getVal()->messageList->errorCount);
        self::assertEquals(1,getVal()->messageList->warningCount);
        self::assertEquals(1,getVal()->messageList->infoCount);
        self::assertEquals(1,getVal()->messageList->successCount);
        self::assertEquals('warningm',getVal()->messageList->items['container1']->first());
        self::assertEquals('warningm',getVal()->messageList->items['container1']->firstErrorOrWarning());
        self::assertEquals(null,getVal()->messageList->items['container1']->firstError());
        self::assertEquals('errorm',getVal()->messageList->firstErrorOrWarning());
        self::assertEquals('errorm',getVal()->messageList->firstErrorText());
        self::assertEquals('infom',getVal()->messageList->firstInfoText());
        self::assertEquals('successm',getVal()->messageList->firstSuccessText());
        self::assertEquals('warningm',getVal()->messageList->firstWarningText());
        self::assertEquals('warning',getVal()->messageList->cssClass('container1'));

    }
    public function testMisc() {
        self::assertEquals('jpg',getVal()->getFileExtension('//folder/file.jpg'));
        self::assertEquals('image/jpeg',getVal()->getFileExtension('//folder/file.jpg',true));
    }


    public function testThrow() {
        getVal()->messageList->resetAll();
        try {
            getVal()->type('integer')->throwOnError()->set('hello', 'field1');
            $this->fail('this value means the throw failed');
        } catch(\Exception $ex) {
            $this->assertEquals('field1 is not numeric',$ex->getMessage());
        }
        getVal()->messageList->resetAll();
        try {
            getVal()->type('string')->throwOnError()
                ->condition('eq','%field is not equals to %comp','world')->set('hello', 'field1');
            $this->fail('this value means the throw failed');
        } catch(\Exception $ex) {
            $this->assertEquals('field1 is not equals to world',$ex->getMessage());
        }
        getVal()->messageList->resetAll();
        try {
            getVal()->type('string')->throwOnError()
                ->exist()
                ->get('XXXXYYY');
            $this->fail('this value means the throw failed');
        } catch(\Exception $ex) {
            $this->assertEquals('XXXXYYY does not exist',$ex->getMessage());
        }
    }
    public function testTrimConversion() {
        getVal()->messageList->resetAll();
        $r=getVal()->type('string')->set('  hello  ');
        self::assertEquals('  hello  ',$r);
        $r=getVal()->type('string')->trim()->set('  hello  ');
        self::assertEquals('hello',$r);
        $r=getVal()->type('string')->trim('ltrim')->set('  hello  ');
        self::assertEquals('hello  ',$r);
        $r=getVal()->type('string')->trim('rtrim')->set('  hello  ');
        self::assertEquals('  hello',$r);
        $r=getVal()->type('string')->conversion('sanitizer',FILTER_SANITIZE_EMAIL)->set('email//@email.dom');
        self::assertEquals('email@email.dom',$r);
        $r=getVal()->type('string')->conversion('replace','hello','world')->set('hello hello');
        self::assertEquals('world world',$r);
        $r=getVal()->type('string')->conversion('htmlencode')->set('<b>dog</b>');
        self::assertEquals('&lt;b&gt;dog&lt;/b&gt;',$r);
        $r=getVal()->type('string')->conversion('htmldecode')->set('&lt;b&gt;dog&lt;/b&gt;');
        self::assertEquals('<b>dog</b>',$r);

        getVal()->messageList->resetAll();
        getVal()->alwaysTrim();
        $r=getVal()->type('string')->set('  hello  ');
        self::assertEquals('hello',$r);
        $r=getVal()->type('string')->set('  hello  ');
        self::assertEquals('hello',$r);
        getVal()->alwaysTrim(false);
        $r=getVal()->type('string')->set('  hello  ');
        self::assertEquals('  hello  ',$r);

    }
    public function testExistMissingValid()
    {
        // missing valid
        getVal()->messageList->resetAll();

        unset($_POST['frm_FIELDREQ']);
        $r = getVal()->type('string')->isMissingValid()->exist()->required()->post('FIELDREQ');
        self::assertEquals(0,getVal()->messageList->errorCount);
        self::assertEquals(null, $r);
        // null valid
        getVal()->messageList->resetAll();
        $_POST['frm_FIELDREQ']=null;
        $r = getVal()->type('string')->isNullValid()->condition('notnull')->required()->post('FIELDREQ');
        self::assertEquals(0,getVal()->messageList->errorCount);
        self::assertEquals(null, $r);
        // empty valid
        getVal()->messageList->resetAll();
        $_POST['frm_FIELDREQ']='';
        $r = getVal()->type('string')->isEmptyValid()->notempty()->required()->post('FIELDREQ');
        self::assertEquals(0,getVal()->messageList->errorCount);
        self::assertEquals('', $r);

        // null or empty valid
        getVal()->messageList->resetAll();
        $_POST['frm_FIELDREQ']='';
        $r = getVal()->type('string')->isNullOrEmptyValid()->notempty()->required()->post('FIELDREQ');
        self::assertEquals(0,getVal()->messageList->errorCount);
        self::assertEquals('', $r);

        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ']);
        $r = getVal()->type('string')
            //->isNullValid()
            ->condition('notnull')
            ->required()
            ->post('FIELDREQ');
        self::assertEquals(['FIELDREQ is null', 'FIELDREQ is required'],getVal()->getMessages());
        self::assertEquals(null, $r);

        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ']);
        $r = getVal()->type('string')
            //->isNullValid()
            ->condition('null')
            ->required()
            ->post('FIELDREQ');
        self::assertEquals(['FIELDREQ is required'],getVal()->getMessages());
        self::assertEquals(null, $r);

        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ']);
        $r = getVal()->type('string')
            //->isNullValid()
            ->condition('empty')
            ->required()
            ->post('FIELDREQ');
        self::assertEquals(['FIELDREQ is required'],getVal()->getMessages());
        self::assertEquals(null, $r);

        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ']);
        $r = getVal()->type('string')
            //->isNullValid()
            ->condition('notempty')
            ->required()
            ->post('FIELDREQ');
        self::assertEquals(['FIELDREQ is empty','FIELDREQ is required'],getVal()->getMessages());
        self::assertEquals(null, $r);

        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ']);
        $r = getVal()->type('string')
            //->isNullValid()
            ->condition('notexist')
            ->required(false)
            ->post('FIELDREQ');
        self::assertEquals([],getVal()->getMessages());
        self::assertEquals(null, $r);

        getVal()->messageList->resetAll();
        $_POST['frm_FIELDREQ']='123';
        $r = getVal()->type('string')
            //->isNullValid()
            ->condition('notexist')
            ->required(false)
            ->post('FIELDREQ');
        self::assertEquals(['FIELDREQ exists'],getVal()->getMessages());
        self::assertEquals('123', $r);
    }


    public function testExistRequired2()
    {
        getVal()->messageList->resetAll();
        $_POST['frm_FIELDREQ'] = "hello";
        $r = getVal()->type('string')->exist()->required()->post('FIELDREQ');
        self::assertEquals("hello", $r);

        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ']);
        $r = getVal()->type('string')->exist()->required()->post('FIELDREQ');
        self::assertEquals([
            0 => 'FIELDREQ does not exist',
            1 => 'FIELDREQ is required'],getVal()->messageList->allErrorArray());
        self::assertEquals(2,getVal()->messageList->errorCount);

        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ']);
        $r = getVal()->type('string')->condition('exist')->condition('req')->post('FIELDREQ');
        self::assertEquals([
            0 => 'FIELDREQ does not exist',
            1 => 'FIELDREQ is required'],getVal()->messageList->allErrorArray());
        self::assertEquals(2,getVal()->messageList->errorCount);
    }


    public function testExistRequired() {
        getVal()->messageList->resetAll();
        $_POST['frm_FIELDREQ']="hello";
        $r=getVal()->type('string')->exist()->post('FIELDREQ');
        self::assertEquals("hello",$r);

        getVal()->messageList->resetAll();
        $r=getVal()->type('string')->exist()->set(null);
        self::assertEquals(0,getVal()->messageList->errorCount);
        self::assertEquals(null,$r);

        getVal()->messageList->resetAll();
        $_POST['frm_FIELDREQ']="";
        $r=getVal()->type('string')->condition('req')->post('FIELDREQ');
        self::assertEquals(1,getVal()->messageList->errorCount);
        self::assertEquals(null,$r);

        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ']);
        $r=getVal()->type('string')->exist()->post('FIELDREQ');
        self::assertEquals(1,getVal()->messageList->errorCount);
        self::assertEquals(null,$r);

        getVal()->messageList->resetAll();
        $_POST['frm_FIELDREQ']="hello";
        $r=getVal()->type('string')->exist()->post('FIELDREQ');
        self::assertEquals(0,getVal()->messageList->errorCount);
        self::assertEquals("hello",$r);
    }

    public function testMultiple() {
        getVal()->messageList->resetAll();
        $_POST['frm_FIELDREQ']="hello";
        $r=getVal()->type('string')->exist()->post('FIELDREQ');
        self::assertEquals(0,getVal()->messageList->errorCount);
        self::assertEquals("hello",$r);

        getVal()->messageList->resetAll();
        unset($_POST['frm_FIELDREQ']);
        $r=getVal()->type('string')->exist()->post('FIELDREQ');
        self::assertEquals(1,getVal()->messageList->errorCount);
        self::assertEquals(null,$r);

        getVal()->messageList->resetAll();
        $_POST['frm_FIELDREQ']=null;
        $r=getVal()->type('string')->exist()->post('FIELDREQ');
        self::assertEquals(0,getVal()->messageList->errorCount);
        self::assertEquals(null,$r);

    }

    public function testMultipleCondition()
    {
        getVal()->messageList->resetAll();
        $r = getVal()->def("???")->type('integer')->condition('between', 'value must be between zero and 100', [0, 100])
            ->condition('eq', 'value must be equals to 5', 5)->set('123', 'id1');
        self::assertEquals(2, getVal()->messageList->errorOrWarningCount);
        self::assertEquals([
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
        self::assertEquals([
            0 => 'this value (name) is required',
            1 => 'The minimum lenght is 3'
        ], getVal()->messageList->allErrorOrWarningArray());
        getVal()->messageList->resetAll();
    }

    public function test5()
    {
        getVal()->messageList->resetAll();
        $r = getVal()->def("???")->type('file')->condition('exist')->set(__FILE__, 'filename');

        self::assertEquals([__FILE__, __FILE__], $r);
        //var_dump(getVal()->messageList->allErrorOrWarningArray());
        self::assertEquals(0, getVal()->messageList->errorOrWarningCount); // the file exists.

        getVal()->messageList->resetAll();
        $r = getVal()->def("???")->type('file')->condition('exist')->set(__FILE__ . '.bak');

        self::assertEquals([__FILE__ . '.bak', __FILE__ . '.bak'], $r);
        self::assertEquals(1, getVal()->messageList->errorOrWarningCount); // the file does not exist


    }

    public function testFailCondition()
    {
        getVal()->messageList->resetAll();
        $r = getVal()->def("default")->type('string')->condition('contain', 'it must contains the text hello', 'hello')
            ->condition('req')->condition('maxlen', "it's too big", 10, 'warning')
            ->condition('eq', '%field %value is not equal to %comp', 'abc')->set('abcdefghijklmnopqrst12345');

        self::assertEquals('abcdefghijklmnopqrst12345', $r, 'it must be equals to abcdefghijklmnopqrst12345');
        self::assertEquals('it must contains the text hello', getVal()->messageList->allErrorArray()[0]);
        self::assertEquals('it must contains the text hello', getVal()->messageList->firstErrorText());
        self::assertEquals(2, getVal()->errorCount(true), 'it must be 2 errors');
        self::assertEquals(true, getVal()->hasError(true), 'it must has an error');
        self::assertEquals('setfield abcdefghijklmnopqrst12345 is not equal to abc',
            getVal()->messageList->allErrorArray()[1]);

        self::assertEquals(2, (getVal()->messageList->errorCount), 'it must be 2 errors');
        self::assertCount(1, getVal()->messageList->allWarningArray(), 'it must be 1 warning');
        self::assertEquals(1, getVal()->messageList->warningCount, 'it must be 1 warning');
        self::assertEquals(3, getVal()->messageList->errorOrWarningCount, 'it must be 3 errors or warnings');
    }

    public function test7()
    {
        $r = getVal()->type('string')->isNullValid(true)->set(null, 'field');
        self::assertEquals(null, $r);
    }

    public function testOthers()
    {
        $r = getVal()->ifMissingThenSet('hi world')->get('nope');
        self::assertEquals('hi world', $r);
        $r = getVal()->type('datestring')->setDateFormatEnglish()->set('02/01/2020');
        self::assertEquals('2020-02-01', $r);
        $r = getVal()->type('datestring')->defNatural()->get('nope');
        $now = new DateTime();

        self::assertEquals($now->format('Y-m-d'), $r);

        getVal()->setDateFormatDefault(); // to default configuration
    }
    public function testExt() {
        getVal()->messageList->resetAll();
        $this->assertEquals('image/jpeg',getVal()->getFileExtension('aaa.jpg',true));
        $this->assertEquals('application/javascript',getVal()->getFileExtension('aaa.js',true));
        $this->assertEquals('image/png',getVal()->getFileExtension('aaa.png',true));
        getVal()->addMessage('tmp','error','error');
        $this->assertEquals(1,getVal()->errorCount());
        $this->assertEquals('hello',getVal()->initial('hello')->get('XXXXX'));
    }

    public function testDateEmptyOrMissing()
    {
        getVal()->messageList->resetAll();
        $r = getVal()->type('datetimestring')->def('')->ifFailThenDefault()->set(null);
        self::assertEquals('', $r);
        $r = getVal()->type('datetimestring')->get('missingfield');
        self::assertEquals('', $r);
        $r = getVal()->type('datetimestring')->def(null)->ifFailThenDefault()->set(null);
        self::assertEquals(null, $r);
    }

    public function testDate()
    {
        getVal()->messageList->resetAll();
        $r = getVal()->type('date')->condition('req')->set('31/12/2010');

        self::assertEquals(DateTime::createFromFormat('d/m/Y h:i:s', '31/12/2010 00:00:00'), $r,
            'it must be equals to 31/12/2010');
        self::assertCount(0, getVal()->messageList->allErrorOrWarningArray(),
            'it must have 0 errors or warnings');
        getVal()->messageList->resetAll();

        $r = getVal()->type('date')->condition('req')
            ->condition('lt', 'greater than', DateTime::createFromFormat('d/m/Y h:i:s', '31/12/2009 00:00:00'))
            ->set('31/12/2010');
        self::assertEquals(DateTime::createFromFormat('d/m/Y h:i:s', '31/12/2010 00:00:00'), $r,
            'it must be equals to 31/12/2010');
        self::assertCount(0, getVal()->messageList->allErrorOrWarningArray(),
            'it must have 0 errors or warnings');

        getVal()->messageList->resetAll();
        $r = getVal()->type('datetime')->condition('req')->set('31/12/2010 11:12:13');
        self::assertEquals(DateTime::createFromFormat('d/m/Y h:i:s', '31/12/2010 11:12:13'), $r,
            'it must be equals to 31/12/2010 11:12:13');
        self::assertCount(0, getVal()->messageList->allErrorOrWarningArray(),
            'it must have 0 errors or warnings');
    }

    public function testDateString()
    {
        // getVal()->dateShort= 'd/m/Y'
        //getVal()->dateOutputString='Y-m-d'
        getVal()->messageList->resetAll();
        $r = getVal()->type('datestring')->condition('req')->set('31/12/2010');

        self::assertEquals('2010-12-31', $r, 'it must be equals to 2010-12-31');
        self::assertCount(0, getVal()->messageList->allErrorOrWarningArray(),
            'it must have 0 errors or warnings');
        getVal()->messageList->resetAll();

        $r = getVal()->type('datestring')->condition('req')
            ->condition('lt', 'greater than', DateTime::createFromFormat('d/m/Y h:i:s', '31/12/2009 00:00:00'))
            ->set('31/12/2010');
        self::assertEquals('2010-12-31', $r, 'it must be equals to 2010-12-31');
        self::assertCount(0, getVal()->messageList->allErrorOrWarningArray(),
            'it must have 0 errors or warnings');

        getVal()->messageList->resetAll();
        $r = getVal()->type('datetimestring')->condition('req')->set('31/12/2010 11:12:13');
        //->setTimezone(new DateTimeZone("UTC"));
        self::assertEquals('2010-12-31T11:12:13Z', $r, 'it must be equals to 31/12/2010 11:12:13');
        self::assertCount(0, getVal()->messageList->allErrorOrWarningArray(),
            'it must have 0 errors or warnings');

        // ***********************
        $_POST['frm_date'] = '31/12/2010 11:12:13';
        getVal()->messageList->resetAll();
        $r = getVal()->type('datetimestring')->condition('req')->post('date');
        //->setTimezone(new DateTimeZone("UTC"));
        self::assertEquals('2010-12-31T11:12:13Z', $r, 'it must be equals to 31/12/2010 11:12:13');
        self::assertCount(0, getVal()->messageList->allErrorOrWarningArray(),
            'it must have 0 errors or warnings');

        // *********************** testing errors, required and without a default value
        $_POST['frm_date'] = '31/12/2010a';
        getVal()->messageList->resetAll();
        $r = getVal()->type('datetimestring')->condition('req')->post('date');
        //->setTimezone(new DateTimeZone("UTC"));
        self::assertEquals(null, $r, 'it must be equals to null');
        self::assertCount(1, getVal()->messageList->allErrorOrWarningArray(),
            'it must have 0 errors or warnings');

        // *********************** testing errors, not required and with a default value
        $_POST['frm_date'] = '31/12/2010a';
        getVal()->messageList->resetAll();
        $r = getVal()->type('datetimestring')->def('31/12/2010 11:22:11')->exist(false)->ifFailThenDefault()
            ->post('date');
        //->setTimezone(new DateTimeZone("UTC"));
        self::assertEquals('2010-12-31T11:22:11Z', $r, 'it must be equals to 2010-12-31T11:22:11Z');
        self::assertCount(1, getVal()->messageList->allErrorOrWarningArray(),
            'it must have 0 errors or warnings');
    }
}
