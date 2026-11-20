let audioCtx = null;

function initAudio() {
    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) return null;

    if (!audioCtx) {
        audioCtx = new Ctx();
    }
    if (audioCtx.state === 'suspended') {
        audioCtx.resume();
    }
    return audioCtx;
}

export const kioskAudio = {
    beep() {
        const ctx = initAudio();
        if (!ctx) return;

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(800, ctx.currentTime);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.1);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.1);
    },

    armed() {
        const ctx = initAudio();
        if (!ctx) return;

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'square';
        osc.frequency.setValueAtTime(400, ctx.currentTime);
        osc.frequency.setValueAtTime(600, ctx.currentTime + 0.2);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.linearRampToValueAtTime(0.1, ctx.currentTime + 0.4);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.6);
    },

    urgentBeep() {
        const ctx = initAudio();
        if (!ctx) return;

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(1000, ctx.currentTime);
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.15);
    },

    error() {
        const ctx = initAudio();
        if (!ctx) return;

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(150, ctx.currentTime);
        osc.frequency.setValueAtTime(100, ctx.currentTime + 0.2);
        gain.gain.setValueAtTime(0.2, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.4);
    },

    disarmed() {
        const ctx = initAudio();
        if (!ctx) return;

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(600, ctx.currentTime);
        osc.frequency.setValueAtTime(800, ctx.currentTime + 0.15);
        gain.gain.setValueAtTime(0.1, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.4);
    },

    doorbell() {
        const ctx = initAudio();
        if (!ctx) return;

        const playNote = (freq, startOffset, duration) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, ctx.currentTime + startOffset);
            gain.gain.setValueAtTime(0.22, ctx.currentTime + startOffset);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + startOffset + duration);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(ctx.currentTime + startOffset);
            osc.stop(ctx.currentTime + startOffset + duration);
        };

        playNote(659.25, 0, 0.45);
        playNote(523.25, 0.35, 0.65);
    },
};

window.kioskAudio = kioskAudio;

window.addEventListener('kiosk-sound', (event) => {
    const sound = event.detail?.type;
    if (sound && typeof kioskAudio[sound] === 'function') {
        kioskAudio[sound]();
    }
});

export function bindDialogLightDismissFallback(dialog) {
    if (!dialog || dialog.dataset.lightDismissBound === 'true') return;
    dialog.dataset.lightDismissBound = 'true';

    dialog.addEventListener('close', () => {
        dialog.returnValue = '';
    });

    if (typeof HTMLDialogElement !== 'undefined' && !('closedBy' in HTMLDialogElement.prototype)) {
        const outside = (e) => {
            const r = dialog.getBoundingClientRect();
            if (r.width === 0 && r.height === 0) return false;
            return e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
        };

        let pressedOutside = false;
        dialog.addEventListener('pointerdown', (e) => {
            if (e.target === dialog) {
                pressedOutside = outside(e);
            } else {
                pressedOutside = false;
            }
        });
        dialog.addEventListener('pointerup', (e) => {
            if (pressedOutside && e.target === dialog && outside(e)) {
                dialog.close();
            }
            pressedOutside = false;
        });
    }
}

window.bindDialogLightDismissFallback = bindDialogLightDismissFallback;

