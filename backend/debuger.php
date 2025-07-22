<?php
session_start();

echo 'DEbugger <br>';


if(session_status() === PHP_SESSION_NONE){
    echo 'no session'.' '.session_status().' '.PHP_SESSION_ACTIVE;
    session_start();
    echo 'there is session'.$_SESSION['user_id'];
   
}else{
    echo 'there is session'.$_SESSION['email'].$_SESSION['user_id'];;
}

?>