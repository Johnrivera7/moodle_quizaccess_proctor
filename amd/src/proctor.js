// @license MIT
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    'use strict';

    var stream = null;
    var sessionId = 0;
    var cmid = 0;
    var attemptid = 0;
    var timer = null;
    var captureWidth = 640;
    var captureHeight = 480;
    var alertTitle = 'Proctoring alert';
    var identityConfig = null;
    var incidentMessages = {};
    var screenStream = null;
    var audioContext = null;
    var options = {};
    var blocked = false;
    var faceTimer = null;
    var identityKick = null;
    var lastUploadAt = 0;

    /**
     * When the identity was last checked in this attempt. Moodle reloads attempt.php on every
     * page/answer submission; without this every page would re-verify (and re-bill) identity.
     *
     * @return {number} ms timestamp, 0 if never.
     */
    function lastIdentityAt() {
        try {
            return parseInt(window.sessionStorage.getItem('moodleProctorIdentity:' + attemptid), 10) || 0;
        } catch (e) {
            return 0;
        }
    }

    /**
     *
     */
    function markIdentityChecked() {
        try {
            window.sessionStorage.setItem('moodleProctorIdentity:' + attemptid, String(Date.now()));
        } catch (e) {
            // Private mode: the server still limits identity checks per interval.
        }
    }
    // Incidents raised before the first snapshot opened the session; sent once it exists.
    var pendingIncidents = [];

    /**
     *
     * @param {string} type
     * @param {string} fallback
     */
    function incidentLabel(type, fallback) {
        if (incidentMessages && incidentMessages[type]) {
            return incidentMessages[type];
        }
        return fallback || type;
    }

    /**
     *
     * @param {string} facesjson
     */
    function parseFaces(facesjson) {
        try {
            var faces = JSON.parse(facesjson || '[]');
            return Array.isArray(faces) ? faces : [];
        } catch (e) {
            return [];
        }
    }

    /**
     *
     */
    function removeExistingAlert() {
        var existing = document.getElementById('proctor-live-alert-banner');
        if (existing) {
            existing.remove();
        }
    }

    /**
     *
     * @param {Object} response
     * @param {string} thumbDataUrl
     */
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

    /**
     * The server blocked the attempt: stop monitoring and cover the page. Removing the overlay
     * changes nothing, since the server refuses the attempt pages until a teacher releases it.
     *
     * @param {string} message
     */
    function showBlockedOverlay(message) {
        if (blocked) {
            return;
        }
        blocked = true;
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
        if (faceTimer) {
            clearInterval(faceTimer);
            faceTimer = null;
        }
        if (identityKick) {
            clearTimeout(identityKick);
            identityKick = null;
        }
        var overlay = showIdentityOverlay(message, false);
        overlay.classList.add('blocking');
        overlay.dataset.lock = 'violation';
        var box = overlay.querySelector('.proctor-identity-box');
        if (identityConfig && identityConfig.blockedtitle) {
            var title = document.createElement('h3');
            title.className = 'proctor-identity-title';
            title.textContent = identityConfig.blockedtitle;
            box.insertBefore(title, box.firstChild);
        }
        var leave = document.createElement('button');
        leave.type = 'button';
        leave.className = 'btn btn-light';
        leave.textContent = (identityConfig && identityConfig.leave) || 'Leave';
        leave.addEventListener('click', function() {
            window.location.href = (identityConfig && identityConfig.courseurl) || '/';
        });
        box.appendChild(leave);
    }

    var cameraWaitStarted = 0;

    /**
     * The camera delivers a usable image: at least ~1 s of video and a frame that is not
     * almost black. Gives up waiting after 8 s so a really dark room still gets checked.
     *
     * @param {HTMLVideoElement} video
     * @return {boolean}
     */
    function cameraReady(video) {
        if (!video.videoWidth || video.readyState < 2) {
            return false;
        }
        if (!cameraWaitStarted) {
            cameraWaitStarted = Date.now();
        }
        if (Date.now() - cameraWaitStarted > 8000) {
            return true;
        }
        if (video.currentTime < 1) {
            return false;
        }
        var probe = document.createElement('canvas');
        probe.width = 32;
        probe.height = 24;
        var ctx = probe.getContext('2d', {willReadFrequently: true});
        ctx.drawImage(video, 0, 0, 32, 24);
        var data = ctx.getImageData(0, 0, 32, 24).data;
        var sum = 0;
        for (var i = 0; i < data.length; i += 4) {
            sum += 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
        }
        return sum / (data.length / 4) > 20;
    }

    /**
     *
     */
    function flushPendingIncidents() {
        var queued = pendingIncidents;
        pendingIncidents = [];
        queued.forEach(function(item) {
            logIncident(item.type, item.severity, item.detail);
        });
    }

    /**
     *
     * @param {Object} response
     * @param {string} thumbDataUrl
     */
    function handleAlertResponse(response, thumbDataUrl) {
        if (response && response.success) {
            if (response.sessionid && !sessionId) {
                sessionId = response.sessionid;
                flushPendingIncidents();
            }
            if (response.blocked) {
                showBlockedOverlay(response.blockmessage || response.alertmessage);
                return;
            }
            showLiveAlert(response, thumbDataUrl);
        }
    }

    /**
     *
     * @param {string} type
     * @param {string} severity
     * @param {Object} detail
     */
    function logIncident(type, severity, detail) {
        if (!sessionId) {
            if (severity === 'info') {
                return;
            }
            pendingIncidents.push({type: type, severity: severity, detail: detail});
            showLiveAlert({
                livealert: true,
                alertseverity: severity || 'warning',
                alertmessage: detail || incidentLabel(type, type),
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

    /**
     *
     * @param {string} message
     * @param {boolean} blocking
     */
    function showIdentityOverlay(message, blocking) {
        var existing = document.getElementById('proctor-identity-overlay');
        if (existing) {
            // A violation block stays on top of everything else.
            if (existing.dataset.lock === 'violation') {
                return existing;
            }
            existing.remove();
        }
        var overlay = document.createElement('div');
        overlay.id = 'proctor-identity-overlay';
        overlay.className = 'proctor-identity-overlay' + (blocking ? ' blocking' : '');
        overlay.setAttribute('role', 'alertdialog');

        var box = document.createElement('div');
        box.className = 'proctor-identity-box';
        var text = document.createElement('p');
        text.className = 'proctor-identity-message';
        text.textContent = message;
        box.appendChild(text);
        overlay.appendChild(box);
        document.body.appendChild(overlay);
        return overlay;
    }

    /**
     *
     * @param {string} message
     * @param {string} actionLabel
     * @param {Function} onAction
     */
    function showLockOverlay(message, actionLabel, onAction) {
        var existing = document.getElementById('proctor-identity-overlay');
        if (blocked || (existing && existing.dataset.lock === 'violation')) {
            // Never replace the violation block; callers get a detached element to tag.
            return document.createElement('div');
        }
        if (existing) {
            existing.remove();
        }
        var overlay = showIdentityOverlay(message, false);
        overlay.classList.add('blocking');
        var box = overlay.querySelector('.proctor-identity-box');
        var action = document.createElement('button');
        action.type = 'button';
        action.className = 'btn btn-primary';
        action.textContent = actionLabel;
        action.addEventListener('click', onAction);
        box.appendChild(action);
        return overlay;
    }

    /**
     *
     */
    function enterFullscreen() {
        var root = document.documentElement;
        if (document.fullscreenElement) {
            var overlay = document.getElementById('proctor-identity-overlay');
            if (overlay && overlay.dataset.lock === 'fullscreen') {
                overlay.remove();
            }
            return;
        }
        var request = root.requestFullscreen || root.webkitRequestFullscreen;
        if (request) {
            request.call(root).catch(function() {
                // The browser requires a click. The overlay button retries.
            });
        }
    }

    /**
     *
     */
    function requireFullscreen() {
        if (document.fullscreenElement) {
            return;
        }
        var current = document.getElementById('proctor-identity-overlay');
        if (current && current.dataset.lock === 'fullscreen') {
            return;
        }
        logIncident('fullscreen_exit', 'critical', identityConfig && identityConfig.fullscreen);
        var overlay = showLockOverlay(
            (identityConfig && identityConfig.fullscreen) || 'Fullscreen required',
            (identityConfig && identityConfig.returnfullscreen) || 'Fullscreen',
            enterFullscreen
        );
        overlay.dataset.lock = 'fullscreen';
    }

    /**
     *
     * @param {MediaStream} mediaStream
     */
    function screenIsMonitor(mediaStream) {
        var track = mediaStream && mediaStream.getVideoTracks ? mediaStream.getVideoTracks()[0] : null;
        if (!track || !track.getSettings) {
            return true;
        }
        var surface = track.getSettings().displaySurface;
        return !surface || surface === 'monitor';
    }

    /**
     *
     */
    function stopScreenShare() {
        if (screenStream) {
            screenStream.getTracks().forEach(function(track) {
                track.stop();
            });
            screenStream = null;
        }
    }

    /**
     *
     */
    function requireScreenShare() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
            return;
        }
        if (screenStream && screenStream.getVideoTracks().some(function(track) {
            return track.readyState === 'live';
        })) {
            return;
        }
        var overlay = showLockOverlay(
            (identityConfig && identityConfig.screenstopped) || 'Share the entire screen',
            (identityConfig && identityConfig.sharescreen) || 'Share screen',
            function() {
                navigator.mediaDevices.getDisplayMedia({
                    video: {displaySurface: 'monitor'},
                    audio: false
                }).then(function(shared) {
                    if (!screenIsMonitor(shared)) {
                        shared.getTracks().forEach(function(track) {
                            track.stop();
                        });
                        logIncident('screen_share_stopped', 'critical');
                        return;
                    }
                    stopScreenShare();
                    screenStream = shared;
                    var current = document.getElementById('proctor-identity-overlay');
                    if (current && current.dataset.lock === 'screen') {
                        current.remove();
                    }
                    var track = shared.getVideoTracks()[0];
                    if (track) {
                        track.addEventListener('ended', function() {
                            screenStream = null;
                            logIncident('screen_share_stopped', 'critical');
                            requireScreenShare();
                        });
                    }
                }).catch(function() {
                    logIncident('screen_share_stopped', 'critical');
                });
            }
        );
        overlay.dataset.lock = 'screen';
    }

    /**
     *
     * @param {MediaStream} mediaStream
     */
    function watchAudio(mediaStream) {
        var track = mediaStream.getAudioTracks && mediaStream.getAudioTracks()[0];
        if (!track || !window.AudioContext) {
            return;
        }
        audioContext = new AudioContext();
        var source = audioContext.createMediaStreamSource(mediaStream);
        var analyser = audioContext.createAnalyser();
        analyser.fftSize = 512;
        source.connect(analyser);
        var samples = new Uint8Array(analyser.fftSize);
        var loudTicks = 0;
        setInterval(function() {
            if (!audioContext || audioContext.state === 'closed') {
                return;
            }
            analyser.getByteTimeDomainData(samples);
            var sum = 0;
            for (var i = 0; i < samples.length; i++) {
                var centered = (samples[i] - 128) / 128;
                sum += centered * centered;
            }
            var rms = Math.sqrt(sum / samples.length);
            if (rms > 0.08) {
                loudTicks++;
                if (loudTicks === 4) {
                    logIncident('audio_detected', 'warning', identityConfig && identityConfig.audio);
                }
            } else {
                loudTicks = 0;
            }
        }, 1000);
    }

    /**
     *
     * @param {Event} event
     */
    function blockShortcuts(event) {
        var key = (event.key || '').toLowerCase();
        var modified = event.ctrlKey || event.metaKey;
        var blocked = ['c', 'v', 'x', 'a', 'p', 's', 'u', 'f'];
        if (modified && blocked.indexOf(key) !== -1) {
            event.preventDefault();
            logIncident('keyboard_shortcut', 'critical');
        }
        if (key === 'f12' || (modified && event.shiftKey && (key === 'i' || key === 'j' || key === 'c'))) {
            event.preventDefault();
            logIncident('keyboard_shortcut', 'critical');
        }
    }

    /**
     * Take a snapshot; the server counts faces and checks it is the enrolled person in the
     * same request, and answers with alerts or a block.
     *
     * @param {number} width
     * @param {number} height
     * @param {boolean} first Show "verifying identity" until the first answer.
     * @param {string} purpose "identity" (faces + identity check) or "faces" (free local face count only).
     */
    function captureAndUpload(width, height, first, purpose) {
        if (blocked) {
            return;
        }
        purpose = purpose || 'identity';
        var video = document.getElementById('proctor-monitor-video');
        if (!video || !stream) {
            logIncident('camera_off', 'critical');
            return;
        }
        if (!cameraReady(video)) {
            // Webcams send a few black/dark frames while they adjust exposure: wait for a real image.
            setTimeout(function() {
                captureAndUpload(width, height, first, purpose);
            }, 500);
            return;
        }
        lastUploadAt = Date.now();
        var checking = first && identityConfig && identityConfig.enabled
            ? showIdentityOverlay(identityConfig.checking, false) : null;
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
                cmid: cmid,
                purpose: purpose
            }
        }])[0].then(function(response) {
            if (checking) {
                checking.remove();
            }
            if (response && response.identitychecked) {
                markIdentityChecked();
            }
            if (first && response && response.identitychecked && response.identitymatched && identityConfig) {
                Notification.addNotification({message: identityConfig.ok, type: 'success'});
            }
            handleAlertResponse(response, image);
        }).catch(function() {
            if (checking) {
                checking.remove();
            }
            Notification.addNotification({
                message: incidentLabel('uploadfailed', 'Proctoring upload failed'),
                type: 'error'
            });
        });
    }

    /**
     * Require the browser extension: it answers every ping; without an answer the exam is covered.
     */
    function watchExtension() {
        var lastOk = Date.now();
        var reported = false;
        document.addEventListener('moodle-proctor-extension-ok', function() {
            lastOk = Date.now();
            reported = false;
            var overlay = document.getElementById('proctor-identity-overlay');
            if (overlay && overlay.dataset.lock === 'extension') {
                overlay.remove();
            }
        });
        setInterval(function() {
            document.dispatchEvent(new CustomEvent('moodle-proctor-ping'));
            if (Date.now() - lastOk < 25000 || blocked) {
                return;
            }
            if (!reported) {
                reported = true;
                logIncident('extension_lost', 'critical', options.missing);
            }
            var current = document.getElementById('proctor-identity-overlay');
            if (!current || current.dataset.lock !== 'extension') {
                var overlay = showLockOverlay(options.missing || 'Extension required', 'OK', function() {
                    window.location.reload();
                });
                overlay.dataset.lock = 'extension';
            }
        }, 10000);
        document.dispatchEvent(new CustomEvent('moodle-proctor-ping'));
    }

    /**
     *
     * @param {number} attemptId
     * @param {number} cmId
     * @param {number} width
     * @param {number} height
     * @param {number} intervalMs
     * @param {Object} opts
     * @param {string} liveAlertTitle
     * @param {Object} identity
     * @param {Object} incidents
     */
    function startMonitor(attemptId, cmId, width, height, intervalMs, opts, liveAlertTitle, identity, incidents) {
        if (!attemptId || attemptId <= 0) {
            return;
        }

        attemptid = attemptId;
        cmid = cmId;
        captureWidth = width;
        captureHeight = height;
        alertTitle = liveAlertTitle || alertTitle;
        identityConfig = identity || null;
        incidentMessages = incidents || {};
        // Older callers passed a boolean "requireExtension".
        options = (typeof opts === 'object' && opts) ? opts : {require: !!opts, webcam: true, fullscreen: true};

        if (!navigator.mediaDevices) {
            Notification.addNotification({
                message: incidentLabel('cameranotsupported', 'Webcam not supported'),
                type: 'error'
            });
            return;
        }

        startCamera(width, height, intervalMs);
        bindPageGuards();
    }

    /**
     *
     * @param {number} width
     * @param {number} height
     * @param {number} intervalMs
     */
    function startCamera(width, height, intervalMs) {
        navigator.mediaDevices.getUserMedia({video: true, audio: false}).then(function(s) {
            stream = s;
            var lock = document.getElementById('proctor-identity-overlay');
            if (lock && lock.dataset.lock === 'camera') {
                lock.remove();
            }
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

            // Identity every intervalMs across the whole attempt, not on every page load.
            var startIdentityLoop = function() {
                timer = setInterval(function() {
                    captureAndUpload(width, height, false, 'identity');
                }, intervalMs);
            };
            var since = Date.now() - lastIdentityAt();
            if (since >= intervalMs) {
                // First page of the attempt (or the interval is due): verify, showing "verifying identity".
                captureAndUpload(width, height, true, 'identity');
                startIdentityLoop();
            } else {
                // Next page: quick face check only, and the identity check when it is due.
                captureAndUpload(width, height, false, 'faces');
                identityKick = setTimeout(function() {
                    identityKick = null;
                    captureAndUpload(width, height, false, 'identity');
                    startIdentityLoop();
                }, intervalMs - since);
            }
            // Frequent free check for a second person / nobody, between identity checks.
            if (options.faceintervalms > 0) {
                faceTimer = setInterval(function() {
                    if (Date.now() - lastUploadAt >= options.faceintervalms / 2) {
                        captureAndUpload(width, height, false, 'faces');
                    }
                }, options.faceintervalms);
            }
            if (options.fullscreen) {
                enterFullscreen();
            }
            if (options.screenshare) {
                requireScreenShare();
            }
            navigator.mediaDevices.getUserMedia({audio: true, video: false}).then(watchAudio).catch(function() {
                // Microphone is optional. The webcam stream still runs.
            });
        }).catch(function() {
            if (options.webcam) {
                logIncident('camera_off', 'critical');
                var overlay = showLockOverlay(options.webcamrequired || incidentLabel('cameradenied', 'Camera required'),
                    options.retrycamera || 'Retry', function() {
                        startCamera(width, height, intervalMs);
                    });
                overlay.dataset.lock = 'camera';
                return;
            }
            Notification.addNotification({
                message: incidentLabel('cameradenied', 'Camera permission required'),
                type: 'error'
            });
        });
    }

    /**
     *
     */
    function bindPageGuards() {
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && sessionId) {
                logIncident('tab_hidden', 'critical');
            }
        });

        // Leaving the exam window (another app or browser): logged with how long it lasted.
        var blurredAt = 0;
        window.addEventListener('blur', function() {
            blurredAt = Date.now();
        });
        window.addEventListener('focus', function() {
            if (!blurredAt) {
                return;
            }
            var seconds = Math.round((Date.now() - blurredAt) / 1000);
            blurredAt = 0;
            if (seconds < 2 || blocked) {
                return;
            }
            var detail = (incidentMessages.awayseconds || '{$a}').replace('{$a}', seconds);
            logIncident('focus_lost', seconds >= 10 ? 'critical' : 'warning', detail);
        });

        document.addEventListener('copy', function(e) {
            e.preventDefault();
            logIncident('copy_attempt', 'critical');
        });

        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        document.addEventListener('keydown', blockShortcuts);

        document.addEventListener('fullscreenchange', function() {
            if (options.fullscreen && !document.fullscreenElement) {
                requireFullscreen();
            }
        });

        document.addEventListener('moodle-proctor-incident', function(e) {
            var type = e.detail && e.detail.type;
            if (!type) {
                return;
            }
            logIncident(type, 'critical');
            if (type === 'multiple_displays' && identityConfig) {
                showLockOverlay(identityConfig.displays || type, identityConfig.returnfullscreen || 'OK', function() {
                    if (window.screen && window.screen.isExtended) {
                        return;
                    }
                    var overlay = document.getElementById('proctor-identity-overlay');
                    if (overlay) {
                        overlay.remove();
                    }
                });
            }
        });

        if (window.screen && window.screen.isExtended) {
            logIncident('multiple_displays', 'critical', identityConfig && identityConfig.displays);
        }

        document.addEventListener('paste', function(e) {
            e.preventDefault();
            logIncident('paste_attempt', 'critical');
        });

        if (options.require) {
            watchExtension();
        }
    }

    return {
        init: function(attemptId, cmId, width, height, intervalMs, opts, liveAlertTitle, identity, incidents) {
            startMonitor(attemptId, cmId, width, height, intervalMs, opts, liveAlertTitle, identity, incidents);
        }
    };
});
