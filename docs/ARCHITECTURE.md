# Architecture

`app/ticket-app.tsx` is the client interface. Route handlers validate requests and authorize writes. `lib/server.ts` accesses D1 and resolves events. `db/schema.ts` defines persistent event and booking records.

A reservation uses one `INSERT ... SELECT ... WHERE` with the current confirmed quantity count. D1 serializes database writes; availability is evaluated in the same statement as insertion. Cancellation changes status and restores capacity. The check-in `UPDATE` requires confirmed status, no prior admission, and organizer ownership.

Booking codes are unguessable UUIDs. Knowing a code alone never grants organizer access. User IDs from the trusted hosted authentication dispatch scope ticket reads and cancellation. The organizer attendee list joins bookings to owned events.

Demo events live in a fixture catalog. Created events and bookings persist in D1. Demo events have no organizer account; check-in is available for events created by the signed-in organizer.

Future payment providers should expose create-checkout, verify-webhook, and refund methods separately from booking inventory. Paid inventory requires temporary holds and webhook-based confirmation. Do not confirm tickets from a browser payment redirect.
