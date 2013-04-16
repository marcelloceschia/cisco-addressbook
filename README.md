Valide backends:

#### carddav
```php
  $deviceMapping['SEP123456789'] = array('username' => 'e-groupware', 'password' => 'user', 'backend' => "carddav", uri => "http://carddav.loacal/addressbook/");
```
#### egroupware
```php
  $deviceMapping['SEP123456789'] = array('username' => 'e-groupware', 'password' => 'user', 'backend' => "egroupware");
```
#### exchange
```php
  $deviceMapping['SEP123456789'] = array('username' => 'exchange', 'password' => 'user', 'backend' => "exchange");
```
  you have to edit backend/exchange/services.wsdl and change the <soap:address location="https://my.exchangeserver.org/EWS/Exchange.asmx"/>
exchange2003
    
#### google
```php
  $deviceMapping['SEP123456789'] = array('username' => 'test', 'password' => 'pass', 'backend' => "google");
```
