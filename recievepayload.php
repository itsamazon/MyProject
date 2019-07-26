<?php
header("Access-Control-Allow-Origin:*");
header("Access-Control-Allow-Headers: X-Requested-With,Authorrization,content-type, access-control-allow-origin,access-control-allow-methods,access-control-allow-headers");
include 'library/dblibery.php';
include 'class/general_ops.php';
require ('class/jwtimplement.php');
$forjwt=new HandleJwt;
$validate = new validate;
$db = new dbops;
$project = json_decode(file_get_contents("php://input"), true);
$randomer= new verify;
//var_dump($project); die()

//A346 is login, A347 is signup
$keys = array('A346', 'A347','A348','AB1'); //to put the acceptable keys, so when people call for the api we check if they used the right key
$jwtkeys=array('AB1');
if (isset($project['key'])) { //checking if key is sent in the payload
    if (in_array($project['key'], $keys)) { //checking if the key sent as the payload is part of the acceptable keys
      if(in_array($project['key'],$jwtkeys)){
       $decodejwt=$forjwt::openTokenfull($project['load']);
       //print_r($decodejwt); die();
       if(is_object($decodejwt)){
           //ghgghhgg();
           $checkjwtvalid=$forjwt::checkifcookieisvalid($decodejwt);
           //echo ($checkjwtvalid); die();
           if($checkjwtvalid == TRUE){
               //extract($decodejwt);
               $jwtuser=$decodejwt->id;
               $cont=TRUE;
           }
       }
     }else{ 
        $cont = TRUE;} //we check the keys to make sure theres an operation that has that key
    } 
    else {
        $error = "INVALID ACTIVITY";
    }
} else {
    $error = "KEY REQUIRED";
}
//die($error); BREAK IS TO JUMP THE CODE

