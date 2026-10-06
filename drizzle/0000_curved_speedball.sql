CREATE TABLE `bookings` (
	`id` text PRIMARY KEY NOT NULL,
	`event_id` text NOT NULL,
	`user_id` text NOT NULL,
	`name` text NOT NULL,
	`email` text NOT NULL,
	`quantity` integer NOT NULL,
	`status` text DEFAULT 'confirmed' NOT NULL,
	`created` text NOT NULL,
	`checked` text
);
--> statement-breakpoint
CREATE TABLE `events` (
	`id` text PRIMARY KEY NOT NULL,
	`owner` text NOT NULL,
	`title` text NOT NULL,
	`description` text NOT NULL,
	`venue` text NOT NULL,
	`date` text NOT NULL,
	`category` text NOT NULL,
	`capacity` integer NOT NULL,
	`price` integer DEFAULT 0 NOT NULL
);
