<?php


use eftec\ValidationOne;



if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    function create_autoloader($prefix, $base_dir) {
        return function ($class) use ($prefix, $base_dir) {
            if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
                return;
            }

            $file = $base_dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        };
    }

    spl_autoload_register(create_autoloader("eftec\\", __DIR__ . '/../lib/'));
    spl_autoload_register(create_autoloader("eftec\\tests\\", __DIR__ . '/'));
}
if (class_exists('PHPUnit_Framework_Error_Deprecated')) {
    PHPUnit_Framework_Error_Deprecated::$enabled = false;    
}



function getVal($prefix='frm_') {
	global $validation;
	if ($validation===null) {
		$validation=new ValidationOne($prefix);
	}
	return $validation;
}

