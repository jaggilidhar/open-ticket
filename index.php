<?php
declare(strict_types=1);
ini_set('display_errors','0');
$https=($_SERVER['HTTPS']??'')==='on';
session_set_cookie_params(['httponly'=>true,'secure'=>$https,'samesite'=>'Lax','path'=>'/']);ini_set('session.use_strict_mode','1');session_start();
header('X-Content-Type-Options: nosniff');header('Referrer-Policy: same-origin');header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");header('Cache-Control: no-store');
require __DIR__.'/app/core.php';
if(!is_file(__DIR__.'/storage/config.php')){require __DIR__.'/app/install.php';exit;}
$config=require __DIR__.'/storage/config.php';
try{$pdo=connection($config);date_default_timezone_set('UTC');}catch(Throwable $e){http_response_code(503);exit('Database connection failed. Check storage/config.php or contact your hosting provider.');}
$page=(string)($_GET['page']??'home');$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=(string)($_POST['action']??'');
 try{switch($action){
 case 'register':throttle('register',5);$name=input('name',2,100);$email=email_input();$password=input('password',12,72);if(query('SELECT id FROM ot_users WHERE email=?',[$email])->fetch())throw new RuntimeException('Unable to create this account. Try another email or sign in.');query('INSERT INTO ot_users(name,email,password) VALUES(?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT)]);session_regenerate_id(true);$_SESSION['uid']=(int)db()->lastInsertId();go('tickets');
 case 'login':throttle('login');$email=email_input();$password=(string)($_POST['password']??'');$u=query('SELECT * FROM ot_users WHERE email=?',[$email])->fetch();$hash=$u['password']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';if(!password_verify($password,$hash)||!$u)throw new RuntimeException('Email or password is incorrect.');session_regenerate_id(true);$_SESSION['uid']=(int)$u['id'];go($u['role']==='admin'?'admin':'tickets');
 case 'logout':$_SESSION=[];session_regenerate_id(true);go();
 case 'book':$u=require_user();$eid=qty_input('event_id',PHP_INT_MAX);$code=reserve($eid,(int)$u['id'],qty_input('quantity',6),input('name',2,100),email_input());flash('Your tickets are reserved. Show your booking code at admission.');go('tickets');
 case 'cancel':$u=require_user();cancel_booking(qty_input('booking_id',PHP_INT_MAX),(int)$u['id']);flash('Booking cancelled.');go('tickets');
 case 'event_save':require_admin();$id=(int)($_POST['id']??0);$title=input('title',3,150);$description=input('description',20,10000);$venue=input('venue',3,200);$capacity=qty_input('capacity',100000);$category=input('category',1,30);if(!in_array($category,['Community','Music','Business','Workshops','Arts'],true))throw new RuntimeException('Invalid category.');$date=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',input('starts_at',16,16),new DateTimeZone(setting('timezone','UTC')));if(!$date||$date->format('Y-m-d\TH:i')!==$_POST['starts_at']||$date->getTimestamp()<=time())throw new RuntimeException('Choose a valid future date.');$utc=$date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');$status=input('status',1,20);if(!in_array($status,['draft','published','archived'],true))throw new RuntimeException('Invalid status.');
 db()->beginTransaction();if($id){if(!query('SELECT id FROM ot_events WHERE id=? FOR UPDATE',[$id])->fetch())throw new RuntimeException('Event not found.');if($capacity<sold($id))throw new RuntimeException('Capacity cannot be below existing reservations.');query('UPDATE ot_events SET title=?,description=?,venue=?,category=?,starts_at=?,capacity=?,status=? WHERE id=?',[$title,$description,$venue,$category,$utc,$capacity,$status,$id]);}else{query('INSERT INTO ot_events(title,description,venue,category,starts_at,capacity,status) VALUES(?,?,?,?,?,?,?)',[$title,$description,$venue,$category,$utc,$capacity,$status]);}db()->commit();flash('Event saved.');go('admin');
 case 'checkin':require_admin();checkin(trim((string)($_POST['code']??'')));flash('Admission confirmed. All tickets in this booking are checked in together.');go('attendees');
 case 'settings':require_admin();$name=input('site_name',2,100);$tz=input('timezone',1,80);if(!in_array($tz,DateTimeZone::listIdentifiers(),true))throw new RuntimeException('Choose a valid timezone.');$values=['site_name'=>$name,'timezone'=>$tz,'home_description'=>input('home_description',10,500)];foreach($values as $k=>$v)query('UPDATE ot_settings SET value=? WHERE name=?',[$v,$k]);flash('Settings saved.');go('settings');
 case 'password':$u=require_user();if(!password_verify((string)($_POST['current_password']??''),(string)query('SELECT password FROM ot_users WHERE id=?',[$u['id']])->fetchColumn()))throw new RuntimeException('Current password is incorrect.');$p=input('new_password',12,72);query('UPDATE ot_users SET password=? WHERE id=?',[password_hash($p,PASSWORD_DEFAULT),$u['id']]);session_regenerate_id(true);flash('Password updated.');go('account');
 default:throw new RuntimeException('Unknown action.');
 }}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();$error=$e instanceof RuntimeException&&!($e instanceof PDOException)?$e->getMessage():'Unable to complete this request. Please try again.';}
}
if($page==='export'){
 require_admin();$rows=query('SELECT b.name,b.email,e.title,b.quantity,b.status,b.code,b.checked_at FROM ot_bookings b JOIN ot_events e ON e.id=b.event_id ORDER BY b.id DESC');header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="openticket-attendees.csv"');$out=fopen('php://output','w');fputcsv($out,['Name','Email','Event','Tickets','Status','Code','Checked in'],';', '"','');foreach($rows as $r){foreach($r as &$v){$v=(string)$v;if(preg_match('/^[\s]*[=+@\-]/',$v))$v="'".$v;}unset($v);fputcsv($out,array_values($r),';','"','');}exit;
}
$adminPages=['admin','edit','attendees','settings'];if(in_array($page,$adminPages,true))require_admin();if(in_array($page,['tickets','account'],true))require_user();
header_view(ucfirst($page));if($error):?><p class="error" role="alert"><?=esc($error)?></p><?php endif;
require __DIR__.'/app/views.php';footer_view();
