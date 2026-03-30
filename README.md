# Comby Analytics v2.0

**Ultimate Privacy-First, Cookie-less WordPress Analytics.**

Comby Analytics is a high-performance, GDPR-compliant analytics engine built directly into your WordPress dashboard. It tracks visitors without cookies, respects user privacy by rotating salts daily, and provides deep insights into eCommerce performance, UTM campaigns, and individual user journeys.

---

## ✨ Key Features

-   **100% Privacy-First**: No cookies, no tracking IDs, and no personal data stored in the database. Uses rotating secure hashes for identity-safe tracking.
-   **eCommerce Ready**: Native integration with WooCommerce, Easy Digital Downloads (EDD), and SureCart. Tracks revenue and conversion rates automatically.
-   **UTM & Referrer Tracking**: Monitor your marketing performance with built-in UTM source, medium, and campaign tracking.
-   **Real-time Dashboard**: See live visitors on your site and their active pages as it happens.
-   **User Journeys**: Visualize the exact path visitors take through your site, from landing to conversion.
-   **Geolocation**: Insights into visitor countries and cities without compromising individual privacy.
-   **Lightweight & Fast**: Minimal footprint, using native WordPress REST API and optimized database queries.

---

## 📦 Installation Instructions

### Method 1: The "Import" (Upload) Way (Recommended)
1.  **Download/Prepare the ZIP**:
    *   Compress the `comby-analytics` folder into a file named `comby-analytics.zip`.
    *   *Note: Ensure the root files (like `comby-analytics.php`) are directly inside the folder within the zip.*
2.  **Upload to WordPress**:
    *   Go to your WordPress Admin dashboard.
    *   Navigate to **Plugins > Add New**.
    *   Click the **Upload Plugin** button at the top.
    *   Choose the `comby-analytics.zip` file and click **Install Now**.
3.  **Activate**:
    *   Click **Activate Plugin** once the installation is complete.

### Method 2: Manual FTP Upload
1.  Upload the `comby-analytics` folder to your `/wp-content/plugins/` directory.
2.  Go to **Plugins > Installed Plugins** in your WordPress Admin.
3.  Find **Comby Analytics** and click **Activate**.

---

## 🛠️ Usage

Once activated, you will find a new **Comby Analytics** menu item in your WordPress Sidebar.

-   **Overview**: Summary of visitors, revenue, and conversion trends.
-   **Real-time**: Live view of current active users.
-   **eCommerce**: Detailed breakdown of recent orders and revenue.
-   **Campaigns**: Performance of your UTM-tagged marketing efforts.
-   **Journeys**: Step-by-step paths taken by your visitors.
-   **Settings**: Configure data pruning and privacy options.

---

## ⚙️ Technical Details

-   **Database Tables**: Creates `wp_comby_sessions` and `wp_comby_pageviews` upon activation.
-   **Privacy Hash**: Generated using `sha256(IP + UserAgent + DailySalt)`. The salt rotates every 24 hours via WP-Cron.
-   **REST API**: Uses the `comby-analytics/v1` namespace for tracking pings.

---

## 🛡️ License
GPLv2 or later. Built with ❤️ by Antigravity.
