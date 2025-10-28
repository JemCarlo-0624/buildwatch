-- Add evaluated timeline fields to project_proposals
ALTER TABLE `project_proposals`
  ADD COLUMN `evaluated_start_date` DATE NULL AFTER `end_date`,
  ADD COLUMN `evaluated_end_date` DATE NULL AFTER `evaluated_start_date`,
  ADD COLUMN `evaluation_notes` TEXT NULL AFTER `evaluated_end_date`;


