<?php
 namespace components\container\Exception;

 use Exception; 

 class boundException extends Exception 
 {
   public function __construct($abstract)
   {
    parent::__construct("the abstract [$abstract] has already be bound to the container");
   }
 }