export function reolinkStreamTile(config = {}) {
    return {
        webrtcUrl: config.webrtcUrl || null,
        streamUrl: config.streamUrl || null,
        snapshotUrl: config.snapshotUrl || null,
        supportsTwoWayAudio: Boolean(config.supportsTwoWayAudio),
        cameraName: config.name || 'Camera',
        mode: 'idle',
        statusText: 'Verbinden...',
        twoWayActive: false,
        audioMuted: true,
        micError: null,
        snapshotSrc: null,
        pc: null,
        localStream: null,
        remoteStream: null,
        snapshotTimer: null,

        init() {
            this.connect(false);
        },

        destroy() {
            this.teardown();
        },

        teardown() {
            if (this.snapshotTimer) {
                clearInterval(this.snapshotTimer);
                this.snapshotTimer = null;
            }

            if (this.localStream) {
                this.localStream.getTracks().forEach((track) => track.stop());
                this.localStream = null;
            }

            if (this.pc) {
                try {
                    this.pc.ontrack = null;
                    this.pc.close();
                } catch (_) {
                    // Ignore peer close errors
                }
                this.pc = null;
            }

            this.remoteStream = null;

            if (this.$refs?.videoEl) {
                this.$refs.videoEl.srcObject = null;
            }
        },

        isMjpegStream() {
            if (!this.streamUrl) return false;
            return /(\.mjpg|\.mjpeg|mjpeg|stream\.mjpeg)/i.test(this.streamUrl);
        },

        refreshSnapshot() {
            if (!this.snapshotUrl) return;
            const sep = this.snapshotUrl.includes('?') ? '&' : '?';
            this.snapshotSrc = `${this.snapshotUrl}${sep}_t=${Date.now()}`;
        },

        async connect(withTwoWayMic = false) {
            this.teardown();
            this.micError = null;

            if (this.webrtcUrl && typeof RTCPeerConnection !== 'undefined') {
                this.mode = 'connecting';
                this.statusText = withTwoWayMic ? '2-WEG AUDIO STARTEN...' : 'WEBRTC VERBINDEN...';

                try {
                    const pc = new RTCPeerConnection({
                        iceServers: [{ urls: 'stun:stun.l.google.com:19302' }],
                    });
                    this.pc = pc;
                    this.remoteStream = new MediaStream();

                    pc.addTransceiver('video', { direction: 'recvonly' });

                    if (withTwoWayMic && this.supportsTwoWayAudio) {
                        if (!navigator.mediaDevices?.getUserMedia) {
                            this.micError = 'Microfoon vereist HTTPS of localhost in Android browser.';
                            this.twoWayActive = false;
                            pc.addTransceiver('audio', { direction: 'recvonly' });
                        } else {
                            try {
                                this.localStream = await navigator.mediaDevices.getUserMedia({
                                    audio: {
                                        echoCancellation: true,
                                        noiseSuppression: true,
                                        autoGainControl: true,
                                    },
                                    video: false,
                                });
                                this.localStream.getAudioTracks().forEach((track) => {
                                    pc.addTrack(track, this.localStream);
                                });
                                this.twoWayActive = true;
                                this.audioMuted = false;
                            } catch (_) {
                                this.micError = 'Microfoontoegang geweigerd op dit paneel.';
                                this.twoWayActive = false;
                                pc.addTransceiver('audio', { direction: 'recvonly' });
                            }
                        }
                    } else {
                        this.twoWayActive = false;
                        pc.addTransceiver('audio', { direction: 'recvonly' });
                    }

                    pc.ontrack = (event) => {
                        if (!this.remoteStream) {
                            this.remoteStream = new MediaStream();
                        }
                        if (event.streams && event.streams[0]) {
                            event.streams[0].getTracks().forEach((t) => this.remoteStream.addTrack(t));
                        } else if (event.track) {
                            this.remoteStream.addTrack(event.track);
                        }

                        if (this.$refs?.videoEl) {
                            this.$refs.videoEl.srcObject = this.remoteStream;
                            this.$refs.videoEl.muted = this.audioMuted;
                            this.$refs.videoEl.play().catch(() => {});
                        }
                    };

                    const offer = await pc.createOffer();
                    await pc.setLocalDescription(offer);

                    const controller = new AbortController();
                    const timer = setTimeout(() => controller.abort(), 5000);

                    const response = await fetch(this.webrtcUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/sdp' },
                        body: offer.sdp,
                        signal: controller.signal,
                    });

                    clearTimeout(timer);

                    if (!response.ok) {
                        throw new Error(`go2rtc HTTP ${response.status}`);
                    }

                    const answerSdp = await response.text();
                    await pc.setRemoteDescription({
                        type: 'answer',
                        sdp: answerSdp,
                    });

                    this.mode = 'webrtc';
                    this.statusText = this.twoWayActive ? '2-WEG AUDIO ACTIEF' : 'WEBRTC LIVE';
                    return;
                } catch (_) {
                    this.twoWayActive = false;
                    this.teardown();
                }
            }

            if (this.streamUrl) {
                this.mode = this.isMjpegStream() ? 'mjpeg' : 'video';
                this.statusText = 'LIVE STREAM';
                return;
            }

            if (this.snapshotUrl) {
                this.mode = 'snapshot';
                this.statusText = 'LIVE SNAPSHOT';
                this.refreshSnapshot();
                this.snapshotTimer = setInterval(() => this.refreshSnapshot(), 2500);
                return;
            }

            this.mode = 'unconfigured';
            this.statusText = 'WACHT OP STREAM-URL';
        },

        async toggleTwoWayAudio() {
            if (!this.supportsTwoWayAudio) {
                return;
            }

            if (this.twoWayActive) {
                this.audioMuted = true;
                await this.connect(false);
                return;
            }

            if (!this.webrtcUrl) {
                this.micError = 'Stel eerst go2rtc URL + stream in bij Admin → Camera\'s voor 2-weg audio.';
                return;
            }

            await this.connect(true);
        },

        toggleMute() {
            this.audioMuted = !this.audioMuted;
            if (this.$refs?.videoEl) {
                this.$refs.videoEl.muted = this.audioMuted;
            }
        },
    };
}

window.reolinkStreamTile = reolinkStreamTile;

document.addEventListener('alpine:init', () => {
    if (window.Alpine && typeof window.Alpine.data === 'function') {
        window.Alpine.data('reolinkStreamTile', reolinkStreamTile);
    }
});
