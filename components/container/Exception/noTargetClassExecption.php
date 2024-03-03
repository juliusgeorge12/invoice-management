<?php
 namespace components\container\Exception;

use Exception;

 class noTargetClassExecption extends Exception 
 {
        public function __construct($class , $code = 0 , $e = null)
        { 
          parent::__construct("the target class [$class] does not exist" , $code , $e);
        }
 }