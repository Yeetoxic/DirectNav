<?php
// Determine current directory path
$currentPath = isset($_GET['path']) ? realpath($_GET['path']) : realpath('.');
$rootPath = realpath('.');
$directoryName = basename($currentPath);

// Prevent navigating outside the root directory
if ($currentPath === false || strpos($currentPath, $rootPath) !== 0) {
    $currentPath = $rootPath;
}

// Retrieve directory contents
$files = is_readable($currentPath) ? scandir($currentPath) : [];

// Load selected theme
$theme = isset($_GET['theme']) ? $_GET['theme'] : (isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'default.css');
setcookie('theme', $theme, time() + (10 * 365 * 24 * 60 * 60), "/");


// Calculate file/folder sizes
 // Asynchronous Directory Size Calculation
 if (isset($_GET['action']) && $_GET['action'] === 'calculateDirectorySize') {
    header('Content-Type: application/json');

    function getDirectorySize($dir, &$totalFiles, &$totalFolders) {
        $size = 0;
        $queue = [$dir];
    
        while ($queue) {
            $currentDir = array_pop($queue);
            $files = scandir($currentDir);
    
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
    
                $filePath = $currentDir . DIRECTORY_SEPARATOR . $file;
    
                if (is_file($filePath)) {
                    $size += filesize($filePath);
                    $totalFiles++;
                } elseif (is_dir($filePath)) {
                    $totalFolders++;
                    $queue[] = $filePath; // Add to queue for further processing
                }
            }
        }
    
        return $size;
    }    

    $totalFiles = 0;
    $totalFolders = 0;

    if ($currentPath && strpos($currentPath, $rootPath) === 0) {
        $totalSize = getDirectorySize($currentPath, $totalFiles, $totalFolders);
    
        function formatSize($size) {
            $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
            $index = 0;
    
            while ($size >= 1024 && $index < count($units) - 1) {
                $size /= 1024;
                $index++;
            }
    
            return number_format($size, 2) . ' ' . $units[$index];
        }
    
        echo json_encode([
            'totalFiles' => $totalFiles,
            'totalFolders' => $totalFolders,
            'totalSize' => formatSize($totalSize),
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid directory path']);
    }
    exit;    
}


// Full File structure stuff
if (isset($_GET['showStructure'])) {
    function generateFullStructure($dir, $indent = 0) {
        $output = '';
        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            $prefix = str_repeat('  ', $indent); // Indentation for nested levels

            if (is_dir($filePath)) {
                $output .= $prefix . "📁 " . $file . "\n";
                $output .= generateFullStructure($filePath, $indent + 1); // Recursively add subdirectories
            } elseif (is_file($filePath)) {
                $output .= $prefix . "📄 " . $file . "\n";
            }
        }

        return $output;
    }

    echo generateFullStructure($currentPath); // Generate structure starting from $currentPath
    exit;
} else {
// Use the current theme directly in the <link> tag
echo '<link rel="stylesheet" href="zDirectNav/themes/' . htmlspecialchars($theme) . '">';
}
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
            border-radius: 8px 8px 0 0;
            font-size: 1.2rem;
            text-align: left;
            color: #fff;
        }
        .header-text{
            font-size: 1.2rem;
            text-align: left;
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
        .header-info {
            margin-bottom: 5px;
            padding: 1px;
            border-radius: 4px;
            font-size: 0.9rem;
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
            cursor: pointer;
        }
        li:hover {
            background-color: #3e3e3e;
            transform: scale(1.01);
        }
        li .file-name {
            flex-grow: 1;
            text-align: left;
            color: #eaeaea;
            text-decoration: none; /* Ensure no underline by default */
        }
        li:hover .file-name {
            text-decoration: underline; /* Add underline on hover */
        }

        li .currently-open {
            font-style: italic;
            color: #888;
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
        a.clickable-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #333;
            text-decoration: none;
            color: #eaeaea;
            border: 1px solid #444;
            border-radius: 4px;
            transition: background-color 0.2s, transform 0.1s;
        }

        a.clickable-item:hover {
            background-color: #3e3e3e;
            transform: scale(1.01);
        }

        a.clickable-item .icon {
            margin-right: 10px;
        }
        .back-button {
            display: inline-block;
            font-size: 0.8rem;
            margin-bottom: 5px;
            padding: 8px 12px;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        footer {
            text-align: right;
            padding: 10px;
            font-size: 0.8rem;
            background-color: #222;
            color: #777;
            border-top: 1px solid #444;
        }

    </style>
</head>
<body>
    <div class="container">
        <header>
            <form method="GET" style="text-align: right;">
                <label for="theme">Select Theme:</label>
                <select name="theme" id="theme" onchange="this.form.submit()">
                    <?php
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
                    <?php if (isset($_GET['path'])) { ?>
                        <input type="hidden" name="path" value="<?php echo htmlspecialchars($_GET['path']); ?>">
                    <?php } ?>
                </select>
            </form>
            <p class="header-text"><b>Directory Listing for "<?php echo ($directoryName === 'html' ? 'root' : htmlspecialchars($directoryName)); ?>"</b><br><p>
            <?php 
                if ($currentPath !== $rootPath) {
                    $parentPath = dirname($currentPath);
                    echo '<a href="?path=' . urlencode($parentPath) . '&theme=' . htmlspecialchars($theme) . '" class="back-button">← Back to Parent Directory</a>';
                } else {
                    echo '<p class="header-info"><i>You are at the root directory.</i></p>';
                }
            ?>
        </header>


        <div class="content">
        <?php
            // Determine current directory path
            $currentPath = isset($_GET['path']) ? realpath($_GET['path']) : realpath('.');
            $rootPath = realpath('.');
            $directoryName = basename($currentPath);

            // Prevent navigating outside the root directory
            if ($currentPath === false || strpos($currentPath, $rootPath) !== 0) {
                $currentPath = $rootPath;
            }
            ?>
            <div class="info">
            <p><strong>Current Directory:</strong> <?php echo htmlspecialchars($currentPath); ?></p>
            <p>Total Files: <span id="totalFiles">Calculating...</span></p>
            <p>Total Folders: <span id="totalFolders">Calculating...</span></p>
            <p>Total Size: <span id="totalSize">Calculating...</span></p>
            <button id="toggleStructure">Show File Structure</button>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', async () => {
                    const currentPath = (new URLSearchParams(window.location.search)).get('path') || '.';

                    try {
                        const response = await fetch(`index.php?action=calculateDirectorySize&path=${encodeURIComponent(currentPath)}`);
                        if (response.ok) {
                            const data = await response.json();
                            document.getElementById('totalFiles').textContent = data.totalFiles;
                            document.getElementById('totalFolders').textContent = data.totalFolders;
                            document.getElementById('totalSize').textContent = data.totalSize;
                        } else {
                            document.getElementById('totalFiles').textContent = 'Error';
                            document.getElementById('totalFolders').textContent = 'Error';
                            document.getElementById('totalSize').textContent = 'Error';
                            console.error('Failed to fetch directory size:', await response.text());
                        }
                    } catch (error) {
                        document.getElementById('totalFiles').textContent = 'Error';
                        document.getElementById('totalFolders').textContent = 'Error';
                        document.getElementById('totalSize').textContent = 'Error';
                        console.error('Error fetching directory size:', error);
                    }
                });
            </script>


            <div id="fileStructure" style="display: none; margin-top: 20px; background: #333; padding: 10px; border-radius: 8px; color: #eee;">
                <h4>Full File Structure:</h4>
                <pre id="structureContent"></pre>
            </div>

            <script>
                document.getElementById('toggleStructure').addEventListener('click', function () {
                    const fileStructureDiv = document.getElementById('fileStructure');
                    const structureContent = document.getElementById('structureContent');
                    const button = this;

                    if (fileStructureDiv.style.display === 'none') {
                        // Show file structure
                        button.textContent = 'Hide File Structure';
                        if (!structureContent.textContent.trim()) {
                            // Fetch only if not already loaded
                            // const queryString = window.location.search;
                            // const urlParams = new URLSearchParams(queryString);
                            // const currentPath = urlParams.get('path');
                            const currentPath = (new URLSearchParams(window.location.search)).get('path');
                            fetch('index.php?showStructure=1&path='+currentPath)
                                .then(response => response.text())
                                .then(data => {
                                    structureContent.textContent = data;
                                    fileStructureDiv.style.display = 'block';
                                })
                                .catch(error => console.error('Error fetching file structure:', error));
                        } else {
                            fileStructureDiv.style.display = 'block';
                        }
                    } else {
                        // Hide file structure
                        button.textContent = 'Show File Structure';
                        fileStructureDiv.style.display = 'none';
                    }
                });
            </script>
            <ul>
                <?php
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;

                    $relativePath = ltrim(str_replace($rootPath, '', $currentPath . DIRECTORY_SEPARATOR . $file), DIRECTORY_SEPARATOR);

                    if (is_dir($currentPath . DIRECTORY_SEPARATOR . $file)) {
                        echo '<li onclick="location.href=\'?path=' . urlencode($currentPath . DIRECTORY_SEPARATOR . $file) . '&theme=' . htmlspecialchars($theme) . '\'">';
                        echo '<span class="icon folder">📁</span>';
                        echo '<span class="file-name">' . htmlspecialchars($file) . '</span>';
                        echo '</li>';
                    } elseif ($file === basename(__FILE__) && realpath($currentPath) === $rootPath) {
                        echo '<li>';
                        echo '<span class="icon file">📄</span>';
                        echo '<span class="file-name">' . htmlspecialchars($file) . ' <span class="currently-open">(currently open)</span></span>';
                        echo '</li>';
                    } else {
                        echo '<li onclick="location.href=\'/' . htmlspecialchars($relativePath) . '\'">';
                        echo '<span class="icon file">📄</span>';
                        echo '<span class="file-name">' . htmlspecialchars($file) . '</span>';
                        echo '</li>';
                    }
                }
                ?>
            </ul>
        </div>
        <footer>
            Interactive File Organization Interface &copy; Danil Vilmont <?php echo date('Y'); ?>
        </footer>
    </div>
</body>
</html>