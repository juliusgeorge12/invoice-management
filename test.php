 <?php
  require "vendor/autoload.php";
 use components\container\container;

 interface testClassInterface
 {

 }
 class Name 
 {

 }
  class testClass 
 {
  public function __construct(Name $name)
  {
    //echo $test;   
  }
 }

 try {
  $con = new container;

  $con->bind('config.path' , function($app , $param)
{
      return "{$param["basePath"]}/config/";
});
 $con->bind('echo' , function($app , $param)
 {
    echo $param["msg"];
 });
// $con->bind(testClass::class);
 $con->bind(testClassInterface::class , 'testClass');
 echo $con->resolve('config.path' , ['basePath' => __DIR__]);
 echo "\r\n";
 $con->resolve('echo', ['msg' => 'hello how are you doing']);
 //var_dump($con->resolve(testClassInterface::class));
} catch(Exception $e)
{
        echo $e->getMessage();
}