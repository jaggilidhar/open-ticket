"""Run only against a disposable blank database and uninstalled checkout."""
from pathlib import Path
import urllib.request,urllib.parse,urllib.error,http.cookiejar,re,os,subprocess,concurrent.futures
root=Path(__file__).resolve().parents[1];base=os.environ.get('TEST_URL','http://localhost:8080/')
class Client:
 def __init__(self):self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
 def get(self,page='home',**params):
  return self.opener.open(base+'index.php?'+urllib.parse.urlencode({'page':page,**params})).read().decode()
 def post(self,page,action=None,**params):
  pagehtml=self.get('tickets' if page=='event' else page);token=re.search(r'name="csrf" value="([a-f0-9]+)"',pagehtml).group(1)
  return self.opener.open(base+'index.php?page='+page,urllib.parse.urlencode({'csrf':token,**({'action':action} if action else {}),**params}).encode()).read().decode()
 def blocked(self,path,code):
  try:self.opener.open(base+path)
  except urllib.error.HTTPError as e:assert e.code==code;return
  raise AssertionError('Expected denial: '+path)
a=Client();assert 'Install OpenTicket' in a.get()
key=re.search(r"return '([^']+)'",(root/'storage/install-key.php').read_text()).group(1)
result=a.post('home',install_key=key,host=os.environ.get('TEST_DB_HOST','127.0.0.1'),port=os.environ.get('TEST_DB_PORT','3306'),database=os.environ.get('TEST_DB_NAME','openticket_test'),username=os.environ.get('TEST_DB_USER','root'),db_password=os.environ.get('TEST_DB_PASSWORD','testpass'),name='Test Admin',email='admin@example.com',password='AdminPassword123!',password_confirm='AdminPassword123!',site_name='Test Ticket',timezone='America/Vancouver')
assert 'Admin studio.' in result,result
assert (root/'storage/config.php').exists();assert not (root/'storage/install-key.php').exists()
assert 'Install OpenTicket' not in a.get()
a.blocked('storage/config.php',404);a.blocked('app/schema.sql',404)
from datetime import datetime,timedelta
future=(datetime.now()+timedelta(days=30)).strftime('%Y-%m-%dT%H:%M')
r=a.post('edit','event_save',title='Community <script> meetup',description='A friendly community gathering with refreshments.',venue='Town Hall',capacity=3,category='Community',starts_at=future,status='published')
assert 'Event saved.' in r,r
html=a.get();assert '&lt;script&gt;' in html;assert '<script>' not in html
id=re.search(r'page=event&amp;id=(\d+)',html).group(1)
b=Client();r=b.post('register','register',name='Guest Person',email='guest@example.com',password='GuestPassword123!');assert 'My tickets.' in r
b.blocked('index.php?page=admin',403)
b.blocked('index.php?page=export',403)
r=b.post('event','book',event_id=id,name='Guest Person',email='guest@example.com',quantity=2);assert 'Your tickets are reserved' in r,r
code=re.search(r'<code>([a-f0-9]{32})</code>',r).group(1)
r=b.post('event','book',event_id=id,name='Guest Person',email='guest@example.com',quantity=2);assert 'Not enough tickets' in r
anon=Client();assert code not in anon.get('ticket',code=code)
cancel_id=re.search(r'name="booking_id" value="(\d+)"',b.get('tickets')).group(1)
b.post('tickets','cancel',booking_id=cancel_id)
# Verify capacity has been released. Three independent PHP processes contend for 3 seats plus a fourth rejected request.
def reserve(_):return subprocess.check_output(['php',str(root/'tests/reserve-worker.php'),id,'2']).decode()
with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:results=list(pool.map(reserve,range(4)))
assert results.count('OK')==3,results
assert results.count('FULL')==1,results
# Cancel one of the guest's new reservations to test check-in and repeat admission.
tickets=b.get('tickets');codes=re.findall(r'<code>([a-f0-9]{32})</code>',tickets);newcode=next(c for c in codes if c!=code)
r=a.post('attendees','checkin',code=newcode);assert 'Admission confirmed.' in r
r=a.post('attendees','checkin',code=newcode);assert 'already checked in' in r
r=a.opener.open(base+'index.php?page=export').read().decode();assert 'Guest Person' in r or 'Concurrent guest' in r
# Write actions without CSRF are rejected.
try:b.opener.open(base+'index.php',urllib.parse.urlencode({'action':'logout'}).encode())
except urllib.error.HTTPError as e:assert e.code==403
else:raise AssertionError('Missing CSRF accepted')
a.post('settings','settings',site_name='My OpenTicket',home_description='A place to find our community events.',timezone='UTC');assert 'My OpenTicket' in a.get()
print('PASS: installer/lock, protected files, signup, admin authorization, escaping, booking, concurrent capacity, cancellation, check-in, duplicate admission, CSV, CSRF, settings')
