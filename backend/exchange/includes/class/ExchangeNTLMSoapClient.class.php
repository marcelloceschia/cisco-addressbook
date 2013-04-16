<?php
class ExchangeNTLMSoapClient extends NTLMSoapClient {
    protected $user 	= '';
    protected $password = '';
    
    public function setUsername($username){
	      $this->user = $username;
    }
    
    public function setPassword($pass){
	      $this->password = $pass;
    }
}
?>
