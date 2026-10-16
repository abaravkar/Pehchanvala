<?php
// This script only provisions the isolated database in storage/mysql, port 3307.
$root=dirname(__DIR__);$envPath=$root.'/.env';$text=file_get_contents($envPath);
$rootFile=$root.'/storage/mysql/.root-password';$rootPassword=is_file($rootFile)?trim(file_get_contents($rootFile)):'';
$db=new PDO('mysql:host=127.0.0.1;port=3307;charset=utf8mb4','root',$rootPassword,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$actual=strtolower(str_replace('\\','/',rtrim($db->query('SELECT @@datadir')->fetchColumn(),'/\\')));
$expected=strtolower(str_replace('\\','/',realpath($root.'/storage/mysql')));
if($actual!==$expected)throw new RuntimeException('Port 3307 is not the isolated project database. No changes were made.');
$db->exec('CREATE DATABASE IF NOT EXISTS pehchanvala_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
preg_match('/^DB_PASSWORD=(.*)$/m',$text,$match);
$password=(str_contains($text,'DB_USERNAME=pehchanvala_local')&& !empty($match[1]))?trim($match[1]):bin2hex(random_bytes(24));
$db->exec("CREATE USER IF NOT EXISTS 'pehchanvala_local'@'127.0.0.1' IDENTIFIED BY ".$db->quote($password));
$db->exec("ALTER USER 'pehchanvala_local'@'127.0.0.1' IDENTIFIED BY ".$db->quote($password));
$db->exec("GRANT ALL PRIVILEGES ON pehchanvala_local.* TO 'pehchanvala_local'@'127.0.0.1'");
if(!$rootPassword){$rootPassword=bin2hex(random_bytes(32));file_put_contents($rootFile,$rootPassword);$db->exec("ALTER USER 'root'@'localhost' IDENTIFIED BY ".$db->quote($rootPassword));}
$values=['APP_NAME'=>'Pehchanvala','APP_ENV'=>'local','APP_URL'=>'http://127.0.0.1:8000','APP_TIMEZONE'=>'Asia/Kolkata','DB_CONNECTION'=>'mysql','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3307','DB_DATABASE'=>'pehchanvala_local','DB_USERNAME'=>'pehchanvala_local','DB_PASSWORD'=>$password,'SESSION_DRIVER'=>'database','QUEUE_CONNECTION'=>'database','CACHE_STORE'=>'database','MAIL_MAILER'=>'log','MAIL_FROM_ADDRESS'=>'info@pehchanvala.com','MAIL_FROM_NAME'=>'Pehchanvala','AGENCY_EMAIL'=>'info@pehchanvala.com','RAZORPAY_KEY_ID'=>'','RAZORPAY_KEY_SECRET'=>'','RAZORPAY_WEBHOOK_SECRET'=>'','PAYMENTS_ENABLED'=>'false'];
foreach($values as $key=>$value){$exists=preg_match('/^'.preg_quote($key,'/').'=/m',$text);if($exists&&in_array($key,['MAIL_MAILER','MAIL_FROM_ADDRESS','MAIL_FROM_NAME','AGENCY_EMAIL','RAZORPAY_KEY_ID','RAZORPAY_KEY_SECRET','RAZORPAY_WEBHOOK_SECRET','PAYMENTS_ENABLED']))continue;if($exists)$text=preg_replace('/^'.preg_quote($key,'/').'=.*$/m',$key.'='.$value,$text);else $text.="\n$key=$value";}
file_put_contents($envPath,$text."\n");echo "Local application database and environment configured. Secrets were not printed.\n";
