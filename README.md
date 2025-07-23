# DirectNav - Lightweight Directory Navigation Tool

## A Directory Navigator for ITAS 186

**DirectNav** is a simple and customizable PHP-based directory navigation tool. It provides a clean, interactive interface for browsing directories with swappable themes and smart UX touches.

---

## 🚀 Features

* Clickable, interactive file and folder cards with icons and file size display.
* Live theme switching with multiple pre-built options (Default, Ocean, Sunset, etc.)
* Real-time search bar that instantly filters matching files.
* Breadcrumb-style navigation bar for clear path tracking.
* Responsive layout with modern styling and dark mode themes.
* Now supports Docker-based local development with both HTTP and HTTPS

---

## 📁 File Structure

```
app/
├── index.php
├── zDirectNav/
│   ├── themes/
│   │   ├── default.css
│   │   ├── midnight.css
│   │   ├── cyberpunk.css
│   │   ├── forest.css
│   │   ├── mae_red.css
│   │   ├── ocean.css
│   │   └── Sunset.css
```

> 💡 `config.json` has been deprecated and is no longer required.

---

## 🐳 Setup (Docker Desktop Recommended)

This project includes everything you need to get started with Docker:

### 1. Clone the Repository

```bash
git clone https://github.com/Yeetoxic/DirectNav.git
cd DirectNav
```

### 2. Windows Users

Double-click `setup_windows.bat` to build and launch the container.

### 3. macOS/Linux Users

Run the following in your terminal:

```bash
chmod +x setup_linux.sh
./setup_linux.sh
```

---

## 🌐 Accessing DirectNav

Once the container is running, open one of the following in your browser:

* 🔓 [http://localhost:9000](http://localhost:9000) (for local development)
* 🔒 [https://localhost:9443](https://localhost:9443) (self-signed cert, will show warning)

---

## 🎨 Adding a Theme

1. Create a new `.css` file in `zDirectNav/themes/`.
2. Use the structure below or copy from any of the existing themes:

```css
body {
  background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
  color: #eaeaea;
}
.icon.file { color: #61dafb; }
.icon.folder { color: #f0c674; }
/* Check other themes for full examples */
```

3. Refresh the page to see your theme appear in the dropdown.

---

## 🛠️ Troubleshooting

* **Theme Not Showing Up**

  * Ensure the `.css` file is placed in `zDirectNav/themes/`
  * Check for typos in the filename or syntax errors in the CSS

* **File Permissions**

  * On Linux/macOS, make sure files inside `app/` are readable:

    ```bash
    chmod -R 755 app/
    ```

* **Docker Not Starting**

  * Ensure Docker Desktop is installed and running
  * On Windows, try running `setup_windows.bat` as Administrator

---

## 📜 License

This project is licensed under the [MIT License](LICENSE).

---

## 🤝 Want to Contribute?

Fork it, add a theme or feature, and submit a pull request! All contributions are welcome.