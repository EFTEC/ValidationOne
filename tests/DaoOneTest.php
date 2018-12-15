<?php
namespace eftec\tests;
use eftec\DaoOne;
use Exception;
use PHPUnit\Framework\TestCase;


class DaoOneTest extends TestCase
{

    public function test_db()
    {
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

}