<?php

global $_database;

safe_query("CREATE TABLE IF NOT EXISTS plugins_links_categories (
  id int(11) NOT NULL AUTO_INCREMENT,
  title varchar(100) NOT NULL,
  icon varchar(100) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

safe_query("CREATE TABLE IF NOT EXISTS plugins_links (
  id int(11) NOT NULL AUTO_INCREMENT,
  title varchar(100) NOT NULL,
  url varchar(255) NOT NULL,
  description text DEFAULT NULL,
  category_id int(11) DEFAULT NULL,
  image varchar(255) DEFAULT NULL,
  target varchar(10) DEFAULT '_blank',
  visible tinyint(1) DEFAULT 1,
  userID int(11) NOT NULL DEFAULT 0,
  updated_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY category_id (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

safe_query("INSERT IGNORE INTO plugins_links_categories (id, title, icon) VALUES
(1, 'Webseiten', 'bi bi-globe'),
(2, 'YouTube-Kanaele', 'bi bi-youtube'),
(3, 'Tools & Dienste', 'bi bi-tools'),
(4, 'Gaming', 'bi bi-controller'),
(5, 'Lernen & Wissen', 'bi bi-book');");

safe_query("INSERT IGNORE INTO plugins_links (id, title, url, description, category_id, image, target, visible, userID, updated_at) VALUES
(1, 'Linus Tech Tips', 'https://www.youtube.com/user/LinusTechTips', 'Technik-Videos rund ums Thema PC', 2, 'includes/plugins/links/images/linkimg_linus-tech-tips_1763315541.jpg', '_blank', 1, 1, '2025-06-01 09:46:22'),
(2, 'PHP Offizielle Webseite', 'https://php.net', 'Offizielle PHP Webseite mit Doku und Downloads', 3, 'includes/plugins/links/images/linkimg_php-offizielle-webseite_1763315522.png', '_blank', 1, 1, '2025-06-01 09:46:22'),
(3, 'GitHub', 'https://github.com', 'Hosting fuer Softwareprojekte mit Git', 3, 'includes/plugins/links/images/linkimg_github_1763315516.png', '_blank', 1, 1, '2025-06-01 09:46:22'),
(4, 'callofduty', 'https://www.callofduty.com', '', 4, 'includes/plugins/links/images/linkimg_callofduty_1763316924.webp', '_blank', 1, 1, '2025-06-01 09:46:22'),
(5, 'all-inkl', 'https://all-inkl.com', 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.', 3, 'includes/plugins/links/images/linkimg_all-inkl_1763315378.svg', '_blank', 1, 1, '2025-06-01 09:46:22'),
(6, 'nexpell', 'https://www.nexpell.de', 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.', 1, 'includes/plugins/links/images/linkimg_nexpell_1763315534.jpg', '_blank', 1, 1, '2025-06-01 09:46:22'),
(7, 'werstreamt.es', 'https://www.werstreamt.es/', '', 3, 'includes/plugins/links/images/linkimg_werstreamt-es_1763315528.png', '_blank', 1, 1, '2025-06-01 09:46:22'),
(8, 'Miley Cyrus', 'https://www.youtube.com/watch?v=CXBFU97X61I&list=RDMMCXBFU97X61I&start_radio=1', 'Miley Cyrus - End of the World', 2, 'includes/plugins/links/images/linkimg_miley-cyrus_1763315550.jpg', '_blank', 1, 1, '2025-06-01 09:46:22');");

safe_query("CREATE TABLE IF NOT EXISTS plugins_links_settings (
  linkssetID int(11) NOT NULL AUTO_INCREMENT,
  links int(11) NOT NULL,
  linkchars int(11) NOT NULL,
  PRIMARY KEY (linkssetID)
) AUTO_INCREMENT=1
  DEFAULT CHARSET=utf8 DEFAULT COLLATE utf8_unicode_ci");

safe_query("INSERT IGNORE INTO plugins_links_settings (linkssetID, links, linkchars) VALUES (1, 4, '300')");

safe_query("
    INSERT IGNORE INTO settings_plugins
        (pluginID, modulname, admin_file, activate, author, website, index_link, hiddenfiles, version, path, status_display, plugin_display, widget_display, delete_display, sidebar)
    VALUES
        ('', 'links', 'admin_links', 1, 'T-Seven', 'https://www.nexpell.de', 'links,admin_links,links_rating', '', '1.0.3.3', 'includes/plugins/links/', 1, 1, 1, 1, 'deactivated')
");

safe_query("
    INSERT IGNORE INTO settings_plugins_lang
        (content_key, language, content, modulname, updated_at)
    VALUES
        ('plugin_name_links', 'de', 'Links', 'links', NOW()),
        ('plugin_name_links', 'en', 'Links', 'links', NOW()),
        ('plugin_name_links', 'it', 'Link', 'links', NOW()),
        ('plugin_info_links', 'de', 'Mit diesem Plugin koennt ihr eure Links anzeigen lassen.', 'links', NOW()),
        ('plugin_info_links', 'en', 'With this plugin you can display your links.', 'links', NOW()),
        ('plugin_info_links', 'it', 'Con questo plugin puoi visualizzare i tuoi link.', 'links', NOW())
    ON DUPLICATE KEY UPDATE
        content = VALUES(content),
        modulname = VALUES(modulname),
        updated_at = VALUES(updated_at)
");

safe_query("
    INSERT IGNORE INTO settings_plugins_installed
        (name, modulname, description, version, author, url, folder, installed_date)
    VALUES
        ('Links', 'links', 'With this plugin you can display your links.', '1.0.3.3', 'nexpell-team', 'https://www.nexpell.de', 'links', NOW())
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        description = VALUES(description),
        version = VALUES(version),
        author = VALUES(author),
        url = VALUES(url),
        folder = VALUES(folder),
        installed_date = NOW()
");

$linkID = 0;
$linkRes = safe_query("
    SELECT linkID FROM navigation_dashboard_links
    WHERE modulname = 'links' AND url = 'admincenter.php?site=admin_links'
    ORDER BY linkID ASC LIMIT 1
");
if ($linkRes && ($linkRow = mysqli_fetch_assoc($linkRes))) {
    $linkID = (int) ($linkRow['linkID'] ?? 0);
} else {
    safe_query("
        INSERT IGNORE INTO navigation_dashboard_links
            (catID, modulname, url, sort)
        VALUES
            (13, 'links', 'admincenter.php?site=admin_links', 1)
    ");
    $linkID = (int) mysqli_insert_id($_database);
}

if ($linkID > 0) {
    safe_query("
        INSERT IGNORE INTO navigation_dashboard_lang
            (content_key, language, content, modulname, updated_at)
        VALUES
            ('nav_link_{$linkID}', 'de', 'Links', 'links', NOW()),
            ('nav_link_{$linkID}', 'en', 'Links', 'links', NOW()),
            ('nav_link_{$linkID}', 'it', 'Link', 'links', NOW())
        ON DUPLICATE KEY UPDATE
            content = VALUES(content),
            modulname = VALUES(modulname),
            updated_at = VALUES(updated_at)
    ");
}

$snavID = 0;
$snavRes = safe_query("
    SELECT snavID FROM navigation_website_sub
    WHERE modulname = 'links' AND url = 'index.php?site=links'
    ORDER BY snavID ASC LIMIT 1
");
if ($snavRes && ($snavRow = mysqli_fetch_assoc($snavRes))) {
    $snavID = (int) ($snavRow['snavID'] ?? 0);
} else {
    safe_query("
        INSERT IGNORE INTO navigation_website_sub
            (mnavID, modulname, url, sort, indropdown, last_modified)
        VALUES
            (5, 'links', 'index.php?site=links', 1, 1, NOW())
    ");
    $snavID = (int) mysqli_insert_id($_database);
}

if ($snavID > 0) {
    safe_query("
        INSERT IGNORE INTO navigation_website_lang
            (content_key, language, content, modulname, updated_at)
        VALUES
            ('nav_sub_{$snavID}', 'de', 'Links', 'links', NOW()),
            ('nav_sub_{$snavID}', 'en', 'Links', 'links', NOW()),
            ('nav_sub_{$snavID}', 'it', 'Link', 'links', NOW())
        ON DUPLICATE KEY UPDATE
            content = VALUES(content),
            modulname = VALUES(modulname),
            updated_at = VALUES(updated_at)
    ");
}

safe_query("
  INSERT IGNORE INTO user_role_admin_navi_rights (id, roleID, type, modulname)
  VALUES ('', 1, 'link', 'links')
");
?>
