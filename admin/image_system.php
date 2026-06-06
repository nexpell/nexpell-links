<?php

if (!defined('NX_LINKS_SYSTEM')) {
    define('NX_LINKS_SYSTEM', true);

    // Basis-Pfade
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');
    }

    if (!defined('BASE_URL')) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . '/');
    }

    if (!defined('LINKS_IMG_DIR')) {
        define('LINKS_IMG_DIR', BASE_PATH . "includes/plugins/links/images/");
    }

    if (!is_dir(LINKS_IMG_DIR)) {
        mkdir(LINKS_IMG_DIR, 0755, true);
    }

    function slugify($text)
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        return trim($text, '-');
    }

    function nx_download_image_raw(string $url): ?string
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => "Mozilla/5.0",
        ]);

        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300 && $raw) {
            return $raw;
        }
        return null;
    }

    function nx_save_image_original(string $image_url, string $title = "", ?string $old = null): ?string
    {
        $raw = nx_download_image_raw($image_url);
        if (!$raw) {
            return null;
        }

        $extension = strtolower(pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
            $extension = 'jpg';
        }

        $slug = slugify($title);
        $filename = "linkimg_" . $slug . "_" . time() . "." . $extension;

        $filepath = LINKS_IMG_DIR . $filename;

        file_put_contents($filepath, $raw);

        if ($old && file_exists(BASE_PATH . $old) && !str_contains($old, 'default_thumb')) {
            @unlink(BASE_PATH . $old);
        }

        return "includes/plugins/links/images/" . $filename;
    }

    function cleanup_link_images(mysqli $db): array
    {
        $valid = [];
        $q = $db->query("SELECT image FROM plugins_links");
        while ($row = $q->fetch_assoc()) {
            if (!empty($row['image'])) {
                $valid[] = BASE_PATH . $row['image'];
            }
        }

        $deleted = [];

        foreach (glob(LINKS_IMG_DIR . "*") as $file) {
            if (str_contains($file, 'default_thumb')) continue;

            if (!in_array($file, $valid)) {
                @unlink($file);
                $deleted[] = basename($file);
            }
        }

        return $deleted;
    }

    function process_after_save(mysqli $db): void
    {
        cleanup_link_images($db);
    }
}
