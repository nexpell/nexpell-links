<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\LanguageService;
use nexpell\AccessControl;
global $_database, $languageService;

if (isset($languageService) && $languageService instanceof LanguageService) {
    $languageService->readModule('links');
}

AccessControl::checkAdminAccess('links');

require_once __DIR__ . '/image_system.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? null);
$id     = $_GET['id'] ?? null;

$msg = "";

// Kategorien laden
$categories = [];
$res = $_database->query("SELECT id, title FROM plugins_links_categories ORDER BY title");
while ($row = $res->fetch_assoc()) {
    $categories[$row['id']] = $row['title'];
}

// DELETE
if ($action === 'delete' && $id) {
    $stmt = $_database->prepare("SELECT image, title FROM plugins_links WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($oldImage, $linkTitle);
    $stmt->fetch();
    $stmt->close();

    if ($oldImage && file_exists(BASE_PATH . $oldImage) && !str_contains($oldImage, 'default_thumb')) {
        @unlink(BASE_PATH . $oldImage);
    }

    $stmt = $_database->prepare("DELETE FROM plugins_links WHERE id=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        nx_audit_delete('admin_links', (string)$id, $linkTitle ?? (string)$id, 'admincenter.php?site=admin_links');
        nx_redirect('admincenter.php?site=admin_links', 'success', 'alert_deleted', false);
    }
    $stmt->close();

    nx_redirect('admincenter.php?site=admin_links', 'danger', 'alert_not_found', false);
}

// ADD / EDIT
if (in_array($action, ['add', 'edit'], true) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = trim($_POST['title']);
    $url         = trim($_POST['url']);
    $description = trim($_POST['description']);
    $category_id = (int)$_POST['category_id'];
    $target      = $_POST['target'] ?? '_blank';
    $visible     = isset($_POST['visible']) ? 1 : 0;

    $imagePath   = null;

    // Upload
    if (!empty($_FILES['image']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'], true)) {
            $ext = 'jpg';
        }

        $filename = "linkimg_" . time() . "_" . uniqid() . "." . $ext;
        $dest = LINKS_IMG_DIR . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            $imagePath = "includes/plugins/links/images/" . $filename;
        } else {
            nx_alert('danger', 'alert_upload_failed', false);
        }
    }

    // OG-Fetch-Fallback
    if (!$imagePath && isset($_POST['image_from_og']) && $_POST['image_from_og'] !== '') {
        $imagePath = $_POST['image_from_og'];
    }

    // wirklich letzter Fallback
    if (!$imagePath) {
        $imagePath = 'includes/plugins/links/images/default_thumb.jpg';
    }

    if ($action === 'add') {

        $stmt = $_database->prepare("
            INSERT INTO plugins_links
            (title, url, description, category_id, image, target, visible)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssissi", $title, $url, $description, $category_id, $imagePath, $target, $visible);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $newId = (int)($_database->insert_id ?? 0);
            nx_audit_create('admin_links', (string)$newId, $title, 'admincenter.php?site=admin_links');
            nx_redirect('admincenter.php?site=admin_links', 'success', 'alert_saved', false);
        }
        $stmt->close();

    } else {

        $stmt = $_database->prepare("SELECT image FROM plugins_links WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($oldImg);
        $stmt->fetch();
        $stmt->close();

        if ($imagePath && $imagePath !== $oldImg) {
            if ($oldImg && !str_contains($oldImg, 'default_thumb') && file_exists(BASE_PATH . $oldImg)) {
                @unlink(BASE_PATH . $oldImg);
            }
        } else {
            $imagePath = $oldImg;
        }

        $stmt = $_database->prepare("
            UPDATE plugins_links
            SET title=?, url=?, description=?, category_id=?, image=?, target=?, visible=?
            WHERE id=?
        ");
        $stmt->bind_param("sssissii", $title, $url, $description, $category_id, $imagePath, $target, $visible, $id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            nx_audit_update('admin_links', (string)$id, true, $title, 'admincenter.php?site=admin_links');
            nx_redirect('admincenter.php?site=admin_links', 'success', 'alert_saved', false);
        }
        $stmt->close();
    }

    process_after_save($_database);

    nx_redirect('admincenter.php?site=admin_links', 'success', 'alert_saved', false);
}

// Formular
if (in_array($action, ['add', 'edit'])) {
    $link = [
        'title' => '',
        'url' => '',
        'description' => '',
        'category_id' => '',
        'image' => '',
        'target' => '_blank',
        'visible' => 1,
    ];

    if ($action === 'edit' && $id) {
        $stmt = $_database->prepare("SELECT * FROM plugins_links WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $link = $result->fetch_assoc();
    }
?>

<div class="card shadow-sm mt-4">
    <div class="card-header">
        <div class="card-title">
            <i class="bi bi-link"></i> <span><?= $languageService->get('manage_links') ?></span>
            <small class="text-muted"><?= $languageService->get('add') ?></small>
        </div>
    </div>

    <div class="card-body">
  <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
    <input type="hidden" name="action" value="<?= htmlspecialchars($action) ?>">
    <input type="hidden" name="image_from_og" value="">

    <div class="row g-3">
      <!-- Titel -->
      <div class="col-12">
        <label for="title" class="form-label"><?= $languageService->get('label_title') ?></label>
        <input
          type="text"
          class="form-control"
          id="title"
          name="title"
          value="<?= htmlspecialchars($link['title']) ?>"
          required
        >
      </div>

      <!-- URL -->
      <div class="col-12">
        <label for="url" class="form-label"><?= $languageService->get('url') ?></label>
        <input
          type="url"
          class="form-control"
          id="url"
          name="url"
          value="<?= htmlspecialchars($link['url']) ?>"
          required
        >
      </div>

      <!-- Beschreibung -->
      <div class="col-12">
        <label for="description" class="form-label"><?= $languageService->get('description') ?></label>
        <textarea
          class="form-control"
          id="description"
          name="description"
          rows="3"
        ><?= htmlspecialchars($link['description']) ?></textarea>
      </div>

      <!-- Kategorie + Ziel -->
      <div class="col-12 col-lg-6">
        <label for="category_id" class="form-label"><?= $languageService->get('label_category') ?></label>
        <select id="category_id" name="category_id" class="form-select" required>
          <option value=""><?= $languageService->get('select_choose') ?></option>
          <?php foreach ($categories as $cid => $ctitle): ?>
            <option value="<?= $cid ?>" <?= ($cid == $link['category_id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($ctitle) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-lg-6">
        <label for="target" class="form-label"><?= $languageService->get('label_target') ?></label>
        <select id="target" name="target" class="form-select">
          <option value="_blank" <?= $link['target'] === '_blank' ? 'selected' : '' ?>><?= $languageService->get('label_new_tab') ?></option>
          <option value="_self"  <?= $link['target'] === '_self'  ? 'selected' : '' ?>><?= $languageService->get('label_same_tab') ?></option>
        </select>
      </div>

      <!-- OG Preview + Upload -->
      <div class="col-12">
        <label class="form-label"><?= $languageService->get('og_image_preview') ?></label>

        <div class="d-flex align-items-start gap-3 flex-wrap">
          <div
            id="og-preview"
            class="border rounded p-2 bg-light"
            style="min-width:170px; min-height:110px;"
          >
            <?php if (!empty($link['image']) && file_exists(BASE_PATH . $link['image'])): ?>
              <img
                src="/<?= htmlspecialchars($link['image']) ?>"
                alt="OG Preview"
                class="img-fluid rounded"
                style="max-width:150px;"
              >
            <?php else: ?>
              <p class="text-muted mb-0"><?= $languageService->get('no_url_entered') ?></p>
            <?php endif; ?>
          </div>

          <div class="flex-grow-1">
            <label for="image" class="form-label"><?= $languageService->get('label_upload_picture') ?></label>
            <input class="form-control" type="file" id="image" name="image" accept="image/*">
            <div class="form-text"><?= $languageService->get('formtext_og_picture') ?></div>
          </div>
        </div>
      </div>

      <!-- Sichtbar als Toggle -->
      <div class="col-12">
        <div class="form-check form-switch">
          <!-- Hidden fallback, falls Switch ausgeschaltet => 0 wird gesendet -->
          <input type="hidden" name="visible" value="0">
          <input
            class="form-check-input"
            type="checkbox"
            role="switch"
            id="visible"
            name="visible"
            value="1"
            <?= !empty($link['visible']) ? 'checked' : '' ?>
          >
          <label class="form-check-label" for="visible"><?= $languageService->get('label_visible') ?></label>
        </div>
      </div>

      <!-- Buttons -->
      <div class="col-12 d-flex gap-2 justify-content-start pt-2">
        <button type="submit" class="btn btn-primary"><?= $languageService->get('save') ?></button>
      </div>
    </div>
  </form>
    </div>
</div>
</div>

<script>
document.getElementById("url").addEventListener("blur", function() {

    let url = this.value.trim();
    if (!url.length) return;

    const preview = document.getElementById("og-preview");
    preview.innerHTML = "Lade Metadaten…";

    fetch("/includes/plugins/links/admin/og_parser.php?url=" + encodeURIComponent(url))
        .then(r => r.json())
        .then(data => {
            if (!data.og_image) {
                preview.innerHTML = "Kein OG-Bild gefunden.";
                return;
            }

            preview.innerHTML = "Bild wird geladen…";

            fetch("/includes/plugins/links/admin/og_fetch.php?img="
                + encodeURIComponent(data.og_image)
                + "&title=" + encodeURIComponent(document.querySelector('input[name=title]').value)
            )
                .then(r => r.json())
                .then(f => {
                    if (f.success) {
                        preview.innerHTML = '<img src="/' + f.file + '" style="max-width:150px">';
                        document.querySelector("input[name=image_from_og]").value = f.file;
                    } else {
                        preview.innerHTML = "Bild konnte nicht gespeichert werden.";
                    }
                });
        });
});
</script>

<?php
exit;
}

// Übersicht – LISTE
$msg = '';
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
}

// Query: Links mit Kategorie und Klickanzahl laden
$res = $_database->query("
    SELECT l.*, c.title AS category,
        COALESCE(k.click_count, 0) AS clicks
    FROM plugins_links l
    LEFT JOIN plugins_links_categories c ON l.category_id = c.id
    LEFT JOIN (
        SELECT itemID, COUNT(*) AS click_count
        FROM link_clicks
        WHERE plugin = 'links'
        GROUP BY itemID
    ) k ON l.id = k.itemID
    ORDER BY c.title, l.title
");
?>

<div class="card shadow-sm mt-4">
    <div class="card-header">
        <div class="card-title">
            <i class="bi bi-link"></i> <?= $languageService->get('manage_links') ?>
            <small class="text-muted"><?= $languageService->get('overview') ?></small>
        </div>
    <div>
        <a href="admincenter.php?site=admin_links&action=add" class="btn btn-secondary">
            <?= $languageService->get('add') ?>
        </a>
    </div>
</div>

<div class="card-body">

<div class="table-responsive">
<table class="table">
    <thead>
        <tr>
            <th><?= $languageService->get('image') ?></th>
            <th><?= $languageService->get('title') ?></th>
            <th><?= $languageService->get('url') ?></th>
            <th><?= $languageService->get('category') ?></th>
            <th><?= $languageService->get('clicks_per_day') ?></th>
            <th><?= $languageService->get('visible') ?></th>
            <th width="15%"><?= $languageService->get('actions') ?></th>
        </tr>
    </thead>
    <tbody>
            <?php while ($row = $res->fetch_assoc()): ?>
                <?php 
                    $createdTimestamp = isset($row['created_at']) ? strtotime($row['created_at']) : time();
                    $days = max(1, round((time() - $createdTimestamp) / (60 * 60 * 24)));
                    $perday = round($row['clicks'] / $days, 2);
                ?>
                <tr>
                    <td>
                        <?php if ($row['image'] && file_exists(BASE_PATH . $row['image'])): ?>
                            <img src="/<?= $row['image'] ?>" 
                                 alt="Bild"
                                 style="max-width: 120px; max-height: 50px; width:auto; height:auto; object-fit:contain;">
                        <?php else: ?>
                            <span class="text-muted"><?= $languageService->get('info_no_picture') ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><a href="<?= htmlspecialchars($row['url']) ?>" target="_blank"><?= htmlspecialchars($row['url']) ?></a></td>

                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td><?= (int)$row['clicks'] ?> (Ø <?= $perday ?>/Tag)</td>

                    <td><?= $row['visible'] ? '<span class="badge bg-success">' . $languageService->get('yes') . '</span>' : '<span class="badge bg-danger">' . $languageService->get('no') . '</span>' ?>
                        
                    </td>
                
                    <td>
                    <div class="d-flex flex-nowrap align-items-center gap-2">
                        <a href="admincenter.php?site=admin_links&action=edit&id=<?= (int)$row['id'] ?>" class="btn btn-warning d-inline-flex align-items-center gap-1 w-auto">
                            <i class="bi bi-pencil-square"></i> <?= $languageService->get('edit') ?>
                        </a>
                        <?php
                        $deleteUrl = 'admincenter.php?site=admin_links&action=delete&id=' . (int)$row['id'];
                        ?>
                        <a
                        href="#"
                        class="btn btn-danger d-inline-flex align-items-center gap-1 w-auto"
                        data-bs-toggle="modal"
                        data-bs-target="#confirmDeleteModal"
                        data-confirm-url="<?= htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-trash3"></i> <?= $languageService->get('delete') ?>
                        </a>
                    </div>
                </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
