import { z } from 'zod';
export const bookingInput=z.object({eventId:z.string().min(1).max(80),name:z.string().trim().min(2).max(100),email:z.string().email().max(254),quantity:z.number().int().min(1).max(6)});
export const eventInput=z.object({title:z.string().trim().min(3).max(120),description:z.string().trim().min(20).max(5000),venue:z.string().trim().min(3).max(200),date:z.string().refine(v=>Number.isFinite(Date.parse(v))&&Date.parse(v)>Date.now(),'Choose a future date'),category:z.enum(['Community','Workshops','Business','Music','Arts']),capacity:z.number().int().min(1).max(100000)});
