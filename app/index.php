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


            <div id="fileStructure" style="display: none; margin-top: 20px; background: #333; padding: 10px; border-radius: 8px; color: #eee;">
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