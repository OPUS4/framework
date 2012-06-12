INSERT INTO `collections_roles` (`name`, `oai_name`, `position`, `visible`, `visible_browsing_start`, `display_browsing`, `visible_frontdoor`, `display_frontdoor`, `visible_oai`) VALUES
('open_access', 'open_access', 100, 1, 0, 'Name', 0, 'Name', 1);
SET @roleid = LAST_INSERT_ID();

INSERT INTO `collections` (`role_id`, `number`, `name`, `oai_subset`, `sort_order`, `left_id`, `right_id`, `parent_id`, `visible`) VALUES
(@roleid, NULL, NULL, NULL, 0, 1, 4, NULL, 1);
SET @rootid = LAST_INSERT_ID();

INSERT INTO `collections` (`role_id`, `number`, `name`, `oai_subset`, `sort_order`, `left_id`, `right_id`, `parent_id`, `visible`) VALUES
(@roleid, '', 'open_access', 'open_access', 0, 2, 3, @rootid, 1);
