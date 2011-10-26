-- add new language multilanguage

UPDATE languages SET part2_b = 'mul', part2_t = 'mul', ref_name = 'Multiple languages', active = 1 WHERE id = '8';

-- update creative commons licence information

UPDATE document_licences SET
comment_internal = 'Namensnennung-NichtKommerziell-KeineBearbeitung\r\n\r\nDritte können die Arbeit elektronisch auf beliebigen Servern anbieten oder gedruckte Kopien erstellen (aber: mit Namensnennung, \r\nnicht-kommerziell und keine Veränderung).',
desc_markup = '<!-- Creative Commons-Lizenzvertrag -->\r\n<a rel=\"license\" href=\"http://creativecommons.org/licenses/by-nc-nd/3.0/de/\"><img alt=\"Creative Commons-Lizenzvertrag\" border=\"0\" src=\"http://creativecommons.org/images/public/somerights20.gif\" /></a><br />\r\nDiese Inhalt ist unter einer <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-nc-nd/3.0/de/\">Creative Commons-Lizenz</a> lizenziert.\r\n<!-- /Creative Commons-Lizenzvertrag -->\r\n\r\n\r\n<!--\r\n\r\n<rdf:RDF xmlns=\"http://web.resource.org/cc/\"\r\n    xmlns:dc=\"http://purl.org/dc/elements/1.1/\"\r\n    xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\">\r\n<Work rdf:about=\"\">\r\n   <dc:type rdf:resource=\"http://purl.org/dc/dcmitype/Text\" />\r\n   <license rdf:resource=\"http://creativecommons.org/licenses/by-nc-nd/3.0/de/\" />\r\n</Work>\r\n\r\n<License rdf:about=\"http://creativecommons.org/licenses/by-nc-nd/3.0/de/\">\r\n   <permits rdf:resource=\"http://web.resource.org/cc/Reproduction\" />\r\n   <permits rdf:resource=\"http://web.resource.org/cc/Distribution\" />\r\n   <requires rdf:resource=\"http://web.resource.org/cc/Notice\" />\r\n   <requires rdf:resource=\"http://web.resource.org/cc/Attribution\" />\r\n   <prohibits rdf:resource=\"http://web.resource.org/cc/CommercialUse\" />\r\n</License>\r\n\r\n</rdf:RDF>\r\n\r\n-->',
desc_text = 'Dies ist die restriktivste der sechs Kernlizenzen. Sie erlaubt lediglich Download und Weiterverteilung des Werkes/Inhaltes unter Nennung Ihres Namens, jedoch keinerlei Bearbeitung oder kommerzielle Nutzung.',
link_licence = 'http://creativecommons.org/licenses/by-nc-nd/3.0/de/deed.de',
link_logo = 'http://i.creativecommons.org/l/by-nc-nd/3.0/de/88x31.png',
link_sign = '',
name_long = 'Creative Commons - Namensnennung-Nicht kommerziell-Keine Bearbeitung'
WHERE id = 3;

UPDATE document_licences SET
comment_internal = 'Lediglich die Namensnennung ist zwingend.',
desc_markup = '<!-- Creative Commons License -->\r\n<a rel=\"license\" href=\"http://creativecommons.org/licenses/by/3.0/de/\"><img alt=\"Creative Commons License\" border=\"0\" src=\"http://creativecommons.org/images/public/somerights20.gif\" /></a><br />\r\nThis work is licensed under a <a rel=\"license\" href=\"http://creativecommons.org/licenses/by/3.0/de/\">Creative Commons License</a>.\r\n<!-- /Creative Commons License -->\r\n\r\n\r\n<!--\r\n\r\n<rdf:RDF xmlns=\"http://web.resource.org/cc/\"\r\n    xmlns:dc=\"http://purl.org/dc/elements/1.1/\"\r\n    xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\">\r\n<Work rdf:about=\"\">\r\n   <dc:type rdf:resource=\"http://purl.org/dc/dcmitype/Text\" />\r\n   <license rdf:resource=\"http://creativecommons.org/licenses/by/3.0/de/\" />\r\n</Work>\r\n\r\n<License rdf:about=\"http://creativecommons.org/licenses/by/3.0/de/\">\r\n   <permits rdf:resource=\"http://web.resource.org/cc/Reproduction\" />\r\n   <permits rdf:resource=\"http://web.resource.org/cc/Distribution\" />\r\n   <requires rdf:resource=\"http://web.resource.org/cc/Notice\" />\r\n   <requires rdf:resource=\"http://web.resource.org/cc/Attribution\" />\r\n   <permits rdf:resource=\"http://web.resource.org/cc/DerivativeWorks\" />\r\n</License>\r\n\r\n</rdf:RDF>\r\n\r\n-->\r\n',
desc_text = 'Diese Lizenz erlaubt anderen, Ihr Werk/Ihren Inhalt zu verbreiten, zu remixen, zu verbessern und darauf aufzubauen, auch kommerziell, solange Sie als Urheber des Originals genannt werden. Dies ist die freieste CC-Lizenz, empfohlen für maximale Verbreitung und Nutzung des lizenzierten Materials.',
link_licence = 'http://creativecommons.org/licenses/by/3.0/de/deed.de',
link_logo = 'http://i.creativecommons.org/l/by/3.0/de/88x31.png',
link_sign = ''
WHERE id = 4;

