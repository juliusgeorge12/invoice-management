<?php
 namespace components\container;

use Closure;
use components\container\Exception\boundException;
use components\container\Exception\notFoundException;
use components\contracts\container\container as ContainerContract;
use ReflectionClass;
use TypeError;

 /**
  * this is the ioc container used to
  * inject dependencies into object
  */

  class container implements ContainerContract
  {
    /**
     * the container instance
     * 
     */
     private static $instance = null;

     /**
      * cached instances
      * @var array [$abstract => $instance ,...]
      */
      private $instances = [];

      /**
       * container bindings
       * @var array [$abtract=>["concrete"=>"implementation" , "shared"=>bool] , ...]
       */
     private $bindings = [];

     /**
      * array of resolved types
      * @var array [$abstract,...]
      */
      private $resolved = [];
      
      /**
       * parameter overide for the abstract to 
       * be resolved
       * @var array
       */
       private $parametersOveride = null;

      /**
       * check if an abstract is a shared object
       * @param string $abstract
       * @return bool 
       */

       public function isShared(string $abstract)
       {
        return (isset($this->instances[$abstract]) || 
                ($this->bindings[$abstract] && $this->bindings[$abstract]["shared"] === true));
       }
       /**
        * check if a abstract has been resolved
        * @param string $abstract
        * @return bool
        */

        public function isResolved(string $abstract)
        {
          return (in_array($abstract , $this->resolved) || isset($instances[$abstract]));
        }

        /**
         * create a closure to be used as a concrete type
         * 
         */

         protected function createClosure($abstract , $concrete)
         {
           return function($container , $params = []) use ($abstract , $concrete)
           {
             if($abstract === $concrete)
             {
                return $container->build($concrete);
             }
             if(is_string($concrete))
             {
                if($this->isInstantiableType($concrete))
                {
                  return new $concrete(...array_values($params));
                }
                return $concrete;
             }
             return $container->resolve($abstract, $params);
           };
         }

         /**
          * check if the given concrete is a class and is
          * instantiable
          * @param string $concrete
          * @return bool
          */
         protected function isInstantiableType($concrete)
         {
            if(class_exists($concrete))
            {
                $reflector = new ReflectionClass($concrete);
                return !$reflector->isAbstract() || $reflector->isInstantiable();
            }
            return false;
         }
        /**
         * check if an abstract type has been bound to the container
         * @param string $abstract
         * @return bool
         */

         public function bound(string $abstract)
         {
           return (isset($this->bindings[$abstract]) || $this->instances[$abstract]);
         }

         public function bind(string $abstract, $concrete = null, $shared = false)
         {
            if($this->bound($abstract))
            {
                throw new boundException($abstract);
            }
            if(is_null($concrete))
            {
                $concrete = $abstract;
            }
            if(!$concrete instanceof Closure)
            {
                if(!is_string($concrete))
                {
                  throw new TypeError(self::class . '::bind(): Argument #2 ($concrete) must be of type Closure|string|null');  
                }
                $concrete = $this->createClosure($abstract , $concrete);
            }
            $this->bindings[$abstract] = ["concrete" => $concrete , "shared" => $shared];
         }

         /**
          * get the concrete for the abstract type
          * @param string $abstract
          */
         protected function getConcrete(string $abstract)
         {
            if($this->bound($abstract))
            {
              return $this->bindings[$abstract]["concrete"];
            }
            throw new notFoundException($abstract);
         }

         public function singleton(string $abstract, $concrete)
         {
            $this->bind($abstract , $concrete , true);
         }

         public function make($abstract, $params = null)
         {
           return $this->resolve($abstract , $params);
         }

         public function resolve(string $abstract, $params = null)
         {
           $concrete = $this->getConcrete($abstract);
            /*
             check if the abstract is a singleton
             if it is a singleton we will check if it has 
             been resolve if yes, we will just return 
             the cached instance
           */
           if(isset($this->instances[$abstract])){
              return $this->instances[$abstract];
            }
            $this->parametersOveride = $params;
           if($this->isBuildable($abstract , $concrete ))
           {
             $object = $this->build($concrete);
           }
           else 
           {
             $object = $this->make($concrete);
           }
           $this->parametersOveride = null;
           return $object;
         }

         /**
          * check if the type is buildable
          * @param string $abstract 
          * @param mixed $concrete
          * @return bool
          */
         protected function isBuildable(string $abstract , mixed $concrete)
         {
            return $abstract === $concrete || ($concrete instanceof Closure);
         }

         /**
          * build the concrete
          * @param mixed $concrete
          */
        
          protected function build($concrete)
          {

          }

         public function get($abstract)
         {
                
         }

         public function has($abstract)
         {
                
         }

         public static function setInstance($instance)
         {
                
         }

         public static function getInstance()
         {
                
         }
         
         public function flush()
         {
                
         }


  }