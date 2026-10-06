import unittest, sqlite3, pathlib, re, tempfile, concurrent.futures
root=pathlib.Path(__file__).resolve().parents[1]
reservation=re.search(r'prepare\("(INSERT INTO bookings[^"\n]+)"\)',(root/'app/api/bookings/route.ts').read_text()).group(1)
checkin=re.search(r'prepare\("(UPDATE bookings[^"\n]+)"\)',(root/'app/api/checkin/route.ts').read_text()).group(1)
cancel=re.search(r'prepare\("(UPDATE bookings[^"\n]+)"\)',(root/'app/api/bookings/route.ts').read_text()).group(1)
class BookingTests(unittest.TestCase):
 def setUp(self):
  self.tmp=tempfile.TemporaryDirectory();self.path=self.tmp.name+'/test.db'
  self.db=sqlite3.connect(self.path)
  self.db.executescript((root/'drizzle/0000_curved_speedball.sql').read_text())
  self.db.execute("INSERT INTO events VALUES ('event','owner','Test','Description','Venue','2027-01-01','Community',3,0)")
  self.db.commit()
 def tearDown(self):self.db.close();self.tmp.cleanup()
 def reserve(self,id,q=1):
  with sqlite3.connect(self.path,timeout=10) as db:
   return db.execute(reservation,(id,'event','guest','Guest','guest@test.ca',q,'now','event',q,3)).rowcount
 def test_capacity(self):
  self.assertEqual(self.reserve('one',2),1);self.assertEqual(self.reserve('two',2),0)
 def test_concurrent_capacity(self):
  with concurrent.futures.ThreadPoolExecutor(max_workers=10) as ex:counts=list(ex.map(lambda i:self.reserve(str(i)),range(20)))
  self.assertEqual(sum(counts),3)
 def test_cancel_releases_capacity_and_checks_owner(self):
  self.reserve('one',3)
  self.assertEqual(self.db.execute(cancel,('one','stranger')).rowcount,0)
  self.assertEqual(self.db.execute(cancel,('one','guest')).rowcount,1);self.db.commit()
  self.assertEqual(self.reserve('two',3),1)
 def test_checkin_ownership_and_duplicates(self):
  self.reserve('one',1)
  self.assertEqual(self.db.execute(checkin,('now','one','stranger')).rowcount,0)
  self.assertEqual(self.db.execute(checkin,('now','one','owner')).rowcount,1)
  self.assertEqual(self.db.execute(checkin,('later','one','owner')).rowcount,0)
  self.assertEqual(self.db.execute(cancel,('one','guest')).rowcount,0)
if __name__=='__main__':unittest.main()
