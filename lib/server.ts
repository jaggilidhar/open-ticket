import { env } from 'cloudflare:workers';
import { getChatGPTUser } from '@/app/chatgpt-auth';
import { samples, type Event } from './catalog';
export function db(){if(!env.DB)throw new Error('Database unavailable');return env.DB;}
export async function user(){const u=await getChatGPTUser();if(!u)throw new Error('Sign in required');return u;}
export function sameOrigin(req:Request){const origin=req.headers.get('origin');if(!origin||origin!==new URL(req.url).origin)throw new Error('Invalid request origin');}
export async function catalog():Promise<Event[]>{const {results}=await db().prepare('SELECT * FROM events').all<Event>();return [...samples,...results];}
export async function findEvent(id:string){return (await catalog()).find(x=>x.id===id);}
export function failure(e:unknown){const m=e instanceof Error?e.message:'Request failed';return Response.json({error:m==='Sign in required'?m:m==='Invalid request origin'?m:'Request could not be completed. Please try again.'},{status:m==='Sign in required'?401:m==='Invalid request origin'?403:500});}
