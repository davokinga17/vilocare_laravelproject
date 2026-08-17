@extends('layouts.app')

@section('page_title', 'ViLoCare ChatBot')

@push('styles')
    <link href="{{ asset('css/ai-assistant.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="ai-workspace">
    <aside class="ai-control-panel">
        <div class="ai-control-heading">
            <div>
                <span class="ai-kicker">Clinical support workspace</span>
                <h2>ViLoCare Assistant</h2>
            </div>
            <span class="ai-online {{ $assistantEnabled ? 'is-online' : '' }}">
                <span></span>{{ $assistantEnabled ? 'Online' : 'Offline' }}
            </span>
        </div>

        <p class="ai-intro">Your intelligent assistant for viral load management and ART clinic support.</p>

        <form method="GET" action="{{ route('ai.assistant.index') }}" class="ai-context-form">
            <label for="patient_id">Select patient context</label>
            <div class="ai-context-row">
                <select id="patient_id" name="patient_id" class="form-select">
                    <option value="">General ViLoCare Assistant</option>
                    @foreach($patients as $patient)
                        <option value="{{ $patient->patient_id }}" @selected((string) request('patient_id') === (string) $patient->patient_id)>
                            {{ $patient->art_number }} - {{ trim($patient->first_name . ' ' . $patient->last_name) }}
                        </option>
                    @endforeach
                </select>
                <button type="submit">Load</button>
            </div>
        </form>

        <div class="ai-capabilities" aria-label="Assistant capabilities">
            <div><span>PT</span>Patient management support</div>
            <div><span>VL</span>Viral load monitoring help</div>
            <div><span>EA</span>ART, EAC &amp; protocols knowledge</div>
            <div><span>DT</span>Data interpretation &amp; decision support</div>
        </div>

        <div class="ai-safety-note">
            <span>i</span>
            <p>Operational guidance only. Diagnosis, treatment decisions, and medication changes remain with a clinician.</p>
        </div>

        @if($selectedPatient)
            <div class="ai-patient-context">
                <div class="ai-patient-context-head">
                    <div>
                        <span>Active patient</span>
                        <strong>{{ $selectedPatient->first_name }} {{ $selectedPatient->last_name }}</strong>
                    </div>
                    <a href="{{ route('patients.show', $selectedPatient->patient_id) }}">Open</a>
                </div>
                <p>ART {{ $selectedPatient->art_number }} · {{ $selectedPatient->phone ?: 'No phone recorded' }}</p>
                @foreach($signals as $signal)
                    <div class="ai-signal ai-signal-{{ $signal['level'] }}">
                        <strong>{{ $signal['title'] }}</strong>
                        <span>{{ $signal['detail'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </aside>

    <section class="ai-chat-panel">
        <header class="ai-chat-header">
            <span class="ai-kicker">{{ $summary['period'] ?? 'All time' }}</span>
            <h1>Assistant Workspace</h1>
            <p>{{ $selectedPatient ? 'Ask a question using the selected patient context and ViLoCare system summary.' : 'Ask a question or request assistance below.' }}</p>
        </header>

        <div class="ai-prompt-chips" aria-label="Suggested questions">
            <button type="button" class="assistant-chip" data-question="Help me review patient management priorities in ViLoCare.">
                <span>PT</span>Patient Management
            </button>
            <button type="button" class="assistant-chip" data-question="Explain the viral load workflow and which records need follow-up.">
                <span>VL</span>Viral Load Help
            </button>
            <button type="button" class="assistant-chip" data-question="Explain the EAC workflow and pending follow-up actions.">
                <span>EA</span>EAC Guidance
            </button>
            <button type="button" class="assistant-chip" data-question="Draft a respectful SMS reminder for a patient due for viral load follow-up.">
                <span>SM</span>SMS Drafting
            </button>
        </div>

        <form id="assistantForm" class="ai-composer">
            @csrf
            <input type="hidden" id="assistantPatientId" value="{{ $selectedPatient?->patient_id }}">
            <label class="visually-hidden" for="assistantQuestion">Ask the ViLoCare Assistant</label>
            <textarea id="assistantQuestion" rows="2" placeholder="Ask anything about viral care, patient management, system workflows, or follow-up..." @disabled(!$assistantEnabled)></textarea>
            <button type="submit" aria-label="Send question" @disabled(!$assistantEnabled)>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 13-7-4 14-3-5-6-2Z"/><path d="m11 14 7-9"/></svg>
            </button>
        </form>

        <div class="ai-status-row">
            <span id="assistantStatus">{{ $assistantEnabled ? $assistantProvider . ' is ready.' : 'Configure ' . $assistantProvider . ' in the environment to enable this assistant.' }}</span>
            <span>Secure staff workspace</span>
        </div>

        <div class="ai-conversation-heading">
            <h2>Conversation</h2>
            <button type="button" id="clearConversation">Clear</button>
        </div>

        <div id="assistantThread" class="ai-thread" aria-live="polite">
            <div class="ai-empty-state" id="assistantEmptyState">
                <div class="ai-empty-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 15a4 4 0 0 1-4 4H8l-4 3v-7a4 4 0 0 1-1-2.7V8a4 4 0 0 1 4-4h9a4 4 0 0 1 4 4v7Z"/></svg>
                </div>
                <strong>Your conversation will appear here</strong>
                <p>Choose a topic above or type a question to begin.</p>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    const assistantAskUrl = @json(route('ai.assistant.ask'));
    const aiCsrfToken = @json(csrf_token());
    const assistantForm = document.getElementById('assistantForm');
    const assistantQuestion = document.getElementById('assistantQuestion');
    const assistantPatientId = document.getElementById('assistantPatientId');
    const assistantThread = document.getElementById('assistantThread');
    const assistantStatus = document.getElementById('assistantStatus');
    const assistantSubmit = assistantForm?.querySelector('button[type="submit"]');

    function appendBubble(text, kind) {
        document.getElementById('assistantEmptyState')?.remove();
        const row = document.createElement('div');
        row.className = `ai-message ai-message-${kind}`;

        const label = document.createElement('span');
        label.className = 'ai-message-avatar';
        label.textContent = kind === 'user' ? 'You' : 'AI';

        const bubble = document.createElement('div');
        bubble.className = 'ai-message-bubble';
        bubble.textContent = text;

        row.append(label, bubble);
        assistantThread.appendChild(row);
        assistantThread.scrollTop = assistantThread.scrollHeight;
    }

    document.querySelectorAll('.assistant-chip').forEach((chip) => {
        chip.addEventListener('click', () => {
            assistantQuestion.value = chip.dataset.question || '';
            assistantQuestion.focus();
        });
    });

    document.getElementById('clearConversation')?.addEventListener('click', () => {
        assistantThread.innerHTML = `
            <div class="ai-empty-state" id="assistantEmptyState">
                <div class="ai-empty-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 15a4 4 0 0 1-4 4H8l-4 3v-7a4 4 0 0 1-1-2.7V8a4 4 0 0 1 4-4h9a4 4 0 0 1 4 4v7Z"/></svg></div>
                <strong>Your conversation will appear here</strong>
                <p>Choose a topic above or type a question to begin.</p>
            </div>`;
    });

    assistantQuestion?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            assistantForm.requestSubmit();
        }
    });

    assistantForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const question = assistantQuestion?.value.trim();
        if (!question) return;

        appendBubble(question, 'user');
        assistantQuestion.value = '';
        assistantStatus.textContent = 'Thinking...';
        assistantSubmit.disabled = true;

        try {
            const response = await fetch(assistantAskUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': aiCsrfToken
                },
                body: JSON.stringify({ question, patient_id: assistantPatientId?.value || '' })
            });
            const payload = await response.json();
            appendBubble(payload.answer || 'No assistant response received.', response.ok ? 'assistant' : 'error');
            assistantStatus.textContent = response.ok ? 'Ready for the next question.' : 'The assistant could not complete that request.';
        } catch (error) {
            appendBubble('The assistant is currently unreachable. Please try again.', 'error');
            assistantStatus.textContent = 'Connection failed.';
        } finally {
            assistantSubmit.disabled = false;
            assistantQuestion.focus();
        }
    });
</script>
@endpush
