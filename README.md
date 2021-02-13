# ValidationOne
It's a PHP library for fetches and validate fields and store messages in different containers(including error, warning, info
, and success) depending on the conditions.

The ideology behind this library is simple: 5 classes, no dependencies and runs in PHP 5.6 and higher, so it could run
in practically any PHP project, including WordPress, Laravel, a custom PHP project, etc. 

[![Build Status](https://travis-ci.org/EFTEC/ValidationOne.svg?branch=master)](https://travis-ci.org/EFTEC/ValidationOne)
[![Packagist](https://img.shields.io/packagist/v/eftec/validationone.svg)](https://packagist.org/packages/eftec/ValidationOne)
[![Total Downloads](https://poser.pugx.org/eftec/validationone/downloads)](https://packagist.org/packages/eftec/ValidationOne)
[![Maintenance](https://img.shields.io/maintenance/yes/2021.svg)]()
[![composer](https://img.shields.io/badge/composer-%3E1.8-blue.svg)]()
[![php](https://img.shields.io/badge/php->5.6-green.svg)]()
[![php](https://img.shields.io/badge/php-7.x-green.svg)]()
[![php](https://img.shields.io/badge/php-8.x-green.svg)]()
[![CocoaPods](https://img.shields.io/badge/docs-70%25-yellow.svg)]()

## Examples

[Examples](https://github.com/EFTEC/ValidationOne/tree/master/examples)

[Tutorial Form and Table with PHP](https://github.com/EFTEC/BladeOne-tutorial1)

![diagram example](examples/docs/DiagramExample.jpg)  
It is an example of functionality. A typical example is more complex, even if it's only a few lines of code.


## ValidationOne

Let's say we want to validate a value an input value (get) called "id", we could do the next things:

* The default value is the text "**ERROR**"
* the type of the value is an **integer**, so it just returns an integer.   It also could be an integer, decimal, string, date,datestring and boolean
* we add a condition, the value must be equals (**eq**) to **10**. If it fails, then it returns a message (as **error**)
* we add another condition if the value must be equals (**eq**) to **30**. If it fails, then it returns an **info** (not an error)
* If the operation fails, then it returns the default value.
* And finally, we obtain the "**id**" from $_GET (parameter URL).

```php
use eftec\ValidationOne;
$val=new ValidationOne();

$r = $val->def('ERROR')
    ->type('integer')
    ->ifMissingThenDefault()
    ->condition("eq", "It's not equals to 10", 10)
    ->condition("eq", "It's not equals to 30 (info)", 30, 'info')
    ->ifFailThenDefault()
    ->get('id'); // <-- end of the chain
```

But where is the error?.  The messages are stored in **messagelist**

```php
var_dump($val->messageList->allArray()); // here we show all messages of any kind of type. 
var_dump($val->messageList->errorcount); // returns the number of errors.
```
However, we could also show a message by type (error, warning..) and only message by a specific identifier.

```php
var_dump($val->messageList->get('id')->allErrorOrWarning()); // All error or warning contained in the key "id".
```

Why the messages are store in some structure?. Is it not easy to simply return the error?

An answer is a form. Let's say we have a form with three fields. If one of them fails, then the error must be visible for each field separately.  Also, the whole form could have its own message.

### condition ($condition, $message = "", $conditionValue = null, $level = 'error', $key = null)

It adds a condition that it depends on the **type** of the input.

* @param string $condition

	<b>number</b>:req,eq,ne,gt,lt,gte,lte,between,null,notnull<br>
	<b>string</b>:req,eq,ne,minlen,maxlen,betweenlen,null,notnull,contain,notcontain
	,alpha,alphanum,text,regexp,email,url,domain<br>
	<b>date</b>:req,eq,ne,gt,lt,gte,lte,between<br>
	<b>datestring</b>:req,eq,ne,gt,lt,gte,lte,between<br>
	<b>boolean</b>:req,eq,ne,true,false<br>
	<b>file</b>:minsize,maxsize,req,image,doc,compression,architecture,ext<br>
	<b>function:</b><br>
	fn.static.Class.methodstatic<br>
	fn.global.function<br>
	fn.object.Class.method where object is a global $object<br>
	fn.class.Class.method<br>
	fn.class.\namespace\Class.method<br>
	
* @param string $message  

    Message could use the next variables '%field','%realfield','%value','%comp','%first','%second'  

    | Field      | Description                                                     | Example             |
    | ---------- | --------------------------------------------------------------- | ------------------- |
    | %field     | name of the field, it could be the **friendid** or the actual name  | CustomerID          |
    | %realfield | name of the field (not the  **friendid**)                           | Customer Identifier |
    | %value     | current value of the field                                      | John                |
    | %comp      | value to compare (if any)                                       |                     |
    | %first     | first value to compare (if the compare value  is an array)      |                     |
    | %second    | second value to compare (if the compare  value is an array)     |                     |
    | %key       | key used (for input array)                                      |                     |

* @param null $conditionValue

* @param string $level (error,warning,info,success). The level of the error. See **MessageList** for further information

* @param string $key If the key is not null then it is used to add more than one condition by key

* @return ValidationOne

> Note: if the value is null and isNullValid() is true, then the conditions are ignored.
> If the value is missing and isMissingValid() is true, then the conditions are ignored.
> If the value is empty ('') and isEmptyValid() is true, then the conditions are ignored.
> If the value is empty ('') or null and isNullOrEmptyValid() is true, then the conditions are ignored.
> isNullValid(),isMissingValid(),isNullOrEmptyValid() and isEmptyValid() are useful when we want to validate a value 
> only if it exists or if the value is set.

Example:

```php
$validation->def(null)
    ->type('integer')
    ->condition('eq','%field %value is not equal to %comp ',50)
    ->condition('eq','%field %value is not equal to %comp ',60)
    ->set('aaa','variable2');	
```

#### types

| type           | description                                                                                             |
|----------------|---------------------------------------------------------------------------------------------------------|
| integer        | (numeric) it allows a number without decimal                                                            |
| unixtime       | (numeric) it allows a number without decimal                                                            |
| boolean        | (boolean) it stores true or false. The input could vary (empty, zero or   null = false, otherwise true) |
| decimal        | (numeric) it allows a number with decimal                                                               |
| float          | (numeric) it allows a number with decimal                                                               |
| varchar        | (string) it allows any input                                                                            |
| string         | (string) it allows any input                                                                            |
| date           | (date) the input could be a DateTime or a string. The value is stored as   an object DateTime           |
| datetime       | (date) the input could be a DateTime or a string. The value is stored as   an object DateTime           |
| datestring     | (date) the input could be a DateTime or a string. The value is stored as a string                    |
| datetimestring | (date) the input could be a DateTime or a string. The value is stored as a string                    |
| file           | (file) the input is a string, and it's store as a string, but it adds special validation |



#### Input type x Conditions allowed.

| Input type                                   | Condition                                                          |   |
|----------------------------------------------|--------------------------------------------------------------------|---|
| number                                       | gt,lt,gte,lte,between                                          |   |
| string                                       | minlen,maxlen,betweenlen,contain<br>,notcontain,alpha,alphanum,text,regexp,email,url,domain |   |
| date                                         | gt,lt,gte,lte,between                                          |   |
| datestring                                   | gt,lt,gte,lte,between                                          |   |
| boolean                                      | true,false                                                     |   |
| file                                         | minsize,maxsize,req,image,doc,compression,architecture,ext         |   |
| *  (it applies for any type)                 | req,eq,ne,null,notnull,empty,notempty                                  |   |
| *                                            | function                                                           |   |
| *                                            | fn.static.Class.methodstatic                                       |   |
| *                                            | fn.global.function                                                 |   |
| *                                            | fn.object.Class.method where object is a global $object            |   |
| *                                            | fn.class.Class.method                                              |   |
| *                                            | fn.class.\namespace\Class.method                                   |   |

#### Types of Conditions.

| Condition                                               | Description                                            | Value Example          |
|---------------------------------------------------------|--------------------------------------------------------|------------------------|
| architecture                                            | The extension of the file must be an architecture file (dwg, etc.) |                        |
| between                                                 | The number must be between two values                  | [0,20]                 |
| betweenlen                                              | The length of the text must be between two values      | [0,20]                 |
| compression                                             | The extension of the file must be an compression file  |                        |
| contain                                                 | The text must contain a value                          | "text"                 |
| doc                                                     | The extension of the file must be a document file     |                        |
| eq (it could be an array or value)                      | The value must be equals to                            | "text",["text","text2"]                 |
| exist                                                   | The file or value must exist (it could be null/empty) |                        |
| missing/notexist                                        | The file or value must not exists or be null/empty     |                        |
| required (or req)                                       | The value must not be null or empty                    |                        |
| ext                                                     | The extension (file) must be in the list of extensions | ["ext1","ext2","ext3"] |
| false                                                   | The value must be false (===false)                     |                        |
| fn.class.\namespace\Class.method                        | The method of a class must returns true                |                        |
| fn.class.Class.method                                   | The method of a class must returns true                |                        |
| fn.global.function                                      | The global function must returns true                  |                        |
| fn.object.Class.method where object is a global $object | The method of a global object must returns true        |                        |
| fn.static.Class.methodstatic                            | The static method of a class must returns true         |                        |
| function                                                | The function must returns true                         |                        |
| gt                                                      | The value must be greater than                         | 123                    |
| gte                                                     | The value must be greater or equal than                | 123                    |
| image                                                   | The extension of the file must be an image file        |                        |
| lt                                                      | The value must be less than                            | 123                    |
| lte                                                     | The value must be less or equal than                   | 123                    |
| maxlen                                                  | The maximum length of a string                         | 123                    |
| maxsize                                                 | The maximum size of a file                             | 123                    |
| minlen                                                  | The minimum length of a string                         | 123                    |
| minsize                                                 | The minimum size of a file                             | 123                    |
| mime (the value to compare could be an string or array) | The mime type of a file                                | "application/msword" or ["application/msword","image/gif"]|
| mimetype                                                | The mime type (without subtype) of a file              | "application" or ["application,"image"]|
| ne (the value to compare could be an single value or array)   | The value must not be equals.                    | 123,[123,345],["aa","bb"]                    |
| notcontain                                              | The value must not contain a value                     | "text"                 |
| notnull                                                 | The value must not be null                             |                        |
| null                                                    | The value must be null                                 |                        |
| empty                                                   | The value must be empty (i.e. "",0,null)               |                        |
| notempty                                                | The value must not be empty (i.e. not equals to "",0,null)|                        |
| req                                                     | The value must be equal                                |                        |
| true                                                    | The value must be true (===true)                       |                        |


Examples:

```php
$validation->def(null)
    ->type('integer')
    ->condition('eq','%field %value is not equal to %comp ',50)
    ->condition('between','%field %value must be between 1 and 50 ',[1,50])
    ->condition('eq','%field %value is not equal to %comp ',60)
    ->condition('eq','%field %value is not equal to %comp ',[60,200]) // eq allows a single or array
    ->condition('fn.static.Example.customval','the function does not work')
    ->condition('req')
    ->condition('lt',"es muy grande",2000,'warning')
    ->condition('eq','%field %value is not equal to %comp',50)
    ->condition('fn.static.Example.fnstatic','the static function does not work')
    ->condition('fn.static.\somespace\Someclass.methodStatic',null)
    ->condition('fn.global.customval','The global function does not work')
    ->condition('fn.object.example.fnnostatic','the function object does not work')
    ->condition('fn.class.\somespace\Someclass.method','The function some class does not work')
    ->condition('fn.class.Example.fnnostatic','la funcion class no funciona');

// ->condition('fn.static.Example.customval','la funcion no funciona') 
function customval($value,$compareValue) {
    return true;
}
```
### Calling a custom function

Sometimes we need to use a custom condition. We could create a global variable, a static function, or even a method 
inside a class.  
Every method or function created must have two parameters (with any name): 
* **$value** The value to evaluate.
* **$compareValue** The value to compare (it could be optional)

For example, what if we need to evaluate if some id does not exist in the Database?

```php
$validation->condition('fn.global.idExist','The id already exist!')->get("id"); 

function idExist($id,$compare=null) {
   // select count(*) c from table where id=$id
   if($c>0) {
        return true; 
   } else {
        return false;
   }   
}

```


> Note: if we need to specify a namespace, then we could use the notation: \namespace\SomeClass

```php
$validation->condition('fn.global.customfn'); // global
$validation->condition('fn.static.SomeClass.staticfn'); // calling a static method inside the class SomeClass.
$validation->condition('fn.class.SomeClass.noStaticFn'); // method inside a class,it creates an instance of an object then it calls the method
$validation->condition('fn.object.myObject.noStaticFn'); // method inside a class, it uses an instance called $myObject

// global function
function customfn($value,$compareValue) {
    // returns true or false
}
// static function
$myObject=new SomeClass();
class SomeClass {
    public static function staticfn($value,$compareValue) {
        // returns true or false
    }
    public function noStaticFn($value,$compareValue) {
        // returns true or false
    }
}

```

## Getting the messages

**MessageList** is a list of containers of messages. It's aimed at convenience, so it features many methods to access the information in different ways. 

Messages are catalogued as follows

| id      | Description                                                          | Example                               |
|---------|----------------------------------------------------------------------|---------------------------------------|
| error   | The message is an error, and it must be solved. It is a show stopper. | Database is down                      |
| warning | The message is a warning that maybe it could be ignored.             | The registry was stored but with warnings |
| info    | The message is information | Log is stored                         |
| success | The message is a successful operation                                 | Order Accepted                        |                             |


Sometimes, both errors are warning are considered as equals. So the system allows reading an error or warning.

Error always has the priority, then warning, info and success.  If you want to read the first message, then it starts searching for errors.

You can obtain a message as an array of objects of the type **MessageItem**, as an array of string, or as a single string (first message)

```php
$validation=new ValidationOne();
$validation->condition('req')->get('idfield'); // container idfield
$validation->condition('req')->get('idfield2'); // container idfield2

if($validation->hasError()) {
    // Error: we do something here.
    echo "we found ".$this->errorCount()." errors in all containers";   
}

// using messageList
if($validation->messageList->hasError()) {
    // Error: we do something here.
    echo "we found ".$this->messageList->errorcount." errors in all containers";
    
}
```

### MessageList

#### Count of messages of all containers

| Name of the field | Type | Description                                            |
| ----------------- | ---- | ------------------------------------------------------ |
| errorcount        | int  | Get the number of errors in all containers             |
| warningcount      | int  | Get the number of warnings in all containers           |
| errorOrWarning    | int  | Get the number of errors or warnings in all containers |
| infocount         | int  | Get the number of information                          |
| successcount      | int  | Get the number of success.                             |

Example:

```
if ($validation->messageList->errorcount>0) {
    // some error
}
```



#### Obtain messages or text of all containers

| Name             | Type   | Description                                                  | Example of result                         |
| ---------------- | ------ | ------------------------------------------------------------ | ----------------------------------------- |
| firstErrorText   | method | Returns the first message of error  of all containers        | "Error in field"                          |
| firstWarningText | method | Returns the first message of warning  of all containers      | "warning in field"                        |
| firstInfoText    | method | Returns the first message of info of  all containers         | "info: log"                               |
| firstSuccessText | method | Returns the first message of success  of all containers      | "Operation successful"                    |
| allError         | method | Returns all errors of all containers (as an array of objects of the type **MessageItem**) | **MessageItem**[]                         |
| allWarning       | method | Returns all warning of all  containers (as an array of objects of the type **MessageItem**) | **MessageItem**[]                         |
| allInfo          | method | Returns all info of all containers (as an array of objects of the type **MessageItem**) | **MessageItem**[]                         |
| allSuccess       | method | Returns all success of all containers (as an array of objects of the type **MessageItem**) | **MessageItem**[]                         |
| allErrorArray    | method | Returns all errors of all containers (as an array of texts)  | ["Error in field1","Error in field2"]     |
| allWarningArray  | method | Returns all warning of all  containers (as an array of texts) | ["Warning in field1","Warning in field2"] |
| allInfoArray     | method | Returns all info of all containers (as an array of texts)    | ["Info in field1","Info in field2"]       |
| allSuccessArray  | method | Returns all success of all containers (as an array of texts) | ["Info in field1","Info in field2"]       |

```php
echo $validation->errorList->firstErrorText(); // returns first error if any
$array=$validation->errorList->allError();  // MessageItem[]
echo $array[0]->firstError(); 
$array=$validation->errorList->allErrorArray();  // string[]
echo $array[0]; 
```

#### Css for a specific container

cssClasses (field) is an associative array to use with the method cssClass()

cssClasses() is method that eturns a class based in the type of level of the container

```
$css=$this-messageList->cssClasses('container1');
```

#### Misc

| Name     | Type   | Description                                                  |
| -------- | ------ | ------------------------------------------------------------ |
| items    | field  | We get all containers (array of the type **MessageItem**). Each container could contain many messages. |
| resetAll | method | $array=$this-messageList->items; $this-messageList->items['id'];Delete all containers and reset counters |
| addItem  | method | It adds a new message to a container                         |
| allIds   | method | Get all the id of the containers                             |
| get      | method | Get a container (as an object of the type **MessageItem**). You can also use items[] |
| hasError | method | Returns true if there is an error.                           |

```php
echo $validation->errorList->resetAll(); // resets all containers
$validation->errorList->addItem('containerid','it is a message','error'); // we add an error in the container with #id containerid
$array=$validation->errorList->allIds(); // ['containerid']
var_dump($validation->get('containerid'));  // object MessageItem

$array=$this-messageList->items;
var_dump($this-messageList->items['containerid']); // object MessageItem

if($validation->errorList->hasError()) { // $validation->hasError() does the same
    echo "there is an error";
}
```



## Exist, Required , NotNull, NotEmpty

There are four different ways to deal with empty values in this library.  

* A value **exist** if the field or file exists, no matter the value or if it is null or empty.

```php
   $validation->exist()->set(null); // is valid.
   $validation->exist()->set(''); // is valid.
   $validation->exist()->get('field'); // is valid only if $_GET['field'] exist (even if it is null)
```

* A value is **required** if the field is not null or empty. Required is equals that null and empty at the same time

```php
   $validation->required()->set(null); // is not valid.
   $validation->required()->set(""); // is not valid.
   $validation->required()->set('hi'); // is valid.   
```

* A value is **not null** if the field is not null, but it could be empty ("").

```php
   $validation->notnull()->set(null); // is not valid.
   $validation->notnull()->set(""); // is valid.
   $validation->notnull()->set('hi'); // is valid.   
```


* A value is **not empty** if the field is not '' (string with length 0), but it could be null.

```php
   $validation->notempty()->set(null); // is valid.
   $validation->notempty()->set(""); // is not valid.
   $validation->notempty()->set('hi'); // is valid.   
```

Also, there are 4 ways to accept missing values, null or empty, bypassing any condition.

```php
   $validation->isNullValid()->condition(....)->set(null); // is valid no matter the condition.
   $validation->isNullorEmptyValid()->condition(....)->set(null); // is valid no matter the condition.
   $validation->isEmptyValid()->condition(....)->set(''); // is valid no matter the condition.
   $validation->isMissingValid()->condition(....)->get('field'); // If the field is missing, then is valid no matter the condition
```

It is used when we need to validate when an input has some value unless the value is missing, empty or null. 

> isNullorEmptyValid() is equals than to call: isEmptyValid()->isNullValid()

Also, those operators could be stacked.

```php
$validation
    ->isNullorEmptyValid()
    ->isMissingValid()
    ->condition(....)
    ->set(....); // this expression is valid if the value is null, empty(''), the value is missing, no matter the conditions.
```

## Processing the result

## def()

We could set a default value. This value could be as fallback when there is an error.  The default value is never converted or processed.

```php
$validation
    ->def(-1)
    ->type('integer')
    ->ifFailThenDefault() 
    ->set(...); // if the operation fails, then it returns -1
```



### trim()

Trim the result. By default, the result is not trimmed. You can trim the left, right or both sides. It uses the method convert() to do the operation.


```php
$validation->trim()->set(....); // trim both sided
$validation->trim('trim','.,')->set(....); // trim . and ,
$validation->trim('ltrim')->set(....); // trim left sided
$validation->trim('rtrim')->set(....); // trim right sided

```

## alwaysTrim()

Sometime we always want to trim the results. So we could use this method to always trim the result. It stacks at the end of the conversion.

```php
$validation->alwaysTrim(); // always trim the next characters " \t\n\r\0\x0B"
$validation->alwaysTrim(true,",."); // always trim , and .
// ...
$validation->alwaysTrim(false);  // we disable the always trim.
```



### convert()

It converts the end result after it is validated. Depending in the type of conversion, it allows up to 2 arguments.   The conversion could be stacked so the order could matter.

If the value is missing or it is used the default value, then it is not converted.

| Type       | Description                                                | Example                                                      |
| ---------- | ---------------------------------------------------------- | ------------------------------------------------------------ |
| upper      | Converts the value in uppercase                            | $this->conversion('upper')->set('Hello World'); // HELLO WORLD |
| lower      | Converts the value in lowercase                            | $this->conversion('lower')->set('Hello World'); // hello world |
| ucfirst    | Converts the first character in uppercase                  | $this->conversion('ucfirst')->set('hello world'); // Hello world |
| ucwords    | Converts the first character in a word in uppercase        | $this->conversion('ucwords')->set('hello world'); // Hello World |
| replace    | Replace a string by other                                  | $this->conversion('replace','hello','world')->set('hello hello'); // world world |
| sanitizer  | Sanitizer the result. It uses filter_var()                 | $this->conversion('sanitizer',FILTER_SANITIZE_EMAIL)->set('//aaa@bb.com'); // aaa@bb.com<br />$this->conversion('sanitizer',FILTER_SANITIZE_SPECIAL_CHARS,FILTER_FLAG_STRIP_HIGH) |
| rtrim      | Trim the right characters                                  | $this->conversion('rtrim')                                   |
| ltrim      | Trim the left characters                                   | $this->conversion('ltrim')                                   |
| trim       | Trim the right and left. It is equivalent to $this->trim() | $this->conversion('trim')->set(' hello '); // hello<br />$this->conversion('trim'," \t\n\r\0\x0B") |
| htmlencode | Encode to html content. It uses htmlentities()             | $this->conversion('htmlencode')->set('\<b>dog\</b>'); //\&lt;b\&gt;dog\&lt; |
| htmldecode | Decode from a html. It uses html_entity_decode()           | $this->conversion('htmldecode')->set('\&lt;b\&gt;dog\&lt;'); // \<b>dog\</b> |



```php
$validation
    ->convert('replace','hello','world') // world world
    ->convert('upper') // WORLD WORLD
    ->set('hello hello');  // stacking an operator.

$validation->convert('upper')->set(....); 
$validation->convert('lower')->set(....); 
$validation->convert('ucfirst')->set(....); 
$validation->convert('ucwords')->set(....); 
$validation->convert('replace','hello','world')->set(....); // trim right sided
$validation->convert('sanitizer',FILTER_SANITIZE_EMAIL)->set(....);
$validation->convert('rtrim')->set(....);
$validation->convert('ltrim')->set(....);
$validation->convert('trim')->set(....);
$validation->convert('htmlencode')->set(....);
$validation->convert('htmldecode')->set(....);

```




## version list

* 2021-02-13 1.29
  * Added the methods trim(), alwaysTrim(), convert(), errorCount() and hasError() .
* 2021-02-10 1.28
  * Added new method isNullOrEmptyValid()   
* 2021-02-10 1.27
  * Added new methods isMissingValid(), isEmptyValid() and isNulLValid()   
* 2021-02-09 1.26
  * New validations and methods.
  * exist() where the value must exist (but it could be null or empty)
  * required() now it validates if the value is not null or empty only. It does not validate if the value exists.
  * notempty()
* 2021-01-07 1.25
    * PHP 8.0 discontinued the constants INPUT_GET, INPUT_POST and INPUT_REQUEST, so we will use instead the numbers
        * INPUT_POST = 0
        * INPUT_GET = 1
        * INPUT_REQUEST = 99
    So, if you are using INPUT_GET,INPUT_POST OR INPUT_REQUEST, then they will still work.   
* 2020-10-01 1.24.3
    * A small cleanup.   
* 2020-05-21 1.24.2
    * Fixed a problem with conditions and array (when it is initiated).    
* 2020-05-21 1.24.1
    * Fixed a problem with conditions and array.      
* 2020-05-21 1.24
    * Cleanups
* 2020-04-07 1.23.2
    * Solved a problem with datetimestring and a defnatural
* 2020-04-07 1.23.1
    * Solved a problem with validation and input. It failed to validate.
    * Solved a problem where the default value is a string and the type is a datetimestring.
* 2020-02-01 1.23
    *  Solved a problem in endConversion() when the default value is "" or null (or not a DateTime object), the type is 
"datetimestring", and the value is missing.
    * Practically all methods were tested.
    * resetValidation() now allows to delete all messages.
    * Fixed the validation "ne"
* 2020-01-04 1.22
    * New conditions 'mime','minetype','exist','notexist',etc.
    * Condition 'eq' and 'ne' allows a simple or an array of values.
* 2020-01-03 1.21
    * ValidationOne::runConditions() now allows (for file type), conditions architecture and compression
    * ValidationOne::getFileExtension() now could return the extension as mime
    * ValidationOne::getFileMime() new method that returns the mime type of a file.
* 2019-11-27 1.20
  * Fixed the name countErrorOrWaring->countErrorOrWarning
* 2019-11-27 1.19 
  * Added new field MessageList.errorOrWarning 
  * Added new method MessageItem.countErrorOrWaring()
* 2019-10.01 1.18 Added compatibility for  phpunit/phpunit 5.7 and 6.5
* 2019-10-01 1.17 Fixed a bug. If the input is zero, then it is considered as null.
* 2019-08-10 1.16 Solved a problem with the datestring/datetimestring.
* 2019-08-07 1.15 
* * Added the type datestring and datetimestring. It reads a string and it converts into another string (as date or datetime)
* * Code formatted
* 2019-03-08 1.14 Added getFile() to upload a file.
* 2018-12-15 1.13 Added phpunit and travis.
* 2018-10-29 1.12 getFile now it's available via ValidationOne()
* 2018-10-22 1.11 Some fixes. Now isEmpty is called isMissing
* 2018-10-22 1.10 New Features
* * Added ValidationInputOne, now the fetchs are done by  this class (SRP principle)
* * Added a fix with the input, when the value expected is an array but it's returned a single value
* 2018-10-15 1.9 Added some extra features
* 2018-10-15 1.8 Some fixes and phpdocs, a new example
* 2018-10-15 1.7 Added method addMessage() in ValidationOne. Now ErrorItem/ErrorList is called MessageItem and MessageList
* 2018-10-06 1.5 added method first() in MessageItem 
* 2018-10-03 1.4 added defaultNatural()
* 2018-10-02 1.3 basicvalidation() was deleted. It was restored.
* 2018-10-02 1.2 array() is now isArray()
* 2018-09-30 1.1 Some fixes
* 2018-09-29 1.0 first version

## todo
* More examples
* Documentation


## Note

It's distributed as dual license, as LGPL-v3 and commercial.

You can use it freely in your close source project. However, if you change this library, then the changes must be disclosed.

