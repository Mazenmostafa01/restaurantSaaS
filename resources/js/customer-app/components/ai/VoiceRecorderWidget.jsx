import React from "react";

function formatDuration(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${String(mins).padStart(2, "0")}:${String(secs).padStart(2, "0")}`;
}

export default function VoiceRecorderWidget({
    status,
    durationSec,
    maxDurationSec,
    audioUrl,
    error,
    onStart,
    onStop,
    onReset,
    disabled = false,
    promptTitle = "Tap the microphone and speak your order",
    promptHint = "Up to 45 seconds • Supports Egyptian Arabic & English",
}) {
    return (
        <div className="ai-voice-stage">
            {status === "recording" ? (
                <>
                    <button
                        type="button"
                        className="ai-mic-btn recording"
                        onClick={onStop}
                        aria-label="Stop recording"
                    >
                        <span className="ai-mic-pulse" />
                        <svg
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            width="28"
                            height="28"
                        >
                            <rect x="6" y="6" width="12" height="12" rx="2" />
                        </svg>
                    </button>
                    <div className="ai-voice-timer">
                        <span className="ai-recording-dot" />
                        <span>
                            Recording {formatDuration(durationSec)} /{" "}
                            {formatDuration(maxDurationSec)}
                        </span>
                    </div>
                    <p className="ai-voice-hint">
                        Speak clearly. Tap stop when finished.
                    </p>
                </>
            ) : status === "recorded" && audioUrl ? (
                <div className="ai-recorded-card">
                    <div className="ai-recorded-head">
                        <span className="ai-recorded-badge">
                            ✅ Voice Note Ready (
                            {formatDuration(durationSec || 1)})
                        </span>
                        <button
                            type="button"
                            className="ai-rerecord-btn"
                            onClick={onReset}
                        >
                            Discard & Re-record
                        </button>
                    </div>
                    <audio
                        controls
                        src={audioUrl}
                        className="ai-audio-player"
                    />
                </div>
            ) : (
                <>
                    <button
                        type="button"
                        className="ai-mic-btn"
                        onClick={onStart}
                        disabled={status === "requesting" || disabled}
                        aria-label="Start voice recording"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            width="30"
                            height="30"
                        >
                            <path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z" />
                            <path d="M19 10v2a7 7 0 01-14 0v-2" />
                            <line x1="12" y1="19" x2="12" y2="23" />
                            <line x1="8" y1="23" x2="16" y2="23" />
                        </svg>
                    </button>
                    <p className="ai-voice-title">
                        {status === "requesting"
                            ? "Requesting microphone access..."
                            : promptTitle}
                    </p>
                    <p className="ai-voice-hint">{promptHint}</p>
                </>
            )}
            {error && <div className="form-error">{error}</div>}
        </div>
    );
}
