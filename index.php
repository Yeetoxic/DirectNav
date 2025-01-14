<?php
// Load configuration
$configFile = 'zDirectNav/config.json';
if (!file_exists($configFile)) {
    // Default configuration if file doesn't exist
    file_put_contents($configFile, json_encode(['port' => 9000], JSON_PRETTY_PRINT));
}
$config = json_decode(file_get_contents($configFile), true);

// Handle configuration updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['port'])) {
    $newPort = intval($_POST['port']);
    if ($newPort > 0 && $newPort <= 65535) { // Validate port range
        $config['port'] = $newPort;
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)); // Save new port
        $portUpdated = true;
    } else {
        $portError = "Invalid port. Please enter a number between 1 and 65535.";
    }
}

// Current port
$currentPort = $config['port'];

// Determine current directory path
$currentPath = isset($_GET['path']) ? realpath($_GET['path']) : realpath('.');
$rootPath = realpath('.');
$directoryName = basename($currentPath);

// Prevent navigating outside the root directory
if (strpos($currentPath, $rootPath) !== 0) {
    $currentPath = $rootPath;
}

// Retrieve directory contents
$files = scandir($currentPath);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php
        if ($directoryName === 'html') {
            echo "root - DirectNav";
        } else {
            echo htmlspecialchars($directoryName) . " - DirectNav";
        }
        ?>
    </title>
    <style>
        body {
            font-family: monospace;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
        }
        header {
            padding: 15px 20px;
            font-size: 1.2rem;
            text-align: center;
            border-radius: 8px 8px 0 0;
            background-color: #007BFF;
            color: #fff;
        }
        .container {
            width: 80%;
            max-width: 800px;
            border-radius: 8px;
            box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .content {
            padding: 20px;
            overflow-y: auto;
            flex-grow: 1;
        }
        .info {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.9rem;
            background-color: #333;
            color: #bbb;
        }
        ul {
            list-style-type: none;
            padding-left: 0;
            margin: 0;
            max-height: 60vh;
            overflow-y: auto;
            border-top: 1px solid #444;
            border-bottom: 1px solid #444;
        }
        ul::-webkit-scrollbar {
            width: 8px;
        }
        ul::-webkit-scrollbar-thumb {
            background-color: #444;
            border-radius: 4px;
        }
        ul::-webkit-scrollbar-track {
            background-color: #222;
        }
        li {
            display: flex;
            align-items: center;
            margin: 8px 0;
            padding: 10px;
            border-radius: 4px;
            background-color: #333;
            transition: background-color 0.2s, transform 0.1s;
        }
        li:hover {
            background-color: #3e3e3e;
            transform: scale(1.01);
        }
        .icon {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }
        a {
            color: #eaeaea;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        a:visited {
            color: #eaeaea; /* Prevent purple visited links */
        }
        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 12px;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        footer {
            text-align: center;
            padding: 10px;
            font-size: 0.8rem;
            background-color: #222;
            color: #777;
            border-top: 1px solid #444;
        }
    </style>
    <?php
    // Load selected theme
    $theme = isset($_GET['theme']) ? $_GET['theme'] : 'default.css';
    echo '<link rel="stylesheet" href="zDirectNav/themes/' . htmlspecialchars($theme) . '">';
    ?>
</head>
<body>
    <div class="container">
        <header>
            <form method="GET" style="text-align: right;">
                <label for="theme">Select Theme:</label>
                <select name="theme" id="theme" onchange="this.form.submit()">
                    <?php
                    // Scan the themes directory and list CSS files
                    $themeDir = 'zDirectNav/themes';
                    if (is_dir($themeDir)) {
                        $themeFiles = array_filter(scandir($themeDir), function ($file) use ($themeDir) {
                            return is_file($themeDir . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'css';
                        });

                        foreach ($themeFiles as $file) {
                            $selected = ($file === $theme) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($file) . '" ' . $selected . '>' . ucfirst(pathinfo($file, PATHINFO_FILENAME)) . '</option>';
                        }
                    }
                    ?>
                    <?php
                    // Preserve the current path in the theme selection form
                    if (isset($_GET['path'])) {
                        echo '<input type="hidden" name="path" value="' . htmlspecialchars($_GET['path']) . '">';
                    }
                    ?>
                </select>
            </form>
            Directory Listing for "<?php
            if ($directoryName == 'html') {
                echo "root";
            } else {
                echo htmlspecialchars($directoryName);
            }
            ?>"
        </header>
        <div class="content">
            <?php
            // Display Back Button
            if ($currentPath !== $rootPath) {
                $parentPath = dirname($currentPath);
                echo '<a href="?path=' . urlencode($parentPath) . '&theme=' . htmlspecialchars($theme) . '" class="back-button">← Back to Parent Directory</a>';
            } else {
                echo '<p>You are at the root directory.</p>';
            }

            echo '<div class="info">';
            echo '<p><strong>Current Directory:</strong> ' . htmlspecialchars($currentPath) . '</p>';
            echo '<p>Total Files: ' . count(array_filter($files, fn($f) => !is_dir($currentPath . '/' . $f))) . '</p>';
            echo '<p>Total Folders: ' . count(array_filter($files, fn($f) => is_dir($currentPath . '/' . $f))) . '</p>';
            echo '</div>';
            ?>

            <ul>
            <?php
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    $relativePath = str_replace($rootPath, '', $currentPath . DIRECTORY_SEPARATOR . $file);
                    $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);

                    if (is_dir($currentPath . DIRECTORY_SEPARATOR . $file)) {
                        echo '<li><span class="icon folder">📁</span><a href="?path=' . urlencode($currentPath . DIRECTORY_SEPARATOR . $file) . '&theme=' . htmlspecialchars($theme) . '">' . htmlspecialchars($file) . '</a></li>';
                    } elseif ($file === basename(__FILE__) && realpath($currentPath) === $rootPath) {
                        echo '<li><span class="icon file">📄</span>' . htmlspecialchars($file) . ' <span class="currently-open">(currently open)</span></li>';
                    } else {
                        echo '<li><span class="icon file">📄</span><a href="http://php84.local:9000/' . htmlspecialchars($relativePath) . '">' . htmlspecialchars($file) . '</a></li>';
                    }
                }
                ?>
            </ul>
        </div>
        <div class="content">
            <h2>Configuration</h2>
            <form method="POST">
                <label for="port">Change Port:</label>
                <input type="number" id="port" name="port" value="<?php echo htmlspecialchars($currentPort); ?>" required>
                <button type="submit">Save Port</button>
            </form>
            <?php
            if (isset($portUpdated) && $portUpdated) {
                echo '<p class="success-message">Port updated successfully to ' . htmlspecialchars($currentPort) . '!</p>';
            }
            if (isset($portError)) {
                echo '<p class="error-message" style="color: red;">' . htmlspecialchars($portError) . '</p>';
            }
            ?>
        </div>
        <footer>
            &copy; <?php echo date('Y'); ?> Danil Vilmont
        </footer>
    </div>
</body>
</html>
