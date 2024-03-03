<?php
 namespace components\container\Exception;

use Exception;

 class notInstantiableTypeException extends Exception 
 {
        public function __construct($type , $code = 0 , $e = null)
        {
                parent::__construct("the type [$type] is not instantiable it could be an abstract class or a class with private constructor");
        }
 }