UPDATE document_licences SET
desc_markup = '<!--Creative Commons License--><a rel=\"license\" href=\"http://creativecommons.org/licenses/by-sa/3.0/de/\"><img alt=\"Creative Commons License\" style=\"border-width: 0\" src=\"http://creativecommons.org/images/public/somerights20.gif\"/></a><br/>Dieser Inhalt ist unter einer <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-sa/3.0/de/\">Creative Commons-Lizenz</a> lizenziert.<!--/Creative Commons License--><!-- <rdf:RDF xmlns=\"http://web.resource.org/cc/\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" xmlns:rdfs=\"http://www.w3.org/2000/01/rdf-schema#\">\r\n	<Work rdf:about=\"\">\r\n		<license rdf:resource=\"http://creativecommons.org/licenses/by-sa/3.0/de/\" />\r\n	</Work>\r\n	<License rdf:about=\"http://creativecommons.org/licenses/by-sa/3.0/de/\"><permits rdf:resource=\"http://web.resource.org/cc/Reproduction\"/><permits rdf:resource=\"http://web.resource.org/cc/Distribution\"/><requires rdf:resource=\"http://web.resource.org/cc/Notice\"/><requires rdf:resource=\"http://web.resource.org/cc/Attribution\"/><permits rdf:resource=\"http://web.resource.org/cc/DerivativeWorks\"/><requires rdf:resource=\"http://web.resource.org/cc/ShareAlike\"/></License></rdf:RDF> -->',
desc_text = 'Diese Lizenz erlaubt es anderen, Ihr Werk/Ihren Inhalt zu verbreiten, zu remixen, zu verbessern und darauf aufzubauen, auch kommerziell, solange Sie als Urheber des Originals genannt werden und die auf Ihrem Werk/Inhalt basierenden neuen Werke unter denselben Bedingungen veröffentlicht werden. Diese Lizenz wird oft mit \"Copyleft\"-Lizenzen im Bereich freier und Open Source Software verglichen. Alle neuen Werke/Inhalte, die auf Ihrem aufbauen, werden unter derselben Lizenz stehen, also auch kommerziell nutzbar sein. Dies ist die Lizenz, die auch von der Wikipedia eingesetzt wird, empfohlen für Material, für das eine Einbindung von Wikipedia-Material oder anderen so lizenzierten Inhalten sinnvoll sein kann. ',
link_licence = 'http://creativecommons.org/licenses/by-sa/3.0/de/deed.de',
link_logo = 'http://i.creativecommons.org/l/by-sa/3.0/de/88x31.png',
link_sign = ''
WHERE id = 5;

