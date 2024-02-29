<?php
 namespace components\container\Exception;

use Exception;

 class notFoundException extends Exception 
 {
   public function __construct($abstract)
   {
        parent::__construct("the abstract [$abstract] was not found, it seems it has not been bound to the container");
   }
 }