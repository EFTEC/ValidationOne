# ValidationOne
It's a php library for fetch and validate fields and returns an error (or a list of errors) depending in the conditions.

It's also a error-container library.

It's in beta.

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

$r = $val->default('ERROR')
    ->type('integer')
    ->condition("eq", "It's not equals to 10", 10)
    ->condition("eq", "It's not equals to 30 (info)", 30, 'info')
    ->ifFailThenDefault()
    ->get('id');
```

But, where is the error?.  It's in errorlist

```php
var_dump($val->errorList->allArray())
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

## ErrorList




## version list

* 2018-10-06 1.5 added method ErrorItem on first()
* 2018-10-03 1.4 added defaultNatural()
* 2018-10-02 1.3 basicvalidation() was deleted. It was restored.
* 2018-10-02 1.2 array() is now isArray()
* 2018-09-30 1.1 Some fixes
* 2018-09-29 1.0 first version

## Note
 
It's distributed as dual license, as lgpl-v3 and commercial.
