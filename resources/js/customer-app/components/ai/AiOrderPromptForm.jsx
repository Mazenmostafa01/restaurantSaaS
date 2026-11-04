import React, { useId } from "react";
import VoiceRecorderWidget from "./VoiceRecorderWidget";

export default function AiOrderPromptForm({
    activeTab,
    onTabChange,
    textPrompt,
    onTextPromptChange,
    onSubmit,
    examplePrompts = [],
    recorder,
    loading = false,
    apiError = null,
}) {
    const textareaId = useId();

    const handleKeyDown = (event) => {
        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault();
            if (event.nativeEvent?.isComposing || event.keyCode === 229) {
                return;
            }
            onSubmit();
        }
    };

    const isVoice = activeTab === "voice";
    const isSubmitDisabled =
        loading ||
        recorder.status === "recording" ||
        (!isVoice && !textPrompt.trim()) ||
        (isVoice && !recorder.audioBlob);

    return (
        <form onSubmit={onSubmit} className="ai-compose-form">
            <div className="ai-mode-tabs" role="tablist">
                <button
                    type="button"
                    role="tab"
                    aria-selected={!isVoice}
                    className={`ai-mode-tab ${!isVoice ? "active" : ""}`}
                    onClick={() => onTabChange("text")}
                >
                    <span>✍️</span>
                    <span>Type Order</span>
                </button>
                <button
                    type="button"
                    role="tab"
                    aria-selected={isVoice}
                    className={`ai-mode-tab ${isVoice ? "active" : ""}`}
                    onClick={() => onTabChange("voice")}
                >
                    <span>🎙️</span>
                    <span>Voice Note</span>
                </button>
            </div>

            {!isVoice ? (
                <div className="ai-text-pane">
                    <label htmlFor={textareaId} className="form-label">
                        Write what you&apos;d like to eat (Arabic, Franco, or English)
                    </label>
                    <div className="ai-textarea-wrapper">
                        <textarea
                            id={textareaId}
                            dir="auto"
                            className="form-textarea ai-order-textarea"
                            placeholder="مثال: عايز اتنين كشري كبير واحد بدون بصل، وأحمد واحد طاجن..."
                            value={textPrompt}
                            maxLength={2000}
                            rows={4}
                            disabled={loading}
                            onChange={(e) => onTextPromptChange(e.target.value)}
                            onKeyDown={handleKeyDown}
                        />
                        <div className="ai-textarea-footer">
                            <span className="ai-char-hint">
                                Press Enter to analyze • Shift+Enter for new line
                            </span>
                            <span className="ai-char-count">
                                {textPrompt.length} / 2000
                            </span>
                        </div>
                    </div>

                    {examplePrompts.length > 0 && (
                        <div className="ai-examples-box">
                            <span className="ai-examples-label">
                                Try an example:
                            </span>
                            <div className="ai-examples-chips">
                                {examplePrompts.map((example) => (
                                    <button
                                        key={example.label}
                                        type="button"
                                        className="ai-example-chip"
                                        onClick={() =>
                                            onTextPromptChange(example.text)
                                        }
                                    >
                                        <strong>{example.label}:</strong>{" "}
                                        <span>{example.text}</span>
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            ) : (
                <div className="ai-voice-pane">
                    <VoiceRecorderWidget
                        status={recorder.status}
                        durationSec={recorder.durationSec}
                        maxDurationSec={recorder.maxDurationSec}
                        audioUrl={recorder.audioUrl}
                        error={recorder.error}
                        onStart={recorder.startRecording}
                        onStop={recorder.stopRecording}
                        onReset={recorder.resetRecording}
                        disabled={loading}
                    />
                </div>
            )}

            {apiError && <div className="form-error">{apiError}</div>}

            <button
                type="submit"
                className="btn btn-primary btn-block ai-submit-btn"
                disabled={isSubmitDisabled}
            >
                {loading ? (
                    <>
                        <progress
                            aria-label="Analyzing order with AI"
                            className="loading-spinner"
                        />
                        <span>AI is matching dishes & prices...</span>
                    </>
                ) : (
                    <span>✨ Analyze Order with AI</span>
                )}
            </button>
        </form>
    );
}
