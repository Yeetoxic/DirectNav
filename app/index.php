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

// Recursive search
if (isset($_GET['action']) && $_GET['action'] === 'searchFiles' && isset($_GET['query'])) {
    header('Content-Type: application/json');
    $query = strtolower($_GET['query']);
    $rootPath = realpath('.');

    function formatSizeUnits($bytes) {
        if ($bytes >= 1099511627776) return number_format($bytes / 1099511627776, 2) . ' TB';
        elseif ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        elseif ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        elseif ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        elseif ($bytes > 1) return $bytes . ' B';
        elseif ($bytes == 1) return '1 B';
        else return '0 B';
    }

    function recursiveSearch($dir, $query, $rootPath) {
        $results = [];
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;

            if (stripos($item, $query) !== false) {
                $results[] = [
                    'name' => $item,
                    'path' => str_replace($rootPath . DIRECTORY_SEPARATOR, '', $fullPath),
                    'size' => is_file($fullPath) ? formatSizeUnits(filesize($fullPath)) : 'folder',
                    'isDir' => is_dir($fullPath),
                ];
            }

            if (is_dir($fullPath)) {
                $results = array_merge($results, recursiveSearch($fullPath, $query, $rootPath));
            }
        }
        return $results;
    }

    echo json_encode(recursiveSearch($rootPath, $query, $rootPath));
    exit;
}

// Calculate file/folder sizes
 // Asynchronous Directory Size Calculation
 if (isset($_GET['action']) && $_GET['action'] === 'calculateDirectorySize') {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    function streamUpdate($data) {
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush();
        flush();
    }

    function getDirectorySize($dir) {
        $size = 0;
        $totalFiles = 0;
        $totalFolders = 0;
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
                    $queue[] = $filePath;
                }

                // Stream updates to the client
                streamUpdate([
                    'totalFiles' => $totalFiles,
                    'totalFolders' => $totalFolders,
                    'totalSize' => $size,
                ]);
            }
        }

        return ['totalSize' => $size, 'totalFiles' => $totalFiles, 'totalFolders' => $totalFolders];
    }

    $currentPath = realpath($_GET['path'] ?? '.');
    $rootPath = realpath('.');

    if ($currentPath && strpos($currentPath, $rootPath) === 0) {
        $result = getDirectorySize($currentPath);

        // Send a final message and close the connection
        streamUpdate([
            'totalFiles' => $result['totalFiles'],
            'totalFolders' => $result['totalFolders'],
            'totalSize' => $result['totalSize'],
            'complete' => true, // Indicate the process is complete
        ]);

        // Close the connection
        echo "event: close\n"; // Optional event to signal closure
        echo "data: {}\n\n";
        ob_flush();
        flush();
    } else {
        streamUpdate(['error' => 'Invalid directory path']);
    }
    exit;
}

