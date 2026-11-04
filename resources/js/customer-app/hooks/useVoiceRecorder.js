import { useState, useRef, useCallback, useEffect } from "react";

const MAX_DURATION_SEC = 45;
const MAX_SIZE_BYTES = 3 * 1024 * 1024; // 3 MB

const MIME_CANDIDATES = [
    { mime: "audio/webm;codecs=opus", baseMime: "audio/webm", ext: "webm" },
    { mime: "audio/webm", baseMime: "audio/webm", ext: "webm" },
    { mime: "audio/ogg;codecs=opus", baseMime: "audio/ogg", ext: "ogg" },
    { mime: "audio/mp4", baseMime: "audio/mp4", ext: "mp4" },
];

function negotiateAudioFormat() {
    if (typeof window === "undefined" || typeof window.MediaRecorder === "undefined") {
        return { mime: "", baseMime: "audio/webm", ext: "webm" };
    }
    for (const candidate of MIME_CANDIDATES) {
        if (window.MediaRecorder.isTypeSupported(candidate.mime)) {
            return candidate;
        }
    }
    return { mime: "", baseMime: "audio/webm", ext: "webm" };
}

export function useVoiceRecorder() {
    const [status, setStatus] = useState("idle"); // idle | requesting | recording | recorded | error
    const [audioBlob, setAudioBlob] = useState(null);
    const [audioUrl, setAudioUrl] = useState(null);
    const [audioFilename, setAudioFilename] = useState("voice-order.webm");
    const [durationSec, setDurationSec] = useState(0);
    const [error, setError] = useState(null);

    const mediaRecorderRef = useRef(null);
    const streamRef = useRef(null);
    const chunksRef = useRef([]);
    const timerRef = useRef(null);
    const audioUrlRef = useRef(null);

    const stopStreamTracks = useCallback(() => {
        if (streamRef.current) {
            streamRef.current.getTracks().forEach((track) => track.stop());
            streamRef.current = null;
        }
    }, []);

    const clearTimer = useCallback(() => {
        if (timerRef.current) {
            clearInterval(timerRef.current);
            timerRef.current = null;
        }
    }, []);

    const revokeCurrentUrl = useCallback(() => {
        if (audioUrlRef.current) {
            URL.revokeObjectURL(audioUrlRef.current);
            audioUrlRef.current = null;
        }
    }, []);

    const resetRecording = useCallback(() => {
        clearTimer();
        if (
            mediaRecorderRef.current &&
            mediaRecorderRef.current.state !== "inactive"
        ) {
            try {
                mediaRecorderRef.current.stop();
            } catch {
                // ignore stop error
            }
        }
        stopStreamTracks();
        revokeCurrentUrl();
        chunksRef.current = [];
        setAudioBlob(null);
        setAudioUrl(null);
        setDurationSec(0);
        setError(null);
        setStatus("idle");
    }, [clearTimer, stopStreamTracks, revokeCurrentUrl]);

    const stopRecording = useCallback(() => {
        clearTimer();
        if (
            mediaRecorderRef.current &&
            mediaRecorderRef.current.state === "recording"
        ) {
            mediaRecorderRef.current.stop();
        }
    }, [clearTimer]);

    const startRecording = useCallback(async () => {
        setError(null);

        if (typeof window !== "undefined" && window.isSecureContext === false) {
            setStatus("error");
            setError(
                "Microphone access requires a secure connection. Please open the site using https:// instead of http://.",
            );
            return;
        }

        const hasMediaDevices =
            typeof navigator !== "undefined" &&
            Boolean(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
        const hasMediaRecorder =
            typeof window !== "undefined" &&
            typeof window.MediaRecorder !== "undefined";

        if (!hasMediaDevices || !hasMediaRecorder) {
            setStatus("error");
            setError(
                "Voice recording is not supported in this browser. Please type your order instead.",
            );
            return;
        }

        revokeCurrentUrl();
        setAudioBlob(null);
        setAudioUrl(null);
        setDurationSec(0);
        chunksRef.current = [];
        setStatus("requesting");

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true,
            });
            streamRef.current = stream;

            const format = negotiateAudioFormat();
            const options = format.mime ? { mimeType: format.mime } : undefined;
            const recorder = new window.MediaRecorder(stream, options);
            mediaRecorderRef.current = recorder;

            recorder.ondataavailable = (event) => {
                if (event.data && event.data.size > 0) {
                    chunksRef.current.push(event.data);
                }
            };

            recorder.onstop = () => {
                stopStreamTracks();
                clearTimer();

                if (chunksRef.current.length === 0) {
                    setStatus("error");
                    setError(
                        "No audio was captured. Please hold or tap to speak clearly.",
                    );
                    return;
                }

                const blob = new Blob(chunksRef.current, {
                    type: format.baseMime,
                });

                if (blob.size > MAX_SIZE_BYTES) {
                    setStatus("error");
                    setError(
                        "Voice note exceeds the 3MB limit. Please record a shorter message.",
                    );
                    return;
                }

                const url = URL.createObjectURL(blob);
                audioUrlRef.current = url;
                setAudioFilename(`voice-order.${format.ext}`);
                setAudioBlob(blob);
                setAudioUrl(url);
                setStatus("recorded");
            };

            recorder.onerror = () => {
                stopStreamTracks();
                clearTimer();
                setStatus("error");
                setError("Microphone recording interrupted. Please try again.");
            };

            recorder.start(250);
            setStatus("recording");

            const startedAt = Date.now();
            timerRef.current = setInterval(() => {
                const elapsed = Math.floor((Date.now() - startedAt) / 1000);
                if (elapsed >= MAX_DURATION_SEC) {
                    setDurationSec(MAX_DURATION_SEC);
                    if (recorder.state === "recording") {
                        recorder.stop();
                    }
                } else {
                    setDurationSec(elapsed);
                }
            }, 250);
        } catch (err) {
            stopStreamTracks();
            clearTimer();
            setStatus("error");
            if (
                err?.name === "NotAllowedError" ||
                err?.name === "PermissionDeniedError"
            ) {
                setError(
                    "Microphone permission was denied. Please allow microphone access in your browser settings or type your order.",
                );
            } else {
                setError(
                    "Could not access your microphone. Please check your device or type your order.",
                );
            }
        }
    }, [clearTimer, revokeCurrentUrl, stopStreamTracks]);

    useEffect(() => {
        return () => {
            clearTimer();
            stopStreamTracks();
            revokeCurrentUrl();
        };
    }, [clearTimer, stopStreamTracks, revokeCurrentUrl]);

    return {
        status,
        audioBlob,
        audioUrl,
        audioFilename,
        durationSec,
        maxDurationSec: MAX_DURATION_SEC,
        error,
        startRecording,
        stopRecording,
        resetRecording,
    };
}
