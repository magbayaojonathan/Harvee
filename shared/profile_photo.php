<?php

function harvee_profile_photo_url(string $stored_path, string $relative_prefix = '../'): string
{
    $stored_path = trim($stored_path);
    if ($stored_path === '' || $stored_path === 'default-avatar.png') {
        return '';
    }

    if (strpos($stored_path, 'http://') === 0 || strpos($stored_path, 'https://') === 0 || ($stored_path[0] ?? '') === '/') {
        return $stored_path;
    }

    $repo_root = dirname(__DIR__);
    $fs_path = $repo_root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($stored_path, '/'));
    if (!is_file($fs_path)) {
        return '';
    }

    return $relative_prefix . ltrim($stored_path, '/');
}

function harvee_handle_profile_photo_upload(PDO $pdo, int $user_id, array $file, string $old_stored_path = ''): array
{
    if ($user_id <= 0) {
        return ['ok' => false, 'message' => 'Invalid user.', 'path' => null];
    }

    if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => 'Please choose a valid image file.', 'path' => null];
    }

    $tmp_name = (string)($file['tmp_name'] ?? '');
    $size = (int)($file['size'] ?? 0);

    if ($tmp_name === '' || !is_file($tmp_name)) {
        return ['ok' => false, 'message' => 'Upload failed. Please try again.', 'path' => null];
    }

    if ($size <= 0 || $size > 3 * 1024 * 1024) {
        return ['ok' => false, 'message' => 'Image must be less than 3MB.', 'path' => null];
    }

    $image_info = @getimagesize($tmp_name);
    if (!$image_info || !isset($image_info[2])) {
        return ['ok' => false, 'message' => 'Invalid image file.', 'path' => null];
    }

    $type = (int)$image_info[2];
    $ext = '';
    if ($type === IMAGETYPE_JPEG) {
        $ext = 'jpg';
    } elseif ($type === IMAGETYPE_PNG) {
        $ext = 'png';
    } elseif ($type === IMAGETYPE_WEBP) {
        $ext = 'webp';
    } else {
        return ['ok' => false, 'message' => 'Only JPG, PNG, or WEBP images are allowed.', 'path' => null];
    }

    $repo_root = dirname(__DIR__);
    $upload_dir = $repo_root . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile_photos';
    if (!is_dir($upload_dir)) {
        if (!@mkdir($upload_dir, 0775, true) && !is_dir($upload_dir)) {
            return ['ok' => false, 'message' => 'Server error: could not create upload folder.', 'path' => null];
        }
    }

    $rand = bin2hex(random_bytes(6));
    $filename = 'user_' . $user_id . '_' . date('Ymd_His') . '_' . $rand . '.' . $ext;
    $dest_fs = $upload_dir . DIRECTORY_SEPARATOR . $filename;

    if (!@move_uploaded_file($tmp_name, $dest_fs)) {
        return ['ok' => false, 'message' => 'Failed to save image. Please try again.', 'path' => null];
    }

    $stored_path = 'assets/uploads/profile_photos/' . $filename;

    try {
        $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $stmt->execute([$stored_path, $user_id]);
    } catch (PDOException $e) {
        @unlink($dest_fs);
        error_log('Profile photo update error: ' . $e->getMessage());
        return ['ok' => false, 'message' => 'Database error: unable to save profile photo.', 'path' => null];
    }

    $old_stored_path = trim($old_stored_path);
    if ($old_stored_path !== '' && $old_stored_path !== 'default-avatar.png' && strpos($old_stored_path, 'assets/uploads/profile_photos/') === 0) {
        $old_fs = $repo_root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($old_stored_path, '/'));
        if (is_file($old_fs)) {
            @unlink($old_fs);
        }
    }

    return ['ok' => true, 'message' => 'Profile photo updated successfully!', 'path' => $stored_path];
}

