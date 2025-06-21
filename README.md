Search files...
Search files...
Search files...
Search files...
Here is a comprehensive README update for your project, based on all the information found in your codebase and documentation. This version is structured to meet your teacher's requirements, including main features, user interaction, licensing, and design motivation. You can copy and adapt this into your README.md.

---

# 🎬 ActorAwardsVisualizer

A web app for exploring Screen Actors Guild Award winners, built with PHP, HTML, CSS, and SQL. The project leverages open-source libraries and APIs, and all code is under a free license (MIT). All content respects Creative Commons terms.

---

## 📜 License

- **MIT License** – All source code is under the MIT license, ensuring it is free and open for modification and redistribution.
- **Content** – All data and content provided by the application respect Creative Commons terms.
- **Third-party assets** – Actor images and information are fetched from the [TMDB API](https://www.themoviedb.org/documentation/api), with proper attribution in the footer.

---

## 📦 Dependencies

- **vlucas/phpdotenv** – for environment variables
- **TMDB API** – for actor images and info
- **intervention/image** – image processing (open source)
- **phenx/php-svg-lib** – SVG export (open source)
- **phpmailer/phpmailer** – email sending (open source)
- **rosell-dk/webp-convert** – WebP export (open source)
- **PDO SQLite** – database (open source, built-in)
- **Chart.js** – for statistics visualization (open source, via CDN)
- **Font Awesome** – for icons (open source, via CDN)

---

## 👥 Team Members

- Turcanu Denis Rafael
- Meraru Ioan-Lucian

---

## 📝 Main Features & Functionalities

### User-Facing Pages

1. **Home Page**
   - Introduction to the project and navigation to all main sections.
   - Quick stats: total nominations, unique actors, award categories, years of data.

2. **Nominations**
   - Browse and filter nominations by year, category, actor, or production.
   - Links to actor and production profiles.

3. **Actor Profile**
   - Detailed biography and award history for each actor (from TMDB and other sources).
   - List of SAG nominations and wins.
   - Actor-specific statistics (e.g., consecutive nominations).
   - News about the actor from external sources.

4. **Production Profile**
   - Details about movies/TV shows (from TMDB).
   - List of involved actors and associated SAG nominations.

5. **Statistics**
   - At least three types of visualizations/statistics:
     - Distribution of nominations by genre.
     - Most nominated actors.
     - Evolution of nominations over the years.
   - Export statistics as CSV, WebP, or SVG.

6. **Admin Dashboard**
   - User management (create, edit, delete users, assign roles).
   - System health and logs (disk/memory usage, error/access logs).
   - Database and media backup/export.

7. **Authentication**
   - Secure login and registration (CSRF protection, password hashing, input validation).
   - Password reset with secure token and email delivery.
   - Role-based access (admin/user).

8. **Settings (from Navbar)**
   - Light/Dark mode (planned).
   - Login/Logout.

9. **Error/Fallback Page (404)**
   - Friendly error page for missing or incorrect routes.

---

## 🖥️ User Interaction & UX

- **Navigation**: Responsive navbar with links to all main sections. User actions (login/logout/admin) are context-aware.
- **Forms**: All forms (login, registration, password reset, admin user management) have validation, CSRF protection, and clear feedback.
- **Filtering**: Nominations and productions can be filtered by multiple criteria.
- **Statistics**: Interactive charts (Chart.js) with export options.
- **Accessibility**: Focus states, keyboard navigation, and ARIA roles where appropriate.
- **Mobile Support**: All pages are fully responsive, with layouts adapting for mobile and tablet screens.

---

## 🎨 Design Motivation

- **Modern, Clean UI**: Uses a palette of blue and white, with gradients for headers and cards, and rounded corners for a friendly look.
- **Consistency**: Common CSS variables and components ensure a unified look across all pages.
- **Responsiveness**: Grid and flex layouts adapt to all screen sizes.
- **Accessibility**: High-contrast colors, focus states, and semantic HTML.
- **Attribution**: TMDB logo and link are always visible in the footer, as required by their API terms.

**Design inspiration**: The interface is inspired by modern dashboard and analytics tools, focusing on clarity, ease of navigation, and data visualization. The use of cards, grids, and clear section headers helps users quickly find and interpret information.

---

## 🏗️ Technical Stack

- **Backend**: PHP 8.1+, SQLite, Composer for dependency management.
- **Frontend**: HTML5, CSS3 (custom and modular), JavaScript (for charts and interactivity).
- **APIs**: TMDB for actor and production data.
- **Containerization**: Dockerfile and docker-compose for easy deployment.

---

## 🛡️ Security

- All user input is validated and sanitized.
- Passwords are hashed using secure algorithms.
- CSRF tokens are used for all forms.
- Sessions are securely managed.
- Admin actions are protected by role-based access control.

---

## 📤 Export & Backup

- **Statistics**: Exportable as CSV, WebP, or SVG.
- **Database**: Downloadable from the admin dashboard.
- **Media**: Backup script for all uploaded/static media.

---

## 📝 Requirements & Standards

- All code and dependencies are under free/open licenses.
- All content respects Creative Commons terms.
- The project follows the IEEE System Requirements Specification Template for documenting requirements (see documentation/documentation.md for details).

---

## 🖼️ Attribution

- Actor images and data: [TMDB API](https://www.themoviedb.org/documentation/api)
- Icons: [Font Awesome](https://fontawesome.com/)
- Charts: [Chart.js](https://www.chartjs.org/)

---
