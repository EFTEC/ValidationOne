# ValidationOne
It's a PHP library for fetch and validate fields and store messages in different containers(including error, warning, info, and success) depending on the conditions.


[![Packagist](https://img.shields.io/packagist/v/eftec/validationone.svg)](https://packagist.org/packages/eftec/validationone)
[![Maintenance](https://img.shields.io/maintenance/yes/2018.svg)]()
[![composer](https://img.shields.io/badge/composer-%3E1.6-blue.svg)]()
[![php](https://img.shields.io/badge/php->5.6-green.svg)]()
[![php](https://img.shields.io/badge/php-7.x-green.svg)]()
[![CocoaPods](https://img.shields.io/badge/docs-70%25-yellow.svg)]()

## Examples

[Examples](https://github.com/EFTEC/ValidationOne/tree/master/examples)

[Tutorial Form and Table with PHP](https://github.com/EFTEC/BladeOne-tutorial1)

![diagram example](examples/docs/DiagramExample.jpg)  
It is an example of functionality.  A normal example is more complex, even if it's only a few lines of code.


## ValidationOne

Let's say we want to validate a value an input value (get) called "id", we could do the next things:

* the default value is the text "**ERROR**"
* the type of the value is an **integer**, so it must returns an integer.   It also could be an integer,decimal,string,date and boolean
* we add a condition, the value must be equals (**eq**) to **10**. If fails then it returns a message (as **error**)
* we add another condition, if the value must be equals (**eq**) to **30**. If fails then it returns an **info** (not an error)
* If the operation fails then it returns the default value.
* And finally, we obtain the "**id**" from $_GET (parameter url).

```php
$val=new ValidationOne();

$r = $val->def('ERROR')
    ->type('integer')
    ->condition("eq", "It's not equals to 10", 10)
    ->condition("eq", "It's not equals to 30 (info)", 30, 'info')
    ->ifFailThenDefault()
    ->get('id');
```

But, where is the error?.  It's in messagelist

```php
var_dump($val->messagelist->allArray())
```

### condition ($type, $message="", $value=null, $level='error')

* @param string $type  


        number:req,eq,ne,gt,lt,gte,lte,between
        string:req,eq,ne,minlen,maxlen,betweenlen,notnull
        date:req,eq,ne,gt,lt,gte,lte,between>
        boolean:req,eq,ne,true,false
        function:
            fn.static.Class.methodstatic
            fn.global.function
            fn.object.Class.method where object is a global $object
            fn.class.Class.method
            fn.class.\namespace\Class.method

* @param string $message  

       Message could uses the next variables '%field','%realfield','%value','%comp','%first','%second'  

* @param null $value
* @param string $level (error,warning,info,success)
* @return ValidationOne

## MessageList

MessageList is a list of containers of messages. It's aimed for convenience, so it features many methods to  access of the information in different ways. 

Messages are cataloged as follow

| id      | Description                                                          | Example                               |
|---------|----------------------------------------------------------------------|---------------------------------------|
| error   | The message is an error and it must be solved. It is a show stopper. | Database is down                      |
| warning | The message is a warning that maybe it could be ignored.             | The size of the field is at the limit |
| info    | The message is an information                                        | Log is stored                         |
| success | The message is a succesful operation                                 | Order Accepted                        |                             |


Sometimes, both errors are warning are considered as equals. So the system allows to read an error or warning.

Error has always the priority, then warning, info and success.  If you want to read the first message, then it starts searching for errors.

You can obtain a message as an array of objects of the type MessageItem, as an array of string, or as an a single string (first message)




## version list

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
 
It's distributed as dual license, as lgpl-v3 and commercial.