// Full File structure stuff
if (isset($_GET['action']) && $_GET['action'] === 'showStructure') {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    function streamUpdate($data) {
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush();
        flush();
    }

    function generateFileStructure($dir, $indent = 0) {
        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            $prefix = str_repeat('  ', $indent); // Indentation for nested levels

            if (is_dir($filePath)) {
                // Stream the directory
                streamUpdate(['type' => 'directory', 'name' => $prefix . "📁 " . $file]);
                generateFileStructure($filePath, $indent + 1); // Recursively process subdirectories
            } elseif (is_file($filePath)) {
                // Stream the file
                streamUpdate(['type' => 'file', 'name' => $prefix . "📄 " . $file]);
            }
        }
    }

    $currentPath = realpath($_GET['path'] ?? '.');
    $rootPath = realpath('.');

    if ($currentPath && strpos($currentPath, $rootPath) === 0) {
        generateFileStructure($currentPath);

        // Send a final message to indicate completion
        streamUpdate(['complete' => true]);

        // Close the connection
        echo "event: close\n";
        echo "data: {}\n\n";
        ob_flush();
        flush();
    } else {
        streamUpdate(['error' => 'Invalid directory path']);
    }
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
        * {
            box-sizing: border-box;
        }
        body {
            font-family: monospace;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh; /* Ensure body takes full height of the viewport */
            background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
            overflow: hidden; /* Prevent body scrollbars */
            padding-top: 20px; /* Add padding to keep space above */
            padding-bottom: 20px; /* Add padding to keep space below */
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
            width: 95%; /* Adjust to fit most of the browser window */
            max-width: 800px; /* Prevent it from growing too large */
            height: auto;
            max-height: 100%; /* Prevent overflow */
            border-radius: 8px;
            box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
            overflow: hidden; /* Clip content inside */
            display: flex;
            flex-direction: column;
        }
        .content {
            padding: 10px; /* Add space inside the content */
            overflow: auto; /* Enable scrolling when needed */
            flex-grow: 1; /* Allow the content to stretch */
            display: flex;
            flex-direction: column;
            gap: 10px; /* Add spacing between children */
        }
        .info {
            flex-shrink: 0; /* Prevent it from shrinking */
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
            flex-grow: 1; /* Allow the list to grow dynamically */
            overflow-y: auto; /* Add a vertical scrollbar */
            overflow-x: hidden;
            max-height: calc(100% - 100px); /* Dynamically calculate height based on other elements */
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
        .explorer-nav {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .explorer-nav button {
            padding: 6px 10px;
            background-color: #2d2d2d;
            border: 1px solid #444;
            color: #eee;
            border-radius: 5px;
            font-size: 0.85rem;
            cursor: pointer;
        }
        .explorer-nav input {
            flex: 1;
            padding: 6px;
            background: #1e1e1e;
            color: #aaa;
            border: 1px solid #444;
            border-radius: 5px;
        }
        .explorer-nav button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        footer {
            flex-shrink: 0; /* Ensure footer doesn't resize */
            text-align: right;
            padding: 10px;
            font-size: 0.8rem;
            background-color: #222;
            color: #777;
            border-top: 1px solid #444;
        }
        /* File structure area */
        #fileStructure {
            display: none; /* Hidden by default */
            flex-grow: 1; /* Allow it to expand when visible */
            max-height: 50%; /* Limit height for better layout */
            overflow-y: auto; /* Add vertical scrolling */
            background: #333;
            padding: 10px;
            border-radius: 8px;
            color: #eee;
        }
        #fileStructure::-webkit-scrollbar {
            width: 8px;
        }
        #fileStructure::-webkit-scrollbar-thumb {
            background-color: #444;
            border-radius: 4px;
        }
        #fileStructure::-webkit-scrollbar-track {
            background-color: #222;
        }
        /* Responsive behavior */
        @media (max-width: 768px) {
            .container {
                height: 95%; /* Increase height for smaller screens */
            }

            .content {
                padding: 10px;
            }

            ul, #fileStructure {
                max-height: 30%; /* Reduce height for smaller screens */
            }
        }

        .file-size {
            font-size: 0.85rem;
            color: #aaa;
            margin-left: 6px;
        }

        /* BreadCrumb stuff */
        .breadcrumb {
            font-size: 0.9rem;
            margin: 10px 0;
            padding: 5px 10px;
            background-color: #222;
            border-radius: 4px;
            color: #bbb;
        }

        .breadcrumb a {
            color: #80d4ff;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .crumb-collapse {
            color: #888;
            padding: 0 5px;
        }

        .breadcrumb.gray-root {
            display: inline-block;
            background-color: #2a2a2a;
            border-radius: 6px;
            padding: 0.25rem 0.6rem;
            color: #aaa;
            font-size: 0.9rem;
            font-weight: 500;
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
            function build_breadcrumb($basePath, $currentPath, $theme) {
                $relative = str_replace($basePath, '', $currentPath);
                $segments = array_filter(explode(DIRECTORY_SEPARATOR, $relative));
                
                if (empty($segments)) {
                    return '<div class="breadcrumb gray-root"><span>You are at the root directory.</span></div>';
                }

                $breadcrumb = '<nav class="breadcrumb"><a href="?path=' . urlencode($basePath) . '&theme=' . urlencode($theme) . '">Home</a>';
                $fullPath = $basePath;

                // Collapse long paths
                if (count($segments) > 4) {
                    $breadcrumb .= ' / <span class="crumb-collapse">...</span>';
                    $segments = array_slice($segments, -3);
                    $start = count(explode(DIRECTORY_SEPARATOR, $basePath)) + (count($segments) - 3);
                    $parts = explode(DIRECTORY_SEPARATOR, $currentPath);
                    $fullPath = implode(DIRECTORY_SEPARATOR, array_slice($parts, 0, $start));
                }

                foreach ($segments as $segment) {
                    $fullPath .= DIRECTORY_SEPARATOR . $segment;
                    $breadcrumb .= ' / <a href="?path=' . urlencode($fullPath) . '&theme=' . urlencode($theme) . '">' . htmlspecialchars($segment) . '</a>';
                }

                $breadcrumb .= '</nav>';
                return $breadcrumb;
            }
            ?>
            <div class="explorer-nav">
                <button id="backBtn" onclick="goBack()" 
                    <?php if ($currentPath === $rootPath) echo 'disabled'; ?>>
                    ◀ Back
                </button>
                <div id="breadcrumbBar">
                    <?= build_breadcrumb($rootPath, $currentPath, $theme) ?>
                </div>
            </div>
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
            <input type="text" id="searchInput" placeholder="Filter files..." style="margin-bottom: 2px; padding: 6px; background: #1e1e1e; border: 1px solid #444; color: #ccc; border-radius: 5px;">
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const currentPath = (new URLSearchParams(window.location.search)).get('path') || '.';
                    const totalFilesEl = document.getElementById('totalFiles');
                    const totalFoldersEl = document.getElementById('totalFolders');
                    const totalSizeEl = document.getElementById('totalSize');

                    const formatSize = (size) => {
                        const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
                        let index = 0;
                        while (size >= 1024 && index < units.length - 1) {
                            size /= 1024;
                            index++;
                        }
                        return `${size.toFixed(2)} ${units[index]}`;
                    };

                    // Open an EventSource to stream updates
                    const eventSource = new EventSource(`index.php?action=calculateDirectorySize&path=${encodeURIComponent(currentPath)}`);

                    eventSource.onmessage = (event) => {
                        const data = JSON.parse(event.data);

                        if (data.error) {
                            totalFilesEl.textContent = 'Error';
                            totalFoldersEl.textContent = 'Error';
                            totalSizeEl.textContent = 'Error';
                            eventSource.close();
                            return;
                        }

                        // Update the counts
                        totalFilesEl.textContent = data.totalFiles || 0;
                        totalFoldersEl.textContent = data.totalFolders || 0;
                        totalSizeEl.textContent = formatSize(data.totalSize || 0);

                        // Close the connection if the process is complete
                        if (data.complete) {
                            console.log('Processing complete.');
                            eventSource.close();
                        }
                    };

                    eventSource.onerror = () => {
                        console.error('Error occurred while fetching updates.');
                        totalFilesEl.textContent = 'Error';
                        totalFoldersEl.textContent = 'Error';
                        totalSizeEl.textContent = 'Error';
                        eventSource.close();
                    };
                });
            </script>


            <div id="fileStructure" style="display: none; margin-top: 5px; background: #333; padding: 10px; border-radius: 8px; color: #eee;">
                <h4>Full File Structure:</h4>
                <pre id="structureContent"></pre>
            </div>

            <script>
                document.getElementById('toggleStructure').addEventListener('click', function () {
                    const fileStructureDiv = document.getElementById('fileStructure');
                    const structureContent = document.getElementById('structureContent');
                    const button = this;

                    // To prevent multiple connections
                    if (window.currentEventSource) {
                        // If EventSource already exists, close it and reset
                        window.currentEventSource.close();
                        window.currentEventSource = null;
                    }

                    if (fileStructureDiv.style.display === 'none') {
                        // Show file structure
                        button.textContent = 'Hide File Structure';
                        fileStructureDiv.style.display = 'block';

                        // Clear previous content
                        structureContent.textContent = '';

                        // Fetch and stream the file structure
                        const currentPath = (new URLSearchParams(window.location.search)).get('path') || '.';
                        const eventSource = new EventSource(`index.php?action=showStructure&path=${encodeURIComponent(currentPath)}`);
                        window.currentEventSource = eventSource; // Store EventSource globally to manage it

                        eventSource.onmessage = (event) => {
                            const data = JSON.parse(event.data);

                            if (data.error) {
                                structureContent.textContent = 'Error loading file structure.';
                                console.error('Error:', data.error);
                                eventSource.close();
                                window.currentEventSource = null;
                                return;
                            }

                            if (data.type === 'directory' || data.type === 'file') {
                                // Append the directory or file to the content
                                structureContent.textContent += data.name + '\n';
                            }

                            if (data.complete) {
                                console.log('File structure loading complete.');
                                eventSource.close();
                                window.currentEventSource = null; // Clear the reference when complete
                            }
                        };

                        eventSource.onerror = () => {
                            structureContent.textContent = 'Error loading file structure.';
                            console.error('Error fetching file structure.');
                            eventSource.close();
                            window.currentEventSource = null; // Clear the reference on error
                        };
                    } else {
                        // Hide file structure and close any active EventSource
                        button.textContent = 'Show File Structure';
                        fileStructureDiv.style.display = 'none';

                        if (window.currentEventSource) {
                            window.currentEventSource.close();
                            window.currentEventSource = null; // Clear the reference
                        }
                    }
                });
            </script>
            <ul>
                <?php
                $visibleItems = array_filter($files, fn($f) => $f !== '.' && $f !== '..');
                function formatSize($bytes) {
                    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                    $i = 0;
                    while ($bytes >= 1024 && $i < count($units) - 1) {
                        $bytes /= 1024;
                        $i++;
                    }
                    return round($bytes, 2) . ' ' . $units[$i];
                }
                if (empty($visibleItems)) {
                    echo '<li><em>This directory is empty.</em></li>';
                }

                foreach ($visibleItems as $file) {

                    $relativePath = ltrim(str_replace($rootPath, '', $currentPath . DIRECTORY_SEPARATOR . $file), DIRECTORY_SEPARATOR);

                    if (is_dir($currentPath . DIRECTORY_SEPARATOR . $file)) {
                        echo '<li onclick="location.href=\'?path=' . urlencode($currentPath . DIRECTORY_SEPARATOR . $file) . '&theme=' . htmlspecialchars($theme) . '\'">';
                        echo '<span class="icon folder">📁</span>';
                        echo '<span class="file-name">' . htmlspecialchars($file) . '</span>';
                        echo '</li>';
                    } elseif ($file === basename(__FILE__) && realpath($currentPath) === $rootPath) {
                        $filePath = $currentPath . DIRECTORY_SEPARATOR . $file;
                        $fileSize = is_file($filePath) ? formatSize(filesize($filePath)) : '';

                        echo '<li>';
                        $ext = pathinfo($file, PATHINFO_EXTENSION);
                        $icon = match (strtolower($ext)) {
                            'png', 'jpg', 'jpeg', 'gif', 'webp' => '🖼️',
                            'mp3', 'wav', 'ogg' => '🎵',
                            'mp4', 'webm', 'avi', 'mov' => '🎞️',
                            'zip', 'rar', '7z' => '📦',
                            'txt', 'md', 'log' => '📜',
                            'php', 'js', 'html', 'css', 'py', 'java', 'c', 'cpp' => '💻',
                            'pdf' => '📕',
                            default => '📄',
                        };
                        echo '<span class="icon file">' . $icon . '</span>';
                        echo '<span class="file-name">' . htmlspecialchars($file) . ' <span class="currently-open">(currently open)</span>';
                        if ($fileSize) echo ' <span class="file-size">(' . $fileSize . ')</span>';
                        echo '</span>';
                        echo '</li>';
                    } else {
                        $filePath = $currentPath . DIRECTORY_SEPARATOR . $file;
                        $fileSize = is_file($filePath) ? formatSize(filesize($filePath)) : '';

                        echo '<li onclick="location.href=\'/' . htmlspecialchars($relativePath) . '\'">';
                        $ext = pathinfo($file, PATHINFO_EXTENSION);
                        $icon = match (strtolower($ext)) {
                            'png', 'jpg', 'jpeg', 'gif', 'webp' => '🖼️',
                            'mp3', 'wav', 'ogg' => '🎵',
                            'mp4', 'webm', 'avi', 'mov' => '🎞️',
                            'zip', 'rar', '7z' => '📦',
                            'txt', 'md', 'log' => '📜',
                            'php', 'js', 'html', 'css', 'py', 'java', 'c', 'cpp' => '💻',
                            'pdf' => '📕',
                            default => '📄',
                        };
                        echo '<span class="icon file">' . $icon . '</span>';
                        echo '<span class="file-name">' . htmlspecialchars($file);
                        if ($fileSize) echo ' <span class="file-size">(' . $fileSize . ')</span>';
                        echo '</span>';
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
    <script>
    document.getElementById('searchInput').addEventListener('input', function () {
        const query = this.value.toLowerCase();
        const fileList = document.querySelector('ul');
        const infoBox = document.querySelector('.info');

        // Clear list if searching
        if (query.length > 0) {
            fetch(`?action=searchFiles&query=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    fileList.innerHTML = '';
                    if (data.length === 0) {
                        fileList.innerHTML = '<li><em>No matches found.</em></li>';
                    } else {
                        data.forEach(file => {
                            const li = document.createElement('li');
                            li.onclick = () => window.location.href = file.isDir
                                ? `?path=${encodeURIComponent(file.path)}`
                                : `/${file.path}`;

                            const icon = document.createElement('span');
                            icon.className = 'icon file';
                            icon.textContent = file.isDir ? '📁' : '📄';

                            const name = document.createElement('span');
                            name.className = 'file-name';
                            name.innerHTML = `${file.name} <span class="file-size">(${typeof file.size === 'string' ? file.size : (file.size / 1024).toFixed(1) + ' KB'})</span><br><small style="color:#888;">${file.path}</small>`;

                            li.appendChild(icon);
                            li.appendChild(name);
                            fileList.appendChild(li);
                        });
                    }

                    infoBox.style.display = 'none'; // hide summary stats while filtering
                });
        } else {
            window.location.reload(); // reload to default view if empty query
        }
    });
    </script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        let currentPath = urlParams.get('path') || '.';
        let theme = urlParams.get('theme') || 'default';

        // Load history from sessionStorage or initialize
        let historyStack = JSON.parse(sessionStorage.getItem('historyStack') || '[]');
        let historyIndex = Number.isInteger(parseInt(sessionStorage.getItem('historyIndex')))
            ? parseInt(sessionStorage.getItem('historyIndex'))
            : -1;

        // Update history if navigating to a new path
        window.addEventListener('beforeunload', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const currentPath = urlParams.get('path') || '.';
            const prevPath = historyStack[historyIndex];

            // Only update history if this is a normal navigation
            if (prevPath !== currentPath) {
                historyStack = historyStack.slice(0, historyIndex + 1);
                historyStack.push(currentPath);
                historyIndex++;
                sessionStorage.setItem('historyStack', JSON.stringify(historyStack));
                sessionStorage.setItem('historyIndex', historyIndex.toString());
            }
        });

        // Save updated history
        sessionStorage.setItem('historyStack', JSON.stringify(historyStack));
        sessionStorage.setItem('historyIndex', historyIndex.toString());

        function goBack() {
            const urlParams = new URLSearchParams(window.location.search);
            const currentPath = urlParams.get('path') || '.';
            const theme = urlParams.get('theme') || 'default';

            // Go up one directory
            const parts = currentPath.split('/');
            if (parts.length > 1) {
                parts.pop();
                const newPath = parts.join('/') || '.';
                window.location.href = `?path=${encodeURIComponent(newPath)}&theme=${encodeURIComponent(theme)}`;
            }
        }

        function updatePathDisplay() {
            document.getElementById('currentPathDisplay').value = decodeURIComponent(currentPath);
        }
        updatePathDisplay();
        document.getElementById('backBtn').disabled = historyIndex <= 0;
    </script>
</body>
</html>