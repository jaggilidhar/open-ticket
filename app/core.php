<?php
declare(strict_types=1);
const ROOT = __DIR__.'/..';
function esc(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $page='home', array $params=[]): string {return 'index.php?'.http_build_query(['page'=>$page]+$params);}
function go(string $page='home',array $params=[]): never {header('Location: '.url($page,$params));exit;}
function csrf(): string {return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));}
function csrf_field(): string {return '<input type="hidden" name="csrf" value="'.esc(csrf()).'">';}
function verify_csrf(): void {if(!is_string($_POST['csrf']??null)||!hash_equals(csrf(),$_POST['csrf'])){http_response_code(403);exit('Invalid form token. Refresh the page and try again.');}}
function flash(string $m):void {$_SESSION['flash']=$m;}
function connection(array $c):PDO {return new PDO('mysql:host='.$c['host'].';port='.$c['port'].';dbname='.$c['database'].';charset=utf8mb4',$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}
function db():PDO {global $pdo;return $pdo;}
function query(string $sql,array $args=[]):PDOStatement {$s=db()->prepare($sql);$s->execute($args);return $s;}
function current_user():?array {static $u=false;if($u===false)$u=isset($_SESSION['uid'])?(query('SELECT id,name,email,role FROM ot_users WHERE id=?',[$_SESSION['uid']])->fetch()?:null):null;return $u;}
function require_user():array {$u=current_user();if(!$u)go('login');return $u;}
function require_admin():array {$u=require_user();if($u['role']!=='admin'){http_response_code(403);exit('Administrator access required.');}return $u;}
function setting(string $key,string $default=''):string {$v=query('SELECT value FROM ot_settings WHERE name=?',[$key])->fetchColumn();return $v===false?$default:(string)$v;}
function input(string $key,int $min,int $max):string {$v=trim((string)($_POST[$key]??''));if(strlen($v)<$min||strlen($v)>$max)throw new RuntimeException('Please check the '.$key.' field.');return $v;}
function email_input():string {$v=input('email',3,254);if(!filter_var($v,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid email address.');return strtolower($v);}
function qty_input(string $name,int $max):int {$v=filter_var($_POST[$name]??null,FILTER_VALIDATE_INT);if($v===false||$v<1||$v>$max)throw new RuntimeException('Invalid '.$name.'.');return $v;}
function event_by_id(int $id):?array {return query('SELECT * FROM ot_events WHERE id=?',[$id])->fetch()?:null;}
function sold(int $id):int {return (int)query("SELECT COALESCE(SUM(quantity),0) FROM ot_bookings WHERE event_id=? AND status='confirmed'",[$id])->fetchColumn();}
function reserve(int $eventId,int $userId,int $quantity,string $name,string $email):string {
 db()->beginTransaction();try {
 $e=query('SELECT * FROM ot_events WHERE id=? FOR UPDATE',[$eventId])->fetch();
 if(!$e||$e['status']!=='published'||strtotime($e['starts_at'])<=time())throw new RuntimeException('This event is unavailable.');
 if($quantity<1||$quantity>6||sold($eventId)+$quantity>(int)$e['capacity'])throw new RuntimeException('Not enough tickets remain.');
 $code=bin2hex(random_bytes(16));query("INSERT INTO ot_bookings(event_id,user_id,code,name,email,quantity) VALUES(?,?,?,?,?,?)",[$eventId,$userId,$code,$name,$email,$quantity]);db()->commit();return $code;
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}
function cancel_booking(int $id,int $uid):void {
 db()->beginTransaction();try{$b=query('SELECT event_id FROM ot_bookings WHERE id=? AND user_id=?',[$id,$uid])->fetch();if(!$b)throw new RuntimeException('Booking not found.');query('SELECT id FROM ot_events WHERE id=? FOR UPDATE',[$b['event_id']]);$s=query("UPDATE ot_bookings SET status='cancelled' WHERE id=? AND user_id=? AND checked_at IS NULL AND status='confirmed'",[$id,$uid]);if(!$s->rowCount())throw new RuntimeException('This booking cannot be cancelled.');db()->commit();}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
}
function checkin(string $code):void {if(!preg_match('/^[a-f0-9]{32}$/',$code))throw new RuntimeException('Invalid booking code.');$s=query("UPDATE ot_bookings SET checked_at=UTC_TIMESTAMP() WHERE code=? AND status='confirmed' AND checked_at IS NULL",[$code]);if(!$s->rowCount())throw new RuntimeException('Ticket not found, cancelled, or already checked in.');}
function throttle(string $purpose,int $limit=10):void {
 $key=hash('sha256',$purpose.'|'.($_SERVER['REMOTE_ADDR']??'unknown'));
 query('INSERT INTO ot_throttle (bucket,hits,expires_at) VALUES(?,1,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE hits=IF(expires_at<UTC_TIMESTAMP(),1,hits+1),expires_at=IF(expires_at<UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 15 MINUTE),expires_at)',[$key]);
 if((int)query('SELECT hits FROM ot_throttle WHERE bucket=?',[$key])->fetchColumn()>$limit)throw new RuntimeException('Too many attempts. Please wait 15 minutes.');
}
function display_date(string $utc):string {return (new DateTimeImmutable($utc,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone(setting('timezone','UTC')))->format('D, M j, Y · g:i A');}
function header_view(string $title):void {$site=isset($GLOBALS['pdo'])?setting('site_name','OpenTicket'):'OpenTicket';?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=esc($title.' — '.$site)?></title><link rel="stylesheet" href="assets/style.css"></head><body><header><a class="brand" href="<?=esc(url())?>"><span class="mark">✳</span><?=esc($site)?></a><?php if(isset($GLOBALS['pdo'])): ?><nav><a href="<?=esc(url())?>">Discover</a><a href="<?=esc(url('tickets'))?>">My tickets</a><?php if(current_user()):if(current_user()['role']==='admin'):?><a href="<?=esc(url('admin'))?>">Admin studio</a><?php endif;?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="logout"><button class="outline">Sign out</button></form><?php else:?><a class="button dark" href="<?=esc(url('login'))?>">Sign in ↗</a><?php endif;?></nav><?php endif;?></header><main><?php if(isset($_SESSION['flash'])):?><p class="notice" role="status"><?=esc($_SESSION['flash'])?></p><?php unset($_SESSION['flash']);endif; }
function footer_view():void {?></main><footer><b>OpenTicket CMS</b><span>Made for people. Built in the open.</span><span>v0.2.0 alpha · MIT licensed</span></footer></body></html><?php }