UPDATE document_licences SET
desc_markup = '<!--Creative Commons License--><a rel=\"license\" href=\"http://creativecommons.org/licenses/by-nd/3.0/de/\"><img alt=\"Creative Commons License\" style=\"border-width: 0\" src=\"http://creativecommons.org/images/public/somerights20.gif\"/></a><br/>Dieser Inhalt ist unter einer <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-nd/3.0/de/\">Creative Commons-Lizenz</a> lizenziert.<!--/Creative Commons License--><!-- <rdf:RDF xmlns=\"http://web.resource.org/cc/\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" xmlns:rdfs=\"http://www.w3.org/2000/01/rdf-schema#\">\r\n	<Work rdf:about=\"\">\r\n		<license rdf:resource=\"http://creativecommons.org/licenses/by-nd/3.0/de/\" />\r\n	</Work>\r\n	<License rdf:about=\"http://creativecommons.org/licenses/by-nd/3.0/de/\"><permits rdf:resource=\"http://web.resource.org/cc/Reproduction\"/><permits rdf:resource=\"http://web.resource.org/cc/Distribution\"/><requires rdf:resource=\"http://web.resource.org/cc/Notice\"/><requires rdf:resource=\"http://web.resource.org/cc/Attribution\"/></License></rdf:RDF> -->',
desc_text = 'Diese Lizenz erlaubt anderen die Weiterverbreitung Ihres Werkes/Inhaltes, kommerziell wie nicht-kommerziell, solange dies ohne Veränderungen und vollständig geschieht und Sie als Urheber genannt werden. ',
link_licence = 'http://creativecommons.org/licenses/by-nd/3.0/de/',
link_logo = 'http://i.creativecommons.org/l/by-nd/3.0/de/88x31.png',
link_sign = ''
WHERE id = 6;

UPDATE document_licences SET
desc_markup = '<!--Creative Commons License--><a rel=\"license\" href=\"http://creativecommons.org/licenses/by-nc-sa/3.0/de/\"><img alt=\"Creative Commons License\" style=\"border-width: 0\" src=\"http://creativecommons.org/images/public/somerights20.gif\"/></a><br/>Dieser Inhalt ist unter einer <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-nc-sa/3.0/de/\">Creative Commons-Lizenz</a> lizenziert.<!--/Creative Commons License--><!-- <rdf:RDF xmlns=\"http://web.resource.org/cc/\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" xmlns:rdfs=\"http://www.w3.org/2000/01/rdf-schema#\">\r\n	<Work rdf:about=\"\">\r\n		<license rdf:resource=\"http://creativecommons.org/licenses/by-nc-sa/3.0/de/\" />\r\n	</Work>\r\n	<License rdf:about=\"http://creativecommons.org/licenses/by-nc-sa/3.0/de/\"><permits rdf:resource=\"http://web.resource.org/cc/Reproduction\"/><permits rdf:resource=\"http://web.resource.org/cc/Distribution\"/><requires rdf:resource=\"http://web.resource.org/cc/Notice\"/><requires rdf:resource=\"http://web.resource.org/cc/Attribution\"/><prohibits rdf:resource=\"http://web.resource.org/cc/CommercialUse\"/><permits rdf:resource=\"http://web.resource.org/cc/DerivativeWorks\"/><requires rdf:resource=\"http://web.resource.org/cc/ShareAlike\"/></License></rdf:RDF> -->',
desc_text = 'Diese Lizenz erlaubt es anderen, Ihr Werk/Ihren Inhalt zu verbreiten, zu remixen, zu verbessern und darauf aufzubauen, allerdings nur nicht-kommerziell und solange Sie als Urheber des Originals genannt werden und die auf Ihrem Werk/Inhalt basierenden neuen Werke unter denselben Bedingungen veröffentlicht werden.',
link_licence = 'http://creativecommons.org/licenses/by-nc-sa/3.0/de',
link_logo = 'http://i.creativecommons.org/l/by-nc-sa/3.0/de/88x31.png',
link_sign = ''
WHERE id = 7;

