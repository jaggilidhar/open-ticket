export type Event = {id:string;owner:string;title:string;description:string;venue:string;date:string;category:string;capacity:number;price:number;remaining?:number};
export const samples:Event[] = [
{id:'community-night',owner:'demo',title:'The community table',description:'Good food. New faces. Better conversations. An evening bringing together the people who make our city special. Includes light refreshments and a community-led conversation.',venue:'Chilliwack Community Hall, BC',date:'2027-03-20T18:00',category:'Community',capacity:80,price:0},
{id:'makers-weekend',owner:'demo',title:'Makers, meet makers',description:'A Saturday for curious minds. Discover local crafts, meet independent creators, and try something new at our hands-on workshops.',venue:'Vancouver Arts Centre, BC',date:'2027-04-10T10:00',category:'Workshops',capacity:120,price:0},
{id:'founders-coffee',owner:'demo',title:'Ideas over coffee',description:'Meet local founders and freelancers over a fresh cup. Share what you are building and find your next collaborator.',venue:'Downtown Studio, Abbotsford, BC',date:'2027-05-15T09:00',category:'Business',capacity:40,price:0}
];
