<?php
 namespace components\container;

use Closure;
use components\container\Exception\boundException;
use components\container\Exception\notFoundException;
use components\container\contracts\container as ContainerContract;
use components\container\Exception\noTargetClassExecption;
use components\container\Exception\notInstantiableTypeException;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
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
       private $parametersOverride = [];

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

         protected function createClosure($abstract , $concrete , $shared = false)
         {
           if($abstract !== $concrete)
           {
                $this->bind($concrete, $concrete , $shared);
           }
           return function($container , $params = []) use ($abstract , $concrete)
           {
              if($abstract === $concrete)
             {
                return $container->build($concrete);
             }
             if(is_string($concrete) && !$this->isInstantiableType($concrete))
             {
                return $concrete;
             }
              return $container->resolve($concrete, $params);
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
           return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
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
                $concrete = $this->createClosure($abstract , $concrete , $shared);
            }
            $this->bindings[$abstract] = ["concrete" => $concrete , "shared" => $shared];
         }
         public function singleton(string $abstract, $concrete)
         {
            $this->bind($abstract , $concrete , true);
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

         public function make($abstract, $params = [])
         {
           return $this->resolve($abstract , $params);
         }

         public function resolve(string $abstract, $params = [])
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
            array_push($this->parametersOverride , $params);
            $object = $this->isBuildable($abstract , $concrete ) ?
                      $this->build($concrete) : $this->make($concrete);
            array_pop($this->parametersOverride);
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
          if($concrete instanceof Closure)
          {
                return $concrete($this, $this->getParametersOverride());
          }
          try 
          {
            $reflector = new ReflectionClass($concrete);
          } catch(ReflectionException $e)
          {
             throw new noTargetClassExecption($concrete);
          }
          if(!$reflector->isInstantiable())
          {
                throw new notInstantiableTypeException($concrete);
          }
          $constructor = $reflector->getConstructor();
          
          // if there is no constructor
          // that means the class does not
          // have a dependency hence ,
          // instantaite it and return the instance

         if(is_null($constructor))
          {
            return new $concrete;
          }
          //get the dependencies of the class
          //by usng the reflection
          $dependencies = $constructor->getParameters();

          //resolve the dependencies recursively
          $resolvedDependencies = $this->resolveDependencies($dependencies);
          try 
          {
           // use the newInstanceArgs method of
           // the reflector object to instantiate the
           // class injecting it's dependencies.
           //return $reflector->newInstanceArgs(...$resolvedDependencies);
          }
           catch(Exception $e)
          {
            throw $e;
          }
        }
        /**
         * resolve the dependencies
         * @param array $dependencies
         * @return array 
         */

        protected function resolveDependencies($dependencies)
        {
           //an array to store the resolved dependencies instance
           $instances = [];

           foreach($dependencies as $dependency)
           {
                if($this->hasParamaterOverride($dependency))
                {
                  $instances[] = $this->getParameterOverride($dependency);
                  continue;
                }
                $instances[] = is_null($this->getClassParamName($dependency)) ?
                                $this->resolvePrimitive($dependency) : $this->resolveClass($dependency);
           }
          return $instances;
        }
        /**
         * get the class parameter name
         * @param \ReflectionParamter $param
         * @return string|null
         */
        protected function getClassParamName($param)
        {
          $type = $param->getType();
          if(!$type instanceof ReflectionNamedType || $type->isBuiltin())
            {
                return null;
            } 
           $name = $type->getName();
           //check if there is a declaring class
           // and get the name
           var_dump($param->getDeclaringclass());
          /* if(!($class = $param->getDeclaringClass()))
           {
                if ($name === 'self') {
                   return $class->getName();
                 }
                if ($name === 'parent' && $parent = $class->getParentClass()) 
                  {
                    return $parent->getName();
                  }
           }
           return $name;*/
        }

        /**
         * resolve primitive type
         * @param \ReflectionParameter $dependency
         * @return mixed
         */

         protected function resolvePrimitive($dependency)
         {

         }

        /**
         * resolve class type
         * @param \ReflectionParameter $dependency
         * @return object
         */

         protected function resolveClass($dependency)
         {

         }
        
        /**
         * get the parameter overide for the concrete
         * @return array
         */

         protected function getParametersOverride()
         {
           return count($this->parametersOverride) ? end($this->parametersOverride) : [];
         }

         /**
          * check if the type has a parameter override
          * @param \ReflectionParameter $dependency
          * @return bool
          */
        protected function hasParamaterOverride($dependency)
        {
           return array_key_exists($dependency->name , $this->getParametersOverride());
        }
        
        /**
         * get the override for the parameter
         * @param \ReflectionParameter $dependency
         * @return mixed
         * 
         */
        protected function getParameterOverride($dependency)
        {
           return $this->getParametersOverride()[$dependency->name];
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