INSERT INTO document_licences (`active`, `comment_internal`, `desc_markup`, `desc_text`, `language`, `link_licence`, `link_logo`, `link_sign`, `mime_type`, `name_long`, `pod_allowed`, `sort_order`) VALUES (1, '', '<!--\r\n Creative Commons-Lizenzvertrag -->\r\n<a rel=\"license\" \r\nhref=\"http://creativecommons.org/licenses/by-nc/3.0/de/\"><img \r\nalt=\"Creative Commons-Lizenzvertrag\" border=\"0\" \r\nsrc=\"http://creativecommons.org/images/public/somerights20.gif\" \r\n/></a><br />\r\nDiese Inhalt ist unter einer <a rel=\"license\" \r\nhref=\"http://creativecommons.org/licenses/by-nc/3.0/de/\">Creative \r\nCommons-Lizenz</a> lizenziert.\r\n<!-- /Creative Commons-Lizenzvertrag -->\r\n\r\n\r\n<!--\r\n\r\n<rdf:RDF xmlns=\"http://web.resource.org/cc/\"\r\n    xmlns:dc=\"http://purl.org/dc/elements/1.1/\"\r\n    xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\">\r\n<Work rdf:about=\"\">\r\n   <dc:type rdf:resource=\"http://purl.org/dc/dcmitype/Text\" />\r\n   <license \r\nrdf:resource=\"http://creativecommons.org/licenses/by-nc/3.0/de/\" \r\n/>\r\n</Work>\r\n\r\n<License \r\nrdf:about=\"http://creativecommons.org/licenses/by-nc/3.0/de/\">\r\n   <permits rdf:resource=\"http://web.resource.org/cc/Reproduction\" \r\n/>\r\n   <permits rdf:resource=\"http://web.resource.org/cc/Distribution\" \r\n/>\r\n   <requires rdf:resource=\"http://web.resource.org/cc/Notice\" />\r\n   <requires rdf:resource=\"http://web.resource.org/cc/Attribution\" \r\n/>\r\n   <prohibits rdf:resource=\"http://web.resource.org/cc/CommercialUse\"\r\n />\r\n</License>\r\n\r\n</rdf:RDF>\r\n\r\n-->', 'Diese Lizenz erlaubt es anderen, Ihr Werk/Ihren Inhalt zu verbreiten, zu remixen, zu verbessern und darauf aufzubauen, allerdings nur nicht-kommerziell. Und obwohl auch bei den auf Ihrem Werk/Inhalt basierenden neuen Werken Ihr Name mit genannt werden muss und sie nur nicht-kommerziell verwendet werden dürfen, müssen diese neuen Werke nicht unter denselben Bedingungen lizenziert werden.', 'deu', 'http://creativecommons.org/licenses/by-nc/3.0/de/deed.de', 'http://i.creativecommons.org/l/by-nc/3.0/de/88x31.png', '', 'text/html', 'Creative Commons - Namensnennung-Nicht kommerziell', 1, 50);

-- if standardized enrichment_key_names from opus3-migration are already used set them to a temporary name

UPDATE `document_enrichments` SET `key_name` = 'TempClassRvk' WHERE `key_name` = 'ClassRvk';
UPDATE `document_enrichments` SET `key_name` = 'TempContributorsName' WHERE `key_name` = 'ContributorsName';
UPDATE `document_enrichments` SET `key_name` = 'TempSourceSwb' WHERE `key_name` = 'SourceSwb';
UPDATE `document_enrichments` SET `key_name` = 'TempSourceTitle' WHERE `key_name` = 'SourceTitle';
UPDATE `document_enrichments` SET `key_name` = 'TempSubjectUncontrolledGerman' WHERE `key_name` = 'SubjectUncontrolledGerman';
UPDATE `document_enrichments` SET `key_name` = 'TempSubjectUncontrolledEnglish' WHERE `key_name` = 'SubjectUncontrolledEnglish';
UPDATE `document_enrichments` SET `key_name` = 'TempSubjectSwd' WHERE `key_name` = 'SubjectSwd';

-- change enrichment_key_names according to opus4-name konventionts

UPDATE `document_enrichments` SET `key_name` = 'ClassRvk' WHERE `key_name` = 'rvk';
UPDATE `document_enrichments` SET `key_name` = 'ContributorsName' WHERE `key_name` = 'contributor';
UPDATE `document_enrichments` SET `key_name` = 'SourceSwb' WHERE `key_name` = 'source_swb';
UPDATE `document_enrichments` SET `key_name` = 'SourceTitle' WHERE `key_name` = 'source';
UPDATE `document_enrichments` SET `key_name` = 'SubjectUncontrolledGerman' WHERE `key_name` = 'subject_uncontrolled_german';
UPDATE `document_enrichments` SET `key_name` = 'SubjectUncontrolledEnglish' WHERE `key_name` = 'subject_uncontrolled_english';
UPDATE `document_enrichments` SET `key_name` = 'SubjectSwd' WHERE `key_name` = 'subject_swd';