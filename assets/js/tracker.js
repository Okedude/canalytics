/**
 * Comby Analytics Tracker v2.0
 * Cookie-less, UTMs, Referrer, Clicks, and Forms.
 */

(function($) {
    'use strict';

    if (typeof comby_config === 'undefined') return;
    const config = comby_config;

    /**
     * Parse UTM Parameters from URL
     */
    const getUTMs = () => {
        const urlParams = new URLSearchParams(window.location.search);
        return {
            utm_source: urlParams.get('utm_source') || '',
            utm_medium: urlParams.get('utm_medium') || '',
            utm_campaign: urlParams.get('utm_campaign') || ''
        };
    };

    /**
     * Send Ping to Server
     */
    const sendPing = (isHeartbeat = false, event = 'pageview', label = '') => {
        const utms = getUTMs();
        const data = {
            url: config.url,
            title: config.title,
            heartbeat: isHeartbeat ? 'true' : 'false',
            user_id: config.user_id,
            author_id: config.author_id,
            categories: config.categories,
            referrer: document.referrer || '',
            event: event,
            label: label,
            ...utms
        };

        fetch(config.root + 'comby-analytics/v1/ping', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': config.nonce
            },
            body: JSON.stringify(data)
        }).catch(err => console.error('Comby Analytics Error:', err));
    };

    // 1. Initial Page Load
    sendPing(false);

    // 2. Heartbeat (Every 5s)
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            sendPing(true);
        }
    }, 5000);

    // 3. Outbound Click Tracking
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (link && link.href) {
            const isExternal = link.hostname !== window.location.hostname;
            if (isExternal) {
                sendPing(false, 'click', link.href);
            }
        }
    });

    // 4. Form Submission Tracking
    document.addEventListener('submit', (e) => {
        const form = e.target;
        const formId = form.id || form.className || 'unknown-form';
        sendPing(false, 'form_submit', formId);
    });

})(typeof jQuery !== 'undefined' ? jQuery : null);
