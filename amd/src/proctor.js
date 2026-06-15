// @license MIT
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';

    var stream = null;
    var sessionId = 0;
    var cmid = 0;
    var attemptid = 0;
    var timer = null;


    function logIncident(type, severity, detail) {
        if (!sessionId) {
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
        }]);
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
            if (response.success) {
                sessionId = response.sessionid;
            }
        }).catch(function() {
            Notification.addNotification({
                message: 'Proctoring upload failed',
                type: 'error'
            });
        });
    }

    function startMonitor(attemptId, cmId, width, height, intervalMs, requireExtension) {
        attemptid = attemptId;
        cmid = cmId;

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
                logIncident('tab_hidden', 'warning');
            }
        });

        window.addEventListener('blur', function() {
            logIncident('focus_lost', 'info');
        });

        document.addEventListener('copy', function(e) {
            e.preventDefault();
            logIncident('copy_attempt', 'warning');
        });

        document.addEventListener('paste', function(e) {
            e.preventDefault();
            logIncident('paste_attempt', 'warning');
        });

        if (requireExtension) {
            setInterval(function() {
                document.dispatchEvent(new CustomEvent('moodle-proctor-ping'));
            }, 15000);
        }
    }

    return {
        init: function(attemptId, cmId, width, height, intervalMs, requireExtension) {
            startMonitor(attemptId, cmId, width, height, intervalMs, requireExtension);
        }
    };
});
