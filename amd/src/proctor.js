// @license MIT
define(['core/ajax', 'core/notification', 'core/str'], function(Ajax, Notification, Str) {
    'use strict';

    var stream = null;
    var sessionId = 0;
    var cmid = 0;
    var attemptid = 0;
    var timer = null;
    var captureWidth = 640;
    var captureHeight = 480;
    var alertTitle = 'Proctoring alert';

    function parseFaces(facesjson) {
        try {
            var faces = JSON.parse(facesjson || '[]');
            return Array.isArray(faces) ? faces : [];
        } catch (e) {
            return [];
        }
    }

    function removeExistingAlert() {
        var existing = document.getElementById('proctor-live-alert-banner');
        if (existing) {
            existing.remove();
        }
    }

    function showLiveAlert(response, thumbDataUrl) {
        if (!response || !response.livealert) {
            return;
        }

        removeExistingAlert();

        var severity = response.alertseverity || 'warning';
        var wrapper = document.createElement('div');
        wrapper.id = 'proctor-live-alert-banner';
        wrapper.className = 'proctor-live-alert ' + severity;
        wrapper.setAttribute('role', 'alert');

        var body = document.createElement('div');
        body.className = 'proctor-alert-body';

        if (thumbDataUrl) {
            var thumbWrap = document.createElement('div');
            thumbWrap.className = 'proctor-alert-thumb-wrap';

            var img = document.createElement('img');
            img.className = 'proctor-alert-thumb';
            img.src = thumbDataUrl;
            img.alt = '';
            thumbWrap.appendChild(img);

            var faces = parseFaces(response.facesjson);
            var imgW = response.imagewidth || captureWidth;
            var imgH = response.imageheight || captureHeight;
            var displayW = 160;
            var scale = displayW / imgW;
            var displayH = imgH * scale;
            img.style.height = displayH + 'px';

            faces.forEach(function(face) {
                var box = document.createElement('div');
                box.className = 'proctor-face-box';
                box.style.left = (face.x * scale) + 'px';
                box.style.top = (face.y * scale) + 'px';
                box.style.width = (face.w * scale) + 'px';
                box.style.height = (face.h * scale) + 'px';
                thumbWrap.appendChild(box);
            });

            body.appendChild(thumbWrap);
        }

        var text = document.createElement('div');
        text.className = 'proctor-alert-text';
        var title = document.createElement('div');
        title.className = 'proctor-alert-title';
        title.textContent = alertTitle;
        var message = document.createElement('div');
        message.textContent = response.alertmessage || '';
        text.appendChild(title);
        text.appendChild(message);
        body.appendChild(text);

        var dismiss = document.createElement('button');
        dismiss.type = 'button';
        dismiss.className = 'proctor-alert-dismiss';
        dismiss.textContent = 'OK';
        dismiss.addEventListener('click', function() {
            wrapper.remove();
        });

        wrapper.appendChild(body);
        wrapper.appendChild(dismiss);
        document.body.appendChild(wrapper);
    }

    function handleAlertResponse(response, thumbDataUrl) {
        if (response && response.success) {
            if (response.sessionid) {
                sessionId = response.sessionid;
            }
            showLiveAlert(response, thumbDataUrl);
        }
    }

    function logIncident(type, severity, detail) {
        if (!sessionId) {
            showLiveAlert({
                livealert: true,
                alertseverity: severity || 'warning',
                alertmessage: detail || type,
                facesjson: '[]'
            }, null);
            return;
        }
        Ajax.call([{
            methodname: 'local_moodle_proctor_log_incident',
            args: {
                sessionid: sessionId,
                type: type,
                severity: severity || 'warning',
                detail: detail || '',
                cmid: cmid
            }
        }])[0].then(function(response) {
            handleAlertResponse(response, null);
        });
    }

    function captureAndUpload(width, height) {
        var video = document.getElementById('proctor-monitor-video');
        if (!video || !stream) {
            logIncident('camera_off', 'critical');
            return;
        }
        var canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, width, height);
        var image = canvas.toDataURL('image/jpeg', 0.8);

        Ajax.call([{
            methodname: 'local_moodle_proctor_upload_snapshot',
            args: {
                attemptid: attemptid,
                image: image,
                cmid: cmid
            }
        }])[0].then(function(response) {
            handleAlertResponse(response, image);
        }).catch(function() {
            Notification.addNotification({
                message: 'Proctoring upload failed',
                type: 'error'
            });
        });
    }

    function startMonitor(attemptId, cmId, width, height, intervalMs, requireExtension, liveAlertTitle) {
        attemptid = attemptId;
        cmid = cmId;
        captureWidth = width;
        captureHeight = height;
        alertTitle = liveAlertTitle || alertTitle;

        if (!navigator.mediaDevices) {
            Notification.addNotification({message: 'Webcam not supported', type: 'error'});
            return;
        }

        navigator.mediaDevices.getUserMedia({video: true, audio: false}).then(function(s) {
            stream = s;
            var container = document.createElement('div');
            container.className = 'proctor-monitor-float position-fixed';
            container.style.cssText = 'bottom:10px;right:10px;z-index:9999;opacity:0.85;';
            var video = document.createElement('video');
            video.id = 'proctor-monitor-video';
            video.width = 160;
            video.height = 120;
            video.autoplay = true;
            video.playsInline = true;
            video.srcObject = stream;
            container.appendChild(video);
            document.body.appendChild(container);

            captureAndUpload(width, height);
            timer = setInterval(function() {
                captureAndUpload(width, height);
            }, intervalMs);
        }).catch(function() {
            Notification.addNotification({message: 'Camera permission required', type: 'error'});
        });

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                logIncident('tab_hidden', 'critical');
            }
        });

        window.addEventListener('blur', function() {
            logIncident('focus_lost', 'info');
        });

        document.addEventListener('copy', function(e) {
            e.preventDefault();
            logIncident('copy_attempt', 'critical');
        });

        document.addEventListener('paste', function(e) {
            e.preventDefault();
            logIncident('paste_attempt', 'critical');
        });

        if (requireExtension) {
            setInterval(function() {
                document.dispatchEvent(new CustomEvent('moodle-proctor-ping'));
            }, 15000);
        }
    }

    return {
        init: function(attemptId, cmId, width, height, intervalMs, requireExtension, liveAlertTitle) {
            startMonitor(attemptId, cmId, width, height, intervalMs, requireExtension, liveAlertTitle);
        }
    };
});
