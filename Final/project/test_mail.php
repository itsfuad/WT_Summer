<?php

require_once 'config/email.php';

$emailManager = new EmailManager();

echo $emailManager->sendOTP('fuad.cs22@gmail.com', 123456);

?>