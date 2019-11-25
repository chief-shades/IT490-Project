
<?php
			     // RabbitMQ Server File 
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
//require_once('APItest.php');

// Login Function 
function doLogin($username, $password)
{
  //Database connection - database server ip, user, pass, database 
  $database_connection = new mysqli("localhost", "user", "pass",     
                                    "ramblers") ;

  //SQL Query running on the User Table
  $login_query = "select * from registration where username = '$username' and 
                  pass = '$password' " ;

  //Executing SQL Query
  $query_result = mysqli_query($database_connection, $login_query) or      
                  die(mysqli_error($database_connection)) ; 

  //Counting Rows in our User Table 
  $count_rows = mysqli_num_rows($query_result) ; 

  //Checking if User is in our Database 
  if ($count_rows > 0) // if user is in our database return 1
  {
      return 1 ;
  }
  else                // else user is not in our database return 0
  {
      return 0 ;
  }
}

// Registration Function 
function doRegister($username, $password, $email)
{
  //Database connection - database server ip, user, pass, database 
  $database_connection = new mysqli("localhost", "user", "pass",     
                                    "ramblers") ;

  //SQL Query running on the User Table
  $check_user_query = " select * from registration where 
                        username = '$username' " ; 
                                         
  //Executing SQL Query
  $query_result = mysqli_query($database_connection, $check_user_query) or      
                  die(mysqli_error($database_connection)) ; 

  //Counting Rows in our User Table 
  $count_rows = mysqli_num_rows($query_result) ; 

  //Checking if the User is in our Database 
  if ( $count_rows > 0 ) //if user exists in our database 
  {
       //echo  " User already exists in our Database !!! " ;
       return 1 ;
  }
  else
  {
       //If the user is not registered - Getting the user Registered !!!

       $password = sha1 ($password); //hashing the password

       //SQL Query running on the User Table
       $register_query = " INSERT INTO registration (username, pass,         
                           email) VALUES ('$username','$password', 
                           '$email') " ;

       //Executing SQL Query
       $query_result = mysqli_query($database_connection, $register_query)     
                       or die(mysqli_error($database_connection)) ; 

       //echo " You are Successfully Registered !!! " ;
       return 0 ;
  }	

}

function api_data($input) #input is shoes brand such as "nike"
{
     //Database connection - database server ip, user, pass, database  
     $database_connection = new mysqli("localhost", "user", "pass",   
                                     "ramblers") ;

     //SQL Query running on the shoes Table
      $api_get_query = "select * from shoes where brand = '$input'" ;

     //Executing SQL Query
      $query_result = mysqli_query($database_connection, $api_get_query)     
                       or die(mysqli_error($database_connection)) ; 

     //Counting Rows in our User Table 
     $count_rows = mysqli_num_rows($query_result) ; 

     //Checking if the Brand is in our Database 
     if ( $count_rows > 0 ) //if brand exists in our database 
     {
       $brand_exist = " Brand '$input' already exists in our Database !!! " ;
       $r = mysqli_fetch_array($query_result, MYSQLI_ASSOC) ;	
       $data ="<b> $brand_exist </b>" . "<br>" . $r['data'] ;
       return $data;
     }
     else
     { 
	    //type and $input is what user inputs on the front end	
	    $type = "Api_search";

	    $client = new RabbitMQClient('testRabbitMQ.ini', 'testServer');

	    //Putting brand input from front end on the array
	    $req = array("brand"=>$input, "type"=>$type);

            //Database Server is sending request 
            //to the MQ for API to respond back to DB Server
	    $response = $client->send_request($req);

	    $apiData = $response; //storing api data 

	     // Calling the API for data & storing it in a local variable
	     //$apiData = search($input);

	     //SQL Query running on the shoes Table
	     $api_query = "INSERT INTO shoes (brand, data )       
		                   VALUES ('$input','$apiData') " ;
		                   
	     //Executing SQL Query
	     $query_result = mysqli_query($database_connection, $api_query) 
                             or die(mysqli_error($database_connection)) ; 

	      //SQL Query running on the shoes Table
	      $api_get_query = "select * from shoes where brand = '$input'" ;
		          
	      //Executing SQL Query
	      $query_result = mysqli_query($database_connection, 
                             $api_get_query) or                      
                             die(mysqli_error($database_connection)) ; 
	 
	      $r = mysqli_fetch_array($query_result, MYSQLI_ASSOC) ;
	      $output = "<b>Shoes Brand: </b>" . $r['brand'] . "<br>" . 
                        "<b>Api Database Returned Data: </b>" . $r['data'];	
	      return $output;
      	}
}

function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  var_dump($request);
  if(!isset($request['type']))
  {
    return "ERROR: unsupported message type";
  }
	echo "Processing: " . var_export($request, true);
  switch ($request['type'])
  {
    case "login":
      return doLogin($request['username'],$request['pass']);
    case "validate_session":
      return doValidate($request['sessionId']);
    case "register":
      return doRegister($request['username'],$request['pass'], 
                        $request['email']);
    //case "search";
      //return search($request["brand"]);
    case "search";
      return api_data($request["brand"]);

  }
  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>

