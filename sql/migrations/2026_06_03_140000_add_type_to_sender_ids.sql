-- Add type column to sender_ids table to distinguish KudiSMS SMS sender IDs from Termii OTP sender IDs
ALTER TABLE `sender_ids` ADD COLUMN `type` ENUM('sms', 'otp') NOT NULL DEFAULT 'sms' AFTER `status`;
