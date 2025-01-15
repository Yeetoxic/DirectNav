# DirectNav - Lightweight Directory Navigation Tool
## A Directory Navigator for ITAS 186

**DirectNav** is a simple and customizable PHP-based directory navigation tool. It allows users to browse directories, switch themes, and configure settings such as the server port.

## Features
- Dynamic directory listing with folder and file distinctions.
- Customizable themes to change the visual appearance.
- Configurable port settings via a user-friendly interface.
- Supports clickable, fully interactive directory boxes.

---

## File Structure
```
/var/www/html/
├── index.php
├── zDirectNav/
│   ├── config.json         # Stores port configuration
│   ├── themes/             # Folder for custom CSS themes
│       ├── default.css
│       ├── midnight.css
│       ├── cyberpunk.css
```

---

## Setup Instructions

1. **Clone or Copy Files**
   - Copy the `index.php` file and the `zDirectNav` folder into your PHP-enabled web server's root directory.

2. **Set Permissions**
   - Ensure that the `config.json` file is writable by the server to allow updates to the port configuration:
     ```bash
     chmod 666 /path/to/zDirectNav/config.json
     ```

3. **Themes**
   - Add custom themes by creating a new `.css` file in the `zDirectNav/themes/` folder.
   - Existing themes include:
     - `default.css`
     - `midnight.css`
     - `cyberpunk.css`

4. **Access the Tool**
   - Open your browser and navigate to the server hosting the tool, e.g., `http://localhost/index.php`.

5. **Change the Port**
   - Use the "Configuration" section to update the server port dynamically. The new port will be saved in `config.json`.

---

## How to Add a Theme
1. Create a new `.css` file in the `zDirectNav/themes/` directory.
2. Use the provided CSS template to maintain compatibility:
   ```css
   body {
    background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
    color: #eaeaea;
      }
      header {
          background-color: #333;
          color: #fff;
      }
      .container {
          background-color: #2a2a2a;
          border: 1px solid #444;
      }
      .info {
          background-color: #333;
          border: 1px solid #b30e0e;
          color: #bbb;
      }
      .back-button {
          background: #333;
          border: 1px solid #b30e0e;
      }
      .back-button:hover {
          background: #3e3e3e;
          border: 1px solid #b30e0e;
      }
      ul li {
          background-color: #333;
          border: 1px solid #444;
      }
      ul li:hover {
          background-color: #3e3e3e;
          border: 1px solid #b30e0e;
      }
      .icon.file {
          color: #61dafb;
      }
      .icon.folder {
          color: #f0c674;
      }
      footer {
          background-color: #222;
          color: #777;
      }
   ```
3. Refresh the page to see the new theme listed in the dropdown menu.

---

## Troubleshooting
1. **Port Update Issues**
   - Ensure the `config.json` file is writable by the server.
   - Check PHP error logs for details.

2. **Theme Not Showing**
   - Verify that the `.css` file is correctly placed in the `themes/` folder.
   - Ensure the file has the correct permissions.

3. **Incorrect Folder/File Count**
   - Ensure the PHP script is running in the correct directory with proper permissions.

---

## License
This project is open-source and available for use and modification under the [MIT License](LICENSE).

---

If you'd like to add a feature / theme, feel free to fork & request a push to the main branch!
