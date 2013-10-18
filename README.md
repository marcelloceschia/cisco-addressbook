Instructions:
=============

```
git clone https://github.com/marcelloceschia/cisco-addressbook.git cisco-addressbook
cd cisco-addressbook
git submodule init
git submodule update

chmod o+w cache		[ or chown www-data:www-data cache, whereby the username:group combination has to match your webserver]
cp config.sample.php config.php
```

and 

edit config.php and .htaccess to match your configuration and devices

make sure that the php library mbstrings is installed


Valid backends:
===============
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
