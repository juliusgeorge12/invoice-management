<?php
 namespace components\contracts\container;

 /**
  * this is the ioc container used to
  * inject dependencies into object
  */

  interface container 
  {
        /**
         * 
         * bind an abstract type to the container
         * @param string $abstract 
         * @param string|callback $concrete
         * @param bool $shared 
         */
        public function bind(string $abstract , $concrete = null , $shared = false);

        /**
         * 
         * bind an abstract that should be resolve once to
         * the container
         * @param string $abstract 
         * @param string|callback $concrete
         */
        public function singleton(string $abstract , $concrete);

        /**
         * resolve an abstract type from the container
         * @param string $abstract
         * @param array|null $params
         */
        public function resolve(string $abstract , $params = null);

        /**
         * return a concrete for an abstract
         * @param string $abstract
         */
        public function make($abstract , $params = null);

        /**
         * check if an abstract has been bound to the container
         * @param string $abstract
         * @return bool
         */
        public function bound(string $abstract);

        /**
         * check if an abstract has been resolved
         * @param string $abstract
         * @return bool 
         */

        public function isResolved(string $abstract);

        /**
         * check if an abstract is a shared abstract
         * @param string $abstract
         * @return bool
         */
        public function isShared(string $abstract);

        /**
         * return a binding or a concrete for an abstract type
         * @param $abstract
         */

         public function get($abstract);
        
        /**
         * check if the abstract type exist
         * @param string $abstract
         * @return bool
         */
         public function has($abstract);

        /**
         * get the container instance
         * @return \container
         */
        public static function getInstance();

        /**
         * set the container instance
         * @param \container $instance
         */

         public static function setInstance($instance);

        /**
         * flush all the contain bindings and free resources
         * 
         */

         public function flush();



  }