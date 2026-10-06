import type { Metadata } from 'next';
import './globals.css';
export const metadata:Metadata={title:'OpenTicket — Good things happen together',description:'Discover events, reserve your place, and bring your community together with OpenTicket.'};
export default function RootLayout({children}:{children:React.ReactNode}){return <html lang="en"><body>{children}</body></html>;}