if ($cont=TRUE){
    //signup block

    if ($project['key'] == 'A347') {
        $allfields = array('surname', 'name', 'email', 'othername', 'password','confirmpassword');
        $reqfields = array('surname', 'name', 'email', 'password'); //this array is store data,to make sure the payload sent contains all this
        $recval = array(); //this empty array is to take the data splitted from the $project as a single array
        foreach ($project as $vv => $value) { //associative array...looping through an array
            array_push($recval, $vv); //adding vv into allar recval
        }

        foreach ($reqfields as $ff) {
            if (in_array($ff, $recval)) { //this compares recval which is the recieved payload with ff which is the required fields checking if fields required are in the field that was sent; the values sent as reqfield are in the acceptable fields
                $cont = true;
            } else {
                 $message= "$ff required " ;
                $error = true;
                $rcode='0';
                
                break;}
        }

        if (!isset($error)) {
            foreach ($allfields as $checked) {
                if ($checked == 'surname' || $checked == 'name' || $checked == 'othername' or $project["othername"] = " ") {
                    $vv = $validate->datatype('1', $checked);
                }
                if ($vv == 0) {
                    $message="$checked is required' ";
                    $rcode='0';
                    break;
                }
                // checks the email
                if ($checked == 'email') {
                    // this checks if the email that was brought in met the requirement that is inside the general ops
                    $vv = $validate->datatype('4', $project[$checked]);
                    //  will echo this if it does not match the email validate rule
                    if ($vv == '0') {$error = "";
                        $message="$checked has to be an email ";
                        $rcode='0';
                        break;}
                }
                if ($checked == 'password') {
                    $vv = $validate->datatype('6', $project[$checked]);
                    if ($vv == '0') {$error = "";
                        $message=" $checked . ' ' . 'must be atleat 8'";
                        $rcode='0';
                        break;}
                }
                if ($checked == 'password') {
                    $vv = $validate->datatype('5', $project[$checked]);
                    if ($vv == '0') {$error = "";
                        $message="$checked . ' ' . ' not in right format'";
                    $rcode='0';}
                }
                if($checked == 'confirmpassword'){
                    $vv = $validate->datatype('5',$project[$checked]);
                }


            }
            if($project['password'] !== $project['confirmpassword']){
                $message="password doesn't match";
                $rcode="0";
                $error=true;
               }

        }
        // we check if ther eid no error in all the validation
        if (!isset($error)) {
            //this area is to check whether the email
            //extract the email
            $email = $project['email'];
            //specify the table
            $table = "merchanttable";
            //this is defining where the selection should be done from
            $where = "email='$email'";
            //defining the column to be selected from
            $col = "email";
            $fetchornot = " ";
            //calling the select method from db library
            $checkemail = $db->buildselect($table, $col, $where, '', '');
            //echo $checkemail; die();
            if ($checkemail == 0) {
                //extracting all the data in $project,this will make them single variable

                //i encrypted the password here;

                //sending or defining the columns which insertion should be make on this stage
                extract($project);
                $password = SHA1(md5($project['password']));
                // $name =$project['name'];
                // $surname=$project['surname'];
                // $othername=$project['othername'];
                // $email=$project['email'];
                $column = "name,surname,othernames,email,pasword";
                //defining the values to be sent, recall an extraction has been made above which converts the data to variables
                $values = " '$name','$surname','$othername','$email','$password' ";
                //activating or calling the insert function from the db library; $db was used to instantiate the class earlier on top of the code
                $insertdata = $db->buildinsert($table, $column, $values);
                $verifyit=$randomer->randomize();
                $t=time();
                $column=" email ,randoms,time,status";
                $values=" '$email','$verifyit','$t','0' ";
                $table='verificationtable';
                $insertdata = $db->buildinsert($table, $column, $values);
                // $encryptemail=sha1($email);
                // $newemail=substr($encryptemail,0,10);
                // $createdatabase= $db->createdatabase($newemail);

                $rcode='1';
                $message="";
               // $createtable=$db->createtable($newemail);
            }
            // this else is for when the checkmail test is failed; that is theres an email with that id already on the data base.
            else {
                $rcode='0';
               $message=  " sorry this email has previously been used ";

            }

        }
        $result=array('rcode'=>$rcode,'message'=>$message);
        
    }

    //login block
    // i used $keys instead of $project["keys"] because i
    if ($project['key'] == 'A346') {
        $Loginrequirement = array('email', 'password');
        $mylog = array(); //this empty array is to take the data splitted from the $project as a single array
        foreach ($project as $ab => $thishere) { //associative array...looping through an array
            array_push($mylog, $ab); //adding vv into allar recval
        }
        //looping through the loginrequiement so that it can be compared with mylog  which is the incoming feild
        foreach ($Loginrequirement as $login) {
            //this is where the comparison is done with in array
            if (in_array($login, $mylog)) {
                $continue = true;

            }
            //this is for the error when one of the required field is not provided
            else {
                $message="$login is required";
                $rcode="0";
                $error = true;
                break;
            }
        }
        //if theres no error that is all fields were provided
        if (!isset($error)) {
            //convertin the data in the project into single variables
            extract($project);
            //if email is sent, i want to validate it here
            if (isset

                ($email)) {
                $verify = $validate->datatype(4, $email);
            }
            //if email is not in the format
            if ($verify == 0) {
                $message="please fill in a valid email";
                $rcode="0";
                $error = true;
            }
            //checking the password input for security
            if (isset($password)) {
                $passwordcheck = $validate->datatype(5, $password);
            }
            if ($passwordcheck == 0) {
                $message="please fill in a valid password";
                $rcode="0";
                $error = true;

            }
        }
        if (!isset($error)) {
            $email = $project['email'];
            //specify the table
            $table = "merchanttable";
            //this is defining where the selection should be done from
            $where = " email='$email' and status<3";
            //defining the column to be selected from
            $col = "*";
            //recall that the 1 is used to activate the $fetchornot in the dplibrary to perform the fetch and use it for the if arguement below
            $checkemail = $db->buildselect($table, $col, $where, '', '1');
            //since this is login the number of rows numrow must be 1
            if ($checkemail['numrows'] == 1) {
                //comparing the paswword in the database with the one passed from the frond end pemember that it must be encrpted the same way it was in the sign up
                if ($checkemail['logresult'][0]['pasword'] === sha1(md5(($project['password'])))) {
                    $issuer='http://localhost:4200';
                    $audience='http://localhost:4200/#/ ';
                    $user_id=$project['email'];
                    $unique_id= sha1($user_id);
                    $tk=time() + 900;
                    $jay=$forjwt::encryptjwt($issuer,$audience,$user_id,$unique_id,$tk);
                    $message= $jay;
                    $rcode="1";
                } else {$message="invalid login";
                        $rcode="0";
                }
            } else {
                $message="invalid login";
                $rcode="0";
            }

        }
        $result=array('message'=>$message,'rcode'=>$rcode);
    }
//while loop is used to select multiple rows


//update column
if($project['key'] == 'A348' ){
    $fieldsneeded= array('schoolname','schooltype','schooladdress');
    $updatefield=array();
    foreach($project as $need=>$needed ){
        array_push($updatefield,$need);
    }
    foreach($fieldsneeded as $myfield){
        if(in_array($myfield,$updatefield)){
            $goon=true;
        } else{
            $message=" $myfield is required";
            $error=true;
            $rcode='0';
            break;
        }
    }
    if(!isset($error)){
        extract($project);
        if(isset($schoolname)){
            $verify=$validate->datatype(7,$schoolname); 
        }
        if($verify==0){
            $message="schoolname can only be alphanumeric";
            $error=true;
            $rcode='0';
        }
        if(isset($schooltype)){
            $verify=$validate->datatype(3,$schooltype);
        }
        if($verify== 0){
            $message= "school type must be a number";
            $error=true;
            $rcode='0'; 
        }
        if(isset($schooladdress)){
            $verify= $validate->datatype(7,$schooladdress);
        }
        if($verify== 0){
            $message= "school address not in the right format";
            $error=true;
            $rcode='0'; 
        }


    }
    if(!isset($error)){
        extract($project);
        $statement="schooladdress='$schooladdress',schoolname='$schoolname',schooltype='$schooltype' ";
        $updated=$db->update($statement,$email);
        $message="succesful";
        $rcode='1';
    }else{
        $message= "not going";
        $rcode='0';
    }
    $result=array('rcode'=>$rcode,'message'=>$message);    

 }


   if('AB1' == $project['key']){
        $table='merchanttable';
       // print_r($project);
      $col='status as how,name as who,email as iden,surname as swho,othernames as owo,schoolname as schn,schooltype as scht,schooladdress as scha,reg_date as regd,finishedprofile as fd';
      $where=" email= '$jwtuser' ";
       $b4dash=$db->buildselect($table, $col, $where, '', '1');
      $rcode="1";     $message=$b4dash;

      $result=array('rcode'=>$rcode,'message'=>$message);
    }
 
 }

if($result){
    echo json_encode($result);
}
?>
