import TicketApp from './ticket-app';
import { getChatGPTUser } from './chatgpt-auth';
export const dynamic='force-dynamic';
export default async function Home(){const u=await getChatGPTUser();return <TicketApp user={u?{id:u.userId,name:u.displayName,email:u.email}:null}/>;}
