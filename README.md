# AB-WP Plugin (AB Addon)

**AB Addon** is a powerful Elementor add‑on that brings a suite of UI enhancements to your WordPress site:

- **Custom Elementor CSS** – a full‑screen editor with a live preview, searchable element tree, and one‑click insertion of IDs, classes or tags.
- **Blog Grid & Basic Posts widgets** – flexible layouts for posts, with pagination and AJAX filtering.
- **Popup Manager** – create, edit and display popups via a clean admin page and the simple `[ab_popup id="…"]` shortcode.
- **Responsive controls** – edit CSS for Desktop, Tablet and Mobile views from the same panel.
- **Dark‑mode support** – seamless styling in Elementor’s dark editor.

---

## 🎯 Features

| Feature | Description |
|---|---|
| **Custom CSS Editor** | Interactive tree view of element selectors, quick insert badges, collapsible hierarchy, and a CodeMirror‑based editor that syncs with Elementor’s live preview. |
| **Widget Collection** | Blog Grid, Basic Posts, and a Popup widget (the popup can also be managed via the AB Settings admin page). |
| **Popup Shortcode** | Use `[ab_popup id="my‑popup"]` anywhere (posts, pages, widgets). |
| **Responsive Tabs** | Separate CSS panes for Desktop, Tablet, Mobile – all stored per‑element. |
| **Dark‑Mode Styling** | Dedicated CSS for Elementor editor dark mode and front‑end dark mode. |
| **Admin Settings** | Central “AB Settings” page under **Settings → AB Settings** to manage all popups. |
| **Performance** | Only enqueues assets when the Elementor editor is active; front‑end assets are loaded conditionally. |

---

## 📦 Installation

1. **Download** the plugin ZIP or clone the repository into `wp-content/plugins/ab-plugin`.
2. In the WordPress admin, go to **Plugins → Add New → Upload Plugin** and upload the ZIP, or simply activate the plugin from the plugins list.
3. Ensure **Elementor** is installed and activated – the plugin checks for it on load.
4. Visit **Settings → AB Settings** to start creating popups, or open any Elementor element and click the **Custom CSS** tab under **Advanced**.

> **Tip:** The plugin works with PHP 7.4+ and WordPress 5.9+. Elementor 3.0+ is required.

---

## 🛠️ Usage

### Custom CSS Editor
1. Edit any Elementor element (section, column, widget, or page settings).
2. In the **Advanced → Custom CSS** panel you will see:
   - A **tree view** of the element’s DOM.
   - Badges for **IDs**, **classes**, and **tags** that can be inserted with a single click.
   - A **CodeMirror** editor where you type CSS.
3. Click **Insert** on a badge – the selector is automatically inserted as:
   - `selector #my-id` if an ID exists.
   - `selector .my-class` if no ID but a class exists (multiple classes are concatenated).
   - `selector tag` when only a tag is available.
4. The CSS is saved per‑element and works across Desktop, Tablet and Mobile tabs.

### Popup Manager
1. Navigate to **Settings → AB Settings**.
2. Click **Add New Popup**, give it an ID, design the content (HTML, shortcodes, etc.) and save.
3. Render the popup with the shortcode:
   ```
   [ab_popup id="my-popup"]
   ```
   Place the shortcode anywhere – posts, pages, widgets, or directly in a theme file.

### Blog Grid & Basic Posts Widgets
- Add the **AB Blog Grid** or **AB Basic Posts** widget from the **AB Addons** category in Elementor.
- Configure query parameters, layout, pagination style, and optional custom CSS.

---

## 🧩 Compatibility
- WordPress 5.9+ 
- PHP 7.4 or higher
- Elementor 3.0+ (works with both free and Pro versions)

---

## 📄 Changelog

### 1.2.2 – 2026‑10‑02
- Fixed stray `id` syntax error in `class-ab-custom-css.php`.
- Updated selector priority logic (ID → Class → Tag) in the JavaScript editor.
- Added dark‑mode CSS adjustments.
- Refactored asset versioning to use a timestamp in debug mode.
- Minor UI polish for the tree view and badge hover states.

### 1.2.1 – 2026‑09‑15
- Implemented responsive CSS tabs and improved live preview synchronization.
- Added a new **Popup Manager** admin page.
- Fixed several CodeMirror focus issues on Chrome/Edge.

### 1.2.0 – 2026‑08‑20
- Initial release of the AB Addon package with Custom CSS editor, Blog Grid & Basic Posts widgets.

---

## 📜 License

GPL‑2.0 or later. See the `LICENSE` file for details.

---

## 🙏 Credits

- **Author:** Ashok Bhaargaw – <ashokbhaargaw@gmail.com>
- **Icons & UI:** Elementor UI library.
- **JS Editor:** CodeMirror.

---

*Feel free to open issues or submit pull requests on the GitHub repo.*
