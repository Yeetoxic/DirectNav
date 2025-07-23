# DirectNav - Lightweight Directory Navigation Tool
## A Directory Navigator for ITAS 186

**DirectNav** is a simple and customizable PHP-based directory navigation tool. It provides a visual interface for browsing directories with swappable themes and an interactive layout.

---

## 🚀 Features
- Dynamic directory listing with folder and file distinctions.
- Clickable, interactive folder/file boxes.
- Customizable theme system with live switching.
- Now supports Docker-based local development with both HTTP and HTTPS

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
│   │   ├── Sunset.css
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

- 🔓 [http://localhost:9000](http://localhost:9000) (mainly for local development, no cert)
- 🔒 [https://localhost:9443](https://localhost:9443) (self-signed cert, may show a warning)

---

## 🎨 Adding a Theme

1. Create a new `.css` file in `zDirectNav/themes/`.
2. Follow the base structure below or copy from an existing theme:

```css
body {
  background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
  color: #eaeaea;
}
.icon.file { color: #61dafb; }
.icon.folder { color: #f0c674; }
/* See other themes for full structure */
```

3. Refresh the page to see your new theme listed in the dropdown.

---

## 🛠️ Troubleshooting

- **Theme Not Showing Up**
  - Ensure the `.css` file is saved in `zDirectNav/themes/`
  - Check for typos in the filename or CSS

- **File Permissions**
  - On Linux/macOS, make sure files inside `app/` are readable:
    ```bash
    chmod -R 755 app/
    ```

- **Docker Not Starting**
  - Make sure Docker Desktop is installed and running
  - On Windows, run `setup_windows.bat` as Administrator if needed

---

## 📜 License
This project is licensed under the [MIT License](LICENSE).

---

## 🙋‍♂️ Want to Contribute?
Fork it, add a theme or feature, and submit a pull request! We welcome all contributions.