<?php

interface Backend{
    public function setUsername($username);
    public function setPassword($password);

    public function getEntries($offset, $limit = null);
    public function getEntry($id);
    public function search($lastname = null, $firstname = null);
    public function reverseLookup($number);
   
}
?>
