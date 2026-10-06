import { db,user,failure } from '@/lib/server';
export async function GET(){try{const u=await user();const {results}=await db().prepare('SELECT b.*,e.title FROM bookings b JOIN events e ON b.event_id=e.id WHERE e.owner=? ORDER BY b.created DESC').bind(u.userId).all();return Response.json(results);}catch(e){return failure(e);